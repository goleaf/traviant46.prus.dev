<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Models\Game\CapturedUnit;
use App\Models\Game\MovementOrder;
use App\Models\Game\ReinforcementGarrison;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use App\Models\Report;
use App\Models\ReportRecipient;
use App\Support\Travian\UnitCatalog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplyStarvationAction
{
    private const RECOVERY_WINDOW_HOURS = 1.0;

    public function __construct(
        private readonly UnitCatalog $unitCatalog,
    ) {}

    public function execute(Village $village): void
    {
        if ($this->currentCropBalance($village) >= 0) {
            return;
        }

        $villageId = (int) $village->getKey();

        DB::transaction(function () use ($villageId): void {
            /** @var Village|null $locked */
            $locked = Village::query()
                ->whereKey($villageId)
                ->lockForUpdate()
                ->first();

            if (! $locked instanceof Village) {
                return;
            }

            $locked->loadMissing([
                'owner',
                'watcher',
                'units.unitType',
                'stationedReinforcements.owner',
                'capturedUnits.owner',
                'movements.owner',
                'incomingMovements.owner',
            ]);

            if ($this->currentCropBalance($locked) >= 0) {
                return;
            }

            $state = $this->initialiseState($locked);
            $records = [];

            $records = array_merge($records, $this->starveForeignReinforcements($locked, $state));
            $records = array_merge($records, $this->starveLocalReinforcements($locked, $state));
            $records = array_merge($records, $this->starveCapturedUnits($locked, $state));
            $records = array_merge($records, $this->starveVillageUnits($locked, $state));
            $records = array_merge($records, $this->starveMovements($locked, $state));

            $this->updateVillageResources($locked, $state);

            if ($records !== []) {
                $this->logReport($locked, $state, $records);
            }
        });

        $village->refresh();
    }

    /**
     * @return array<string, float>
     */
    private function initialiseState(Village $village): array
    {
        return [
            'current_crop' => (float) $this->currentCropBalance($village),
            'net_crop_per_hour' => (float) $this->calculateNetCropPerHour($village),
            'recovered_upkeep' => 0.0,
        ];
    }

    private function starveForeignReinforcements(Village $village, array &$state): array
    {
        if (! $this->needsFurtherStarvation($state)) {
            return [];
        }

        /** @var EloquentCollection<int, ReinforcementGarrison> $garrisons */
        $garrisons = $village->stationedReinforcements
            ->filter(fn (ReinforcementGarrison $garrison): bool => (int) $garrison->stationed_village_id === (int) $village->getKey())
            ->filter(fn (ReinforcementGarrison $garrison): bool => $garrison->owner_user_id === null || $garrison->owner_user_id !== $village->user_id);

        return $this->starveReinforcements($garrisons, $state, 'foreign_reinforcement');
    }

    private function starveLocalReinforcements(Village $village, array &$state): array
    {
        if (! $this->needsFurtherStarvation($state)) {
            return [];
        }

        /** @var EloquentCollection<int, ReinforcementGarrison> $garrisons */
        $garrisons = $village->stationedReinforcements
            ->filter(fn (ReinforcementGarrison $garrison): bool => (int) $garrison->stationed_village_id === (int) $village->getKey())
            ->filter(fn (ReinforcementGarrison $garrison): bool => $garrison->owner_user_id !== null && $garrison->owner_user_id === $village->user_id);

        return $this->starveReinforcements($garrisons, $state, 'local_reinforcement');
    }

    private function starveReinforcements(EloquentCollection $garrisons, array &$state, string $category): array
    {
        $records = [];

        foreach ($garrisons as $garrison) {
            if (! $this->needsFurtherStarvation($state)) {
                break;
            }

            $units = (array) ($garrison->unit_composition ?? []);
            if ($units === []) {
                continue;
            }

            $tribe = $this->resolveTribe(
                data_get($garrison->metadata, 'race'),
                optional($garrison->owner)->race,
            );

            $result = $this->starveComposition(
                $units,
                $tribe,
                $state,
                function (array $updated) use ($garrison, $tribe): void {
                    $composition = $this->convertToUnitKeys($updated);
                    $garrison->unit_composition = $composition;
                    $garrison->upkeep = $this->unitCatalog->calculateCompositionUpkeep($tribe, $composition);
                    $garrison->is_active = array_sum($composition) > 0;
                    $garrison->last_synced_at = Carbon::now();

                    $metadata = (array) ($garrison->metadata ?? []);
                    $metadata['last_starved_at'] = Carbon::now()->toIso8601String();
                    $garrison->metadata = $metadata;

                    $garrison->save();
                },
            );

            if ($result === []) {
                continue;
            }

            $records[] = array_merge([
                'category' => $category,
                'reference_id' => (int) $garrison->getKey(),
                'tribe' => $tribe,
                'owner_user_id' => $garrison->owner_user_id,
            ], $result);
        }

        return $records;
    }

    private function starveCapturedUnits(Village $village, array &$state): array
    {
        if (! $this->needsFurtherStarvation($state)) {
            return [];
        }

        /** @var EloquentCollection<int, CapturedUnit> $captured */
        $captured = $village->capturedUnits
            ->filter(fn (CapturedUnit $unit): bool => $unit->status === 'captured');

        $records = [];

        foreach ($captured as $unit) {
            if (! $this->needsFurtherStarvation($state)) {
                break;
            }

            $units = (array) ($unit->unit_composition ?? []);
            if ($units === []) {
                continue;
            }

            $tribe = $this->resolveTribe(
                data_get($unit->metadata, 'race'),
                optional($unit->owner)->race,
                optional($village->owner)->race,
            );

            $result = $this->starveComposition(
                $units,
                $tribe,
                $state,
                function (array $updated) use ($unit): void {
                    $composition = $this->convertToUnitKeys($updated);
                    if (array_sum($composition) <= 0) {
                        $unit->unit_composition = [];
                        $unit->status = 'executed';
                        $unit->executed_at = Carbon::now();
                    } else {
                        $unit->unit_composition = $composition;
                    }

                    $metadata = (array) ($unit->metadata ?? []);
                    $metadata['last_starved_at'] = Carbon::now()->toIso8601String();
                    $unit->metadata = $metadata;

                    $unit->save();
                },
            );

            if ($result === []) {
                continue;
            }

            $records[] = array_merge([
                'category' => 'trapped_units',
                'reference_id' => (int) $unit->getKey(),
                'tribe' => $tribe,
                'owner_user_id' => $unit->owner_user_id,
            ], $result);
        }

        return $records;
    }

    private function starveVillageUnits(Village $village, array &$state): array
    {
        if (! $this->needsFurtherStarvation($state)) {
            return [];
        }

        $records = [];

        /** @var EloquentCollection<int, VillageUnit> $units */
        $units = $village->units;

        foreach ($units as $unit) {
            if (! $this->needsFurtherStarvation($state)) {
                break;
            }

            $quantity = (int) $unit->quantity;
            if ($quantity <= 0) {
                continue;
            }

            $unitType = $unit->unitType;
            $perUnitUpkeep = (int) ($unitType?->upkeep ?? 0);

            if ($perUnitUpkeep <= 0) {
                continue;
            }

            $killed = 0;
            while ($this->needsFurtherStarvation($state) && $quantity > 0) {
                $kill = $this->determineKillAmount($quantity, $state, $perUnitUpkeep);
                if ($kill <= 0) {
                    break;
                }

                $quantity -= $kill;
                $killed += $kill;

                $delta = $perUnitUpkeep * $kill;
                $state['current_crop'] += $delta * self::RECOVERY_WINDOW_HOURS;
                $state['net_crop_per_hour'] += $delta;
                $state['recovered_upkeep'] += $delta;
            }

            if ($killed <= 0) {
                continue;
            }

            $unit->quantity = $quantity;
            $unit->save();

            $slot = $this->unitCatalog->slotForTypeId($unit->unit_type_id);
            $tribe = $unitType?->tribe ?? $this->resolveTribe(optional($village->owner)->race);

            $records[] = [
                'category' => 'home_garrison',
                'reference_id' => (int) $unit->getKey(),
                'unit_type_id' => $unit->unit_type_id,
                'tribe' => $tribe,
                'units' => $slot !== null
                    ? ['u'.$slot => $killed]
                    : ['unit_type_id' => $unit->unit_type_id, 'killed' => $killed],
                'recovered_upkeep' => $perUnitUpkeep * $killed,
            ];
        }

        return $records;
    }

    private function starveMovements(Village $village, array &$state): array
    {
        if (! $this->needsFurtherStarvation($state)) {
            return [];
        }

        $records = [];

        /** @var Collection<int, MovementOrder> $movements */
        $movements = $this->relevantMovements($village);

        foreach ($movements as $movement) {
            if (! $this->needsFurtherStarvation($state)) {
                break;
            }

            $units = (array) data_get($movement->payload, 'units', []);
            if ($units === []) {
                continue;
            }

            $tribe = $this->resolveTribe(
                data_get($movement->metadata, 'race'),
                optional($movement->owner)->race,
                optional($village->owner)->race,
            );

            $direction = $movement->origin_village_id === $village->getKey() ? 'outbound' : 'returning';

            $result = $this->starveComposition(
                $units,
                $tribe,
                $state,
                function (array $updated) use ($movement): void {
                    $payload = (array) ($movement->payload ?? []);
                    $payload['units'] = $this->convertToUnitKeys($updated);
                    $movement->payload = $payload;

                    $metadata = (array) ($movement->metadata ?? []);
                    $metadata['last_starved_at'] = Carbon::now()->toIso8601String();
                    $movement->metadata = $metadata;

                    $movement->save();
                },
            );

            if ($result === []) {
                continue;
            }

            $records[] = array_merge([
                'category' => 'movement',
                'reference_id' => (int) $movement->getKey(),
                'tribe' => $tribe,
                'owner_user_id' => $movement->user_id,
                'movement_type' => $movement->movement_type,
                'direction' => $direction,
            ], $result);
        }

        return $records;
    }

    /**
     * @param array<string|int, int> $units
     * @return array<string, mixed>
     */
    private function starveComposition(array $units, ?int $tribe, array &$state, callable $persist): array
    {
        $normalized = $this->normaliseComposition($units);
        if ($normalized === []) {
            return [];
        }

        $killed = [];
        $recovered = 0.0;

        foreach ($this->determineKillOrder($normalized, $tribe) as $slot) {
            $available = $normalized[$slot] ?? 0;
            if ($available <= 0) {
                continue;
            }

            $unitUpkeep = $this->unitCatalog->upkeepForSlot($tribe, $slot);
            if ($unitUpkeep <= 0) {
                continue;
            }

            while ($this->needsFurtherStarvation($state) && $available > 0) {
                $kill = $this->determineKillAmount($available, $state, $unitUpkeep);
                if ($kill <= 0) {
                    break 2;
                }

                $available -= $kill;
                $normalized[$slot] = $available;
                $killed[$slot] = ($killed[$slot] ?? 0) + $kill;

                $delta = $unitUpkeep * $kill;
                $state['current_crop'] += $delta * self::RECOVERY_WINDOW_HOURS;
                $state['net_crop_per_hour'] += $delta;
                $state['recovered_upkeep'] += $delta;
                $recovered += $delta;
            }

            if (! $this->needsFurtherStarvation($state)) {
                break;
            }
        }

        if ($killed === []) {
            return [];
        }

        $persist($normalized);

        return [
            'units' => $this->formatUnits($killed),
            'recovered_upkeep' => (int) round($recovered),
        ];
    }

    private function updateVillageResources(Village $village, array &$state): void
    {
        $balances = (array) ($village->resource_balances ?? []);
        $balances['crop'] = (int) max(0, floor($state['current_crop']));
        $village->resource_balances = $balances;

        $storage = (array) ($village->storage ?? []);
        if ($state['net_crop_per_hour'] >= 0) {
            $this->clearGranaryTimers($storage);
        } else {
            $this->scheduleGranaryRetry($storage);
        }
        $village->storage = $storage;

        $village->save();
    }

    private function logReport(Village $village, array $state, array $records): void
    {
        $report = Report::query()->create([
            'user_id' => $village->user_id,
            'origin_village_id' => $village->getKey(),
            'target_village_id' => $village->getKey(),
            'report_type' => 'system.starvation',
            'category' => 'economy',
            'delivery_scope' => 'system',
            'is_system_generated' => true,
            'is_persistent' => false,
            'payload' => [
                'killed' => $records,
                'recovered_upkeep_per_hour' => (int) round($state['recovered_upkeep']),
                'remaining_crop_balance' => data_get($village->resource_balances, 'crop', 0),
                'net_crop_per_hour' => $state['net_crop_per_hour'],
            ],
            'triggered_at' => Carbon::now(),
            'metadata' => [
                'starvation' => [
                    'granary_empty_eta' => data_get($village->storage, 'granary_empty_eta'),
                ],
            ],
        ]);

        $recipients = collect([$village->owner, $village->watcher])
            ->filter(fn ($user) => $user !== null)
            ->uniqueStrict(fn ($user) => $user->getKey());

        foreach ($recipients as $recipient) {
            ReportRecipient::query()->create([
                'report_id' => $report->getKey(),
                'recipient_id' => $recipient->getKey(),
                'visibility_scope' => 'personal',
                'status' => 'unread',
            ]);
        }
    }

    private function calculateNetCropPerHour(Village $village): float
    {
        $productionRates = (array) $village->production_rates;
        $cropProduction = (float) ($productionRates['crop'] ?? 0);
        $populationUpkeep = (float) $village->population;
        $troopUpkeep = $this->calculateTroopUpkeep($village);

        return $cropProduction - $populationUpkeep - $troopUpkeep;
    }

    private function calculateTroopUpkeep(Village $village): float
    {
        $total = 0.0;

        foreach ($village->units as $unit) {
            $upkeep = (int) ($unit->unitType?->upkeep ?? 0);
            $total += $upkeep * (int) $unit->quantity;
        }

        foreach ($village->stationedReinforcements as $garrison) {
            if ((int) $garrison->stationed_village_id !== (int) $village->getKey()) {
                continue;
            }

            $tribe = $this->resolveTribe(
                data_get($garrison->metadata, 'race'),
                optional($garrison->owner)->race,
            );

            $total += $this->unitCatalog->calculateCompositionUpkeep($tribe, (array) $garrison->unit_composition);
        }

        foreach ($village->capturedUnits as $unit) {
            if ($unit->status !== 'captured') {
                continue;
            }

            $tribe = $this->resolveTribe(
                data_get($unit->metadata, 'race'),
                optional($unit->owner)->race,
                optional($village->owner)->race,
            );

            $total += $this->unitCatalog->calculateCompositionUpkeep($tribe, (array) $unit->unit_composition);
        }

        foreach ($this->relevantMovements($village) as $movement) {
            $tribe = $this->resolveTribe(
                data_get($movement->metadata, 'race'),
                optional($movement->owner)->race,
                optional($village->owner)->race,
            );

            $total += $this->unitCatalog->calculateCompositionUpkeep($tribe, (array) data_get($movement->payload, 'units', []));
        }

        return $total;
    }

    private function currentCropBalance(Village $village): float
    {
        return (float) data_get($village->resource_balances, 'crop', 0);
    }

    private function needsFurtherStarvation(array $state): bool
    {
        return $state['current_crop'] < 0 || $state['net_crop_per_hour'] < 0;
    }

    /**
     * @param array<string|int, int> $units
     * @return array<int, int>
     */
    private function normaliseComposition(array $units): array
    {
        $normalized = [];

        foreach ($units as $key => $value) {
            $slot = $this->parseSlot($key);
            if ($slot <= 0) {
                continue;
            }

            $normalized[$slot] = ($normalized[$slot] ?? 0) + max(0, (int) $value);
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param array<int, int> $units
     * @return array<string, int>
     */
    private function convertToUnitKeys(array $units): array
    {
        $result = [];
        foreach ($units as $slot => $amount) {
            if ($amount > 0) {
                $result['u'.$slot] = (int) $amount;
            }
        }

        return $result;
    }

    /**
     * @param array<int, int> $units
     * @return array<int>
     */
    private function determineKillOrder(array $units, ?int $tribe): array
    {
        $scores = [];

        foreach ($units as $slot => $count) {
            if ($count <= 0) {
                continue;
            }

            $scores[$slot] = $count * $this->unitCatalog->upkeepForSlot($tribe, $slot);
        }

        arsort($scores, SORT_NUMERIC);

        return array_keys($scores);
    }

    private function determineKillAmount(int $available, array $state, int $unitUpkeep): int
    {
        if ($unitUpkeep <= 0) {
            return 0;
        }

        $stockDeficit = $state['current_crop'] < 0
            ? (int) ceil(abs($state['current_crop']) / ($unitUpkeep * self::RECOVERY_WINDOW_HOURS))
            : 0;

        $netDeficit = $state['net_crop_per_hour'] < 0
            ? (int) ceil(abs($state['net_crop_per_hour']) / $unitUpkeep)
            : 0;

        $kill = max($stockDeficit, $netDeficit, 1);

        return min($available, $kill);
    }

    /**
     * @param array<int, int> $killed
     * @return array<string, int>
     */
    private function formatUnits(array $killed): array
    {
        $formatted = [];

        foreach ($killed as $slot => $amount) {
            if ($amount > 0) {
                $formatted['u'.$slot] = (int) $amount;
            }
        }

        ksort($formatted);

        return $formatted;
    }

    /**
     * @param array<int|string, string|null> $storage
     */
    private function clearGranaryTimers(array &$storage): void
    {
        unset($storage['granary_empty_eta'], $storage['granary_empty_at']);

        if (isset($storage['granary']) && is_array($storage['granary'])) {
            unset($storage['granary']['empty_eta'], $storage['granary']['empty_at']);
            if ($storage['granary'] === []) {
                unset($storage['granary']);
            }
        }

        if (isset($storage['timers']) && is_array($storage['timers'])) {
            unset($storage['timers']['granary_empty']);
            if ($storage['timers'] === []) {
                unset($storage['timers']);
            }
        }
    }

    /**
     * @param array<int|string, mixed> $storage
     */
    private function scheduleGranaryRetry(array &$storage): void
    {
        $retryAt = Carbon::now()->addMinutes(5)->toIso8601String();

        $storage['granary_empty_eta'] = $retryAt;
        $storage['granary_empty_at'] = $retryAt;

        $storage['granary'] = array_merge(
            is_array($storage['granary'] ?? null) ? $storage['granary'] : [],
            ['empty_eta' => $retryAt, 'empty_at' => $retryAt],
        );

        $storage['timers'] = array_merge(
            is_array($storage['timers'] ?? null) ? $storage['timers'] : [],
            ['granary_empty' => $retryAt],
        );
    }

    private function resolveTribe(mixed ...$candidates): ?int
    {
        foreach ($candidates as $value) {
            if ($value === null) {
                continue;
            }

            if (is_numeric($value)) {
                $tribe = (int) $value;
                if ($tribe > 0) {
                    return $tribe;
                }
            }
        }

        return null;
    }

    /**
     * @return Collection<int, MovementOrder>
     */
    private function relevantMovements(Village $village): Collection
    {
        $outgoing = $village->movements
            ->filter(fn (MovementOrder $movement): bool => (int) data_get($movement->metadata, 'mode') === 0);

        $returning = $village->incomingMovements
            ->filter(fn (MovementOrder $movement): bool => (int) data_get($movement->metadata, 'mode') === 1);

        return $outgoing
            ->merge($returning)
            ->unique(fn (MovementOrder $movement) => $movement->getKey())
            ->values();
    }

    private function parseSlot(string|int $key): int
    {
        if (is_int($key)) {
            return $key;
        }

        $trimmed = trim((string) $key);
        if ($trimmed === '') {
            return 0;
        }

        if (str_starts_with($trimmed, 'u')) {
            $trimmed = substr($trimmed, 1);
        }

        return is_numeric($trimmed) ? (int) $trimmed : 0;
    }
}
