<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Game\Economy\Events\ResourcesProduced;
use App\Events\Game\ResourceStorageWarning;
use App\Jobs\Concerns\InteractsWithShardResolver;
use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use App\Services\Game\VillageUpkeepService;
use App\Services\ResourceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResourceTickJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    /**
     * Supported Travian resource identifiers.
     *
     * @var array<int, string>
     */
    private const RESOURCE_KEYS = ['wood', 'clay', 'iron', 'crop'];

    /**
     * Production percentage bonuses granted by owned oasis types.
     *
     * @var array<int, array<string, float>>
     */
    private const OASIS_TYPE_PRODUCTION_BONUSES = [
        1 => ['wood' => 25.0],
        2 => ['clay' => 25.0],
        3 => ['iron' => 25.0],
        4 => ['crop' => 25.0],
    ];

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        private readonly int $chunkSize = 200,
        int $shard = 0,
        private readonly ?int $tickIntervalSeconds = null,
    ) {
        $this->initializeShardPartitioning($shard);
        $this->onQueue('automation');
    }

    public function handle(ResourceService $resourceCalculator, VillageUpkeepService $upkeepService): void
    {
        $chunkSize = max(1, $this->chunkSize);
        $tickSeconds = max(1, $this->tickIntervalSeconds ?? (int) config('game.tick_interval_seconds', 60));
        $now = Carbon::now();
        $totalShards = $this->shardResolver()->total();
        $currentShard = $this->shard();

        $this->constrainToShard(Village::query()->select(['id']), 'id')
            ->orderBy('id')
            ->chunkById($chunkSize, function (EloquentCollection $villages) use ($resourceCalculator, $upkeepService, $tickSeconds, $now, $currentShard, $totalShards): void {
                foreach ($villages as $village) {
                    try {
                        $payload = $this->processVillage(
                            (int) $village->getKey(),
                            $resourceCalculator,
                            $upkeepService,
                            $tickSeconds,
                            $now,
                        );

                        if ($payload === null) {
                            continue;
                        }

                        $channelName = $this->buildChannelName($payload['village_id']);
                        ResourcesProduced::dispatch($channelName, $payload);

                        $warnings = $payload['warnings'] ?? [];

                        if (is_array($warnings) && $warnings !== []) {
                            foreach ($warnings as $warning) {
                                if (! is_array($warning)) {
                                    continue;
                                }

                                $warningPayload = array_merge(
                                    $warning,
                                    [
                                        'village_id' => $payload['village_id'],
                                        'processed_at' => $payload['processed_at'],
                                        'interval_seconds' => $payload['interval_seconds'],
                                    ],
                                );

                                ResourceStorageWarning::dispatch($channelName, $warningPayload);
                            }
                        }

                    } catch (Throwable $exception) {
                        Log::error('resource.tick.failed', [
                            'village_id' => (int) $village->getKey(),
                            'shard_index' => $currentShard,
                            'shard_count' => $totalShards,
                            'exception' => $exception,
                        ]);
                    }
                }
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function processVillage(
        int $villageId,
        ResourceService $resourceCalculator,
        VillageUpkeepService $upkeepService,
        int $tickSeconds,
        Carbon $now,
    ): ?array {
        $payload = null;

        DB::transaction(function () use (&$payload, $villageId, $resourceCalculator, $upkeepService, $tickSeconds, $now): void {
            /** @var Village|null $village */
            $village = Village::query()
                ->whereKey($villageId)
                ->lockForUpdate()
                ->with([
                    'resources' => static function ($query): void {
                        $query->select([
                            'id',
                            'village_id',
                            'resource_type',
                            'production_per_hour',
                            'storage_capacity',
                            'bonuses',
                            'last_collected_at',
                        ]);
                    },
                    'ownedOases:id,type',
                ])
                ->first();

            if ($village === null) {
                return;
            }

            $storageState = is_array($village->storage) ? $village->storage : [];
            $reservations = $this->extractReservations($storageState);

            $balancesBefore = $this->normaliseResourceMap($village->resource_balances ?? []);
            $productionData = $this->calculateProduction($village, $upkeepService);
            $storageCapacities = $this->determineStorageCapacities($village);

            $updateResult = $resourceCalculator->updateResources(
                $balancesBefore,
                $productionData['per_hour'],
                $tickSeconds,
                [
                    'precision' => 4,
                    'minimum' => 0,
                    'allow_negative_crop' => true,
                    'storage' => $storageCapacities,
                ],
            );

            $balancesAfter = $this->normaliseResourceMap($updateResult['resources']);

            $reservations = $this->reconcileReservations($reservations, $balancesAfter);
            $storageState = $this->updateStorageReservations($storageState, $reservations);

            $village->resource_balances = $balancesAfter;
            $village->storage = $storageState;
            $village->save();

            $produced = [];
            foreach (self::RESOURCE_KEYS as $resource) {
                $produced[$resource] = round($balancesAfter[$resource] - $balancesBefore[$resource], 4);
            }

            /** @var EloquentCollection<int, VillageResource> $resources */
            $resources = $village->getRelation('resources');
            foreach ($resources as $resourceModel) {
                $resourceModel->last_collected_at = $now;
                $resourceModel->save();
            }

            $warnings = $this->detectStorageWarnings($balancesAfter, $storageCapacities, $reservations);

            $payload = [
                'village_id' => $village->getKey(),
                'interval_seconds' => $tickSeconds,
                'processed_at' => $now->toIso8601String(),
                'produced' => $produced,
                'balances' => array_map(static fn (float $value): float => round($value, 4), $balancesAfter),
                'per_hour' => $productionData['per_hour'],
                'base' => $productionData['base'],
                'building' => $productionData['building'],
                'oasis_bonus' => $productionData['oasis_bonus'],
                'upkeep' => $productionData['upkeep'],
                'storage' => $storageCapacities,
                'overflow' => array_map(static fn (float $value): float => round($value, 4), $updateResult['overflow']),
                'had_overflow' => (bool) $updateResult['hadOverflow'],
                'reservations' => $this->normaliseReservationMap($reservations),
                'warnings' => $warnings,
            ];
        }, 5);

        return $payload;
    }

    /**
     * @param array<string, mixed> $balances
     *
     * @return array<string, float>
     */
    private function normaliseResourceMap(array $balances): array
    {
        $normalised = [];

        foreach (self::RESOURCE_KEYS as $resource) {
            $value = $balances[$resource] ?? 0;
            $normalised[$resource] = is_numeric($value) ? (float) $value : 0.0;
        }

        return $normalised;
    }

    /**
     * @return array{
     *     per_hour: array<string, float>,
     *     base: array<string, float>,
     *     building: array<string, float>,
     *     oasis_bonus: array<string, float>
     * }
     */
    private function calculateProduction(Village $village, VillageUpkeepService $upkeepService): array
    {
        $base = $this->normaliseResourceMap((array) ($village->production ?? []));
        $building = array_fill_keys(self::RESOURCE_KEYS, 0.0);
        $oasisPercent = $this->calculateOwnedOasisPercent($village);

        /** @var EloquentCollection<int, VillageResource> $resources */
        $resources = $village->getRelation('resources');

        foreach ($resources as $resourceModel) {
            $resourceType = $resourceModel->resource_type;

            if (! in_array($resourceType, self::RESOURCE_KEYS, true)) {
                continue;
            }

            $building[$resourceType] += (float) ($resourceModel->production_per_hour ?? 0);

            $oasisBonus = Arr::get((array) ($resourceModel->bonuses ?? []), 'oasis');

            if (is_numeric($oasisBonus)) {
                $oasisPercent[$resourceType] += (float) $oasisBonus;
            } elseif (is_array($oasisBonus)) {
                foreach ($oasisBonus as $key => $value) {
                    if (! is_numeric($value)) {
                        continue;
                    }

                    if (in_array($key, self::RESOURCE_KEYS, true)) {
                        $oasisPercent[$key] += (float) $value;

                        continue;
                    }

                    if (in_array($key, ['all', '*', 'global'], true)) {
                        foreach (self::RESOURCE_KEYS as $resource) {
                            $oasisPercent[$resource] += (float) $value;
                        }
                    }
                }
            }
        }

        $perHourRaw = [];
        $oasisBonusValues = [];

        foreach (self::RESOURCE_KEYS as $resource) {
            $baseProduction = $base[$resource] + $building[$resource];
            $oasisProduction = $baseProduction * ($oasisPercent[$resource] / 100);
            $perHourRaw[$resource] = $baseProduction + $oasisProduction;
            $oasisBonusValues[$resource] = round($oasisProduction, 4);
        }

        $upkeep = $upkeepService->calculate($village);
        $perHourRaw['crop'] -= (float) ($upkeep['per_hour'] ?? 0.0);

        $perHour = array_map(static fn (float $value): float => round($value, 4), $perHourRaw);

        return [
            'per_hour' => $perHour,
            'base' => $base,
            'building' => $building,
            'oasis_bonus' => $oasisBonusValues,
            'upkeep' => $upkeep,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function calculateOwnedOasisPercent(Village $village): array
    {
        $percent = array_fill_keys(self::RESOURCE_KEYS, 0.0);

        $ownedOases = $village->getRelation('ownedOases');

        if (! $ownedOases instanceof EloquentCollection) {
            $ownedOases = $village->ownedOases()->select(['id', 'type'])->get();
        }

        foreach ($ownedOases as $oasis) {
            $type = (int) ($oasis->type ?? 0);
            $bonuses = self::OASIS_TYPE_PRODUCTION_BONUSES[$type] ?? [];

            foreach ($bonuses as $resource => $value) {
                if (! in_array($resource, self::RESOURCE_KEYS, true)) {
                    continue;
                }

                $percent[$resource] += (float) $value;
            }
        }

        return $percent;
    }

    /**
     * @return array<string, float>
     */
    private function determineStorageCapacities(Village $village): array
    {
        $storage = (array) ($village->storage ?? []);

        $warehouse = (float) ($storage['warehouse'] ?? 0);
        $granary = (float) ($storage['granary'] ?? 0);

        $capacities = [
            'warehouse' => $warehouse,
            'granary' => $granary,
            'wood' => $warehouse,
            'clay' => $warehouse,
            'iron' => $warehouse,
            'crop' => $granary,
        ];

        /** @var EloquentCollection<int, VillageResource> $resources */
        $resources = $village->getRelation('resources');

        foreach ($resources as $resourceModel) {
            $resourceType = $resourceModel->resource_type;
            $extraCapacity = (float) ($resourceModel->storage_capacity ?? 0);

            if ($extraCapacity <= 0) {
                continue;
            }

            if ($resourceType === 'crop') {
                $capacities['crop'] += $extraCapacity;

                continue;
            }

            if (in_array($resourceType, ['wood', 'clay', 'iron'], true)) {
                $capacities[$resourceType] += $extraCapacity;
            }
        }

        return array_map(static fn (float $value): float => max(0.0, round($value, 4)), $capacities);
    }

    /**
     * @param array<string, mixed> $storage
     *
     * @return array<string, float>
     */
    private function extractReservations(array $storage): array
    {
        $reservations = [];
        $rawReservations = $storage['reservations'] ?? [];

        foreach (self::RESOURCE_KEYS as $resource) {
            $value = is_array($rawReservations) ? ($rawReservations[$resource] ?? 0) : 0;
            $reservations[$resource] = is_numeric($value) ? (float) $value : 0.0;
        }

        return $reservations;
    }

    /**
     * @param array<string, float> $reservations
     * @param array<string, float> $balances
     *
     * @return array<string, float>
     */
    private function reconcileReservations(array $reservations, array $balances): array
    {
        $clamped = [];

        foreach (self::RESOURCE_KEYS as $resource) {
            $requested = (float) ($reservations[$resource] ?? 0.0);
            $available = (float) ($balances[$resource] ?? 0.0);

            $clamped[$resource] = round(max(0.0, min($requested, $available)), 4);
        }

        return $clamped;
    }

    /**
     * @param array<string, mixed> $storage
     * @param array<string, float> $reservations
     *
     * @return array<string, mixed>
     */
    private function updateStorageReservations(array $storage, array $reservations): array
    {
        $filtered = array_filter(
            array_map(static fn (float $value): float => round($value, 4), $reservations),
            static fn (float $value): bool => $value > 0.0,
        );

        if ($filtered === []) {
            unset($storage['reservations']);

            return $storage;
        }

        $storage['reservations'] = $filtered;

        return $storage;
    }

    /**
     * @param array<string, float> $balances
     * @param array<string, float> $capacities
     * @param array<string, float> $reservations
     *
     * @return array<int, array<string, float|int|string>>
     */
    private function detectStorageWarnings(
        array $balances,
        array $capacities,
        array $reservations = []
    ): array {
        $warnings = [];

        foreach (self::RESOURCE_KEYS as $resource) {
            $capacity = (float) ($capacities[$resource] ?? 0.0);

            if ($capacity <= 0.0) {
                continue;
            }

            $amount = (float) ($balances[$resource] ?? 0.0);
            $percent = $capacity > 0.0 ? ($amount / $capacity) * 100.0 : 0.0;

            if ($percent < 90.0) {
                continue;
            }

            $reserved = (float) ($reservations[$resource] ?? 0.0);

            $warnings[] = [
                'resource' => $resource,
                'amount' => round($amount, 4),
                'capacity' => round($capacity, 4),
                'percent' => round(min($percent, 100.0), 2),
                'reserved' => round($reserved, 4),
                'available' => round(max(0.0, $amount - $reserved), 4),
                'threshold' => 90.0,
            ];
        }

        return array_values($warnings);
    }

    /**
     * @param array<string, float> $reservations
     *
     * @return array<string, float>
     */
    private function normaliseReservationMap(array $reservations): array
    {
        $normalised = [];

        foreach (self::RESOURCE_KEYS as $resource) {
            $value = $reservations[$resource] ?? 0.0;
            $normalised[$resource] = is_numeric($value) ? round((float) $value, 4) : 0.0;
        }

        return $normalised;
    }

    private function buildChannelName(int $villageId): string
    {
        return sprintf('game.village.%d', $villageId);
    }
}
