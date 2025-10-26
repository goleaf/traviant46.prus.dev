<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Game\ResourcesProduced;
use App\Jobs\Concerns\InteractsWithShardResolver;
use App\Models\Game\Village;
use App\Models\Game\VillageResource;
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
     * Ordered list of Travian resource identifiers.
     *
     * @var array<int, string>
     */
    private const RESOURCE_KEYS = ['wood', 'clay', 'iron', 'crop'];

    public int $tries = 1;

    public int $timeout = 120;

    public string $queue = 'automation';

    public function __construct(
        private readonly int $chunkSize = 200,
        int $shard = 0,
        private readonly ?int $tickIntervalSeconds = null,
    ) {
        $this->initializeShardPartitioning($shard);
    }

    public function handle(ResourceService $resourceCalculator): void
    {
        $chunkSize = max(1, $this->chunkSize);
        $tickSeconds = max(1, $this->tickIntervalSeconds ?? (int) config('game.tick_interval_seconds', 60));
        $now = Carbon::now();
        $totalShards = $this->shardResolver()->total();
        $currentShard = $this->shard();

        $this->constrainToShard(Village::query()->select(['id']), 'id')
            ->orderBy('id')
            ->chunkById($chunkSize, function (EloquentCollection $villages) use ($resourceCalculator, $tickSeconds, $now, $currentShard, $totalShards): void {
                foreach ($villages as $village) {
                    try {
                        $payload = $this->processVillage(
                            (int) $village->getKey(),
                            $resourceCalculator,
                            $tickSeconds,
                            $now,
                        );

                        if ($payload === null) {
                            continue;
                        }

                        $channelName = $this->buildChannelName($payload['village_id']);
                        ResourcesProduced::dispatch($channelName, $payload);
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
        int $tickSeconds,
        Carbon $now,
    ): ?array {
        $payload = null;

        DB::transaction(function () use (&$payload, $villageId, $resourceCalculator, $tickSeconds, $now): void {
            /** @var Village|null $village */
            $village = Village::query()
                ->whereKey($villageId)
                ->lockForUpdate()
                ->with([
                    'resources' => function ($query): void {
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
                ])
                ->first();

            if ($village === null) {
                return;
            }

            $balancesBefore = $this->normaliseResourceMap($village->resource_balances ?? []);
            $productionData = $this->calculateProduction($village);
            $storageCapacities = $this->determineStorageCapacities($village);

            $updateResult = $resourceCalculator->updateResources(
                $balancesBefore,
                $productionData['per_hour'],
                $tickSeconds,
                [
                    'precision' => 4,
                    'minimum' => 0,
                    'storage' => $storageCapacities,
                ],
            );

            $balancesAfter = $this->normaliseResourceMap($updateResult['resources']);

            $village->resource_balances = $balancesAfter;
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
                'storage' => $storageCapacities,
                'overflow' => array_map(static fn (float $value): float => round($value, 4), $updateResult['overflow']),
                'had_overflow' => (bool) $updateResult['hadOverflow'],
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
    private function calculateProduction(Village $village): array
    {
        $base = $this->normaliseResourceMap((array) ($village->production ?? []));
        $building = array_fill_keys(self::RESOURCE_KEYS, 0.0);
        $oasisPercent = array_fill_keys(self::RESOURCE_KEYS, 0.0);

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

        $perHour = [];
        $oasisBonusValues = [];

        foreach (self::RESOURCE_KEYS as $resource) {
            $baseProduction = $base[$resource] + $building[$resource];
            $oasisProduction = $baseProduction * ($oasisPercent[$resource] / 100);
            $perHour[$resource] = round($baseProduction + $oasisProduction, 4);
            $oasisBonusValues[$resource] = round($oasisProduction, 4);
        }

        return [
            'per_hour' => $perHour,
            'base' => $base,
            'building' => $building,
            'oasis_bonus' => $oasisBonusValues,
        ];
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

    private function buildChannelName(int $villageId): string
    {
        return sprintf('game.village.%d', $villageId);
    }
}
