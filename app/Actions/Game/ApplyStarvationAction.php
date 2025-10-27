<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Enums\Game\MovementOrderStatus;
use App\Models\Game\MovementOrder;
use App\Models\Game\ReinforcementGarrison;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use App\Models\Report;
use App\Models\ReportRecipient;
use App\Repositories\Game\TroopRepository;
use App\Repositories\Game\VillageRepository;
use App\Support\Travian\UnitCatalog;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

/**
 * Apply starvation effects to villages when upkeep exceeds production.
 */
class ApplyStarvationAction
{
    /**
     * Inject repositories to access troop counts and village resource production.
     */
    public function __construct(
        private readonly TroopRepository $troopRepository,
        private readonly VillageRepository $villageRepository,
        private readonly UnitCatalog $unitCatalog,
        private readonly DatabaseManager $database,
    ) {
    }

    /**
     * Execute the starvation balancing adjustments.
     */
    public function execute(Village $village): void
    {
        // Run all starvation work inside a transaction so partial kills never occur.
        $this->database->transaction(function () use ($village): void {
            $lockedVillage = $this->villageRepository->lock($village, [
                'units.unitType',
                'stationedReinforcements.owner',
                'movements.owner',
                'owner',
                'watcher',
            ]);

            $cropBalance = (int) data_get($lockedVillage->resource_balances, 'crop', 0);
            $production = (int) data_get($lockedVillage->production, 'crop', 0);
            $population = (int) $lockedVillage->population;

            // Determine troop upkeep contributions across each category in priority order.
            $homeUnits = $this->troopRepository->unitsWithUpkeep($lockedVillage);
            $reinforcements = $lockedVillage->stationedReinforcements()->active()->get();
            $movements = $lockedVillage->movements()
                ->whereIn('status', [
                    MovementOrderStatus::Pending->value,
                    MovementOrderStatus::InTransit->value,
                ])
                ->get();

            $foreignReinforcements = $reinforcements->filter(fn (ReinforcementGarrison $garrison): bool => $this->isForeignGarrison($lockedVillage, $garrison))->values();
            $localReinforcements = $reinforcements->reject(fn (ReinforcementGarrison $garrison): bool => $this->isForeignGarrison($lockedVillage, $garrison))->values();

            $foreignUpkeep = (int) $foreignReinforcements->sum('upkeep');
            $localUpkeep = (int) $localReinforcements->sum('upkeep');
            $homeUpkeep = (int) $homeUnits->sum(fn (VillageUnit $unit): int => $this->upkeepForVillageUnit($unit));
            $movementUpkeep = (int) $movements->sum(fn (MovementOrder $movement): int => $this->movementUpkeep($movement, $lockedVillage));

            $totalConsumption = $population + $homeUpkeep + $foreignUpkeep + $localUpkeep + $movementUpkeep;
            $initialShortage = max(0, $totalConsumption - $production);

            if ($initialShortage <= 0 && $cropBalance >= 0) {
                // No starvation required, but still clear lingering timers if balance already recovered.
                $this->refreshVillageStorage($lockedVillage, $cropBalance);

                return;
            }

            $shortage = $initialShortage;
            $casualties = [];
            $recoveredUpkeep = 0;

            // Reduce foreign reinforcements first to free up upkeep from allies.
            $recoveredUpkeep += $this->starveReinforcements($lockedVillage, $foreignReinforcements, $shortage, 'foreign_reinforcement', $casualties);

            if ($shortage > 0) {
                // Then sacrifice locally owned reinforcements if deficit persists.
                $recoveredUpkeep += $this->starveReinforcements($lockedVillage, $localReinforcements, $shortage, 'local_reinforcement', $casualties);
            }

            if ($shortage > 0) {
                // Thin out the home garrison as a last line of defence.
                $recoveredUpkeep += $this->starveHomeUnits($homeUnits, $shortage, $casualties);
            }

            if ($shortage > 0) {
                // Finally cancel outgoing movement upkeep if troops are still in flight.
                $recoveredUpkeep += $this->starveMovements($movements, $lockedVillage, $shortage, $casualties);
            }

            $clearedDebt = abs(min(0, $cropBalance));

            $this->refreshVillageStorage($lockedVillage, 0);

            $this->logStarvationReport(
                $lockedVillage,
                $casualties,
                $recoveredUpkeep,
                $clearedDebt,
                $initialShortage,
                $shortage,
            );
        });
    }

    /**
     * Decide whether the garrison belongs to another village owner.
     */
    private function isForeignGarrison(Village $village, ReinforcementGarrison $garrison): bool
    {
        $ownerId = $garrison->owner_user_id;

        if ($ownerId === null) {
            return true;
        }

        return (int) $ownerId !== (int) $village->user_id;
    }

    /**
     * Calculate upkeep for a specific home unit row.
     */
    private function upkeepForVillageUnit(VillageUnit $unit): int
    {
        $quantity = max(0, (int) ($unit->quantity ?? 0));
        $perUnitUpkeep = (int) ($unit->unitType?->upkeep ?? 0);

        return $quantity * $perUnitUpkeep;
    }

    /**
     * Resolve upkeep for a movement order based on its payload and tribe.
     */
    private function movementUpkeep(MovementOrder $movement, Village $village): int
    {
        $composition = (array) data_get($movement->payload, 'units', []);

        if ($composition === []) {
            return 0;
        }

        $tribe = $this->resolveMovementTribe($movement, $village);

        return $this->unitCatalog->calculateCompositionUpkeep($tribe, $composition);
    }

    /**
     * Determine the best available tribe context for a moving army.
     */
    private function resolveMovementTribe(MovementOrder $movement, Village $village): ?int
    {
        $metadata = (array) ($movement->metadata ?? []);
        $payload = (array) ($movement->payload ?? []);

        $candidates = [
            $metadata['race'] ?? null,
            $metadata['tribe'] ?? null,
            $payload['tribe'] ?? null,
            $movement->owner?->tribe,
            $village->owner?->tribe,
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }
        }

        return null;
    }

    /**
     * Remove reinforcement units while shortage persists and record casualties.
     *
     * @param Collection<int, ReinforcementGarrison> $garrisons
     * @param array<int, array<string, mixed>> $casualties
     */
    private function starveReinforcements(
        Village $village,
        Collection $garrisons,
        int &$shortage,
        string $category,
        array &$casualties
    ): int {
        $recovered = 0;

        foreach ($garrisons as $garrison) {
            if ($shortage <= 0) {
                break;
            }

            $result = $this->cullGarrison($village, $garrison, $shortage);

            if ($result['recovered_upkeep'] <= 0) {
                continue;
            }

            $recovered += $result['recovered_upkeep'];

            $casualties[] = [
                'category' => $category,
                'reference_id' => (int) $garrison->getKey(),
                'owner_user_id' => $garrison->owner_user_id,
                'units' => $result['killed_units'],
                'recovered_upkeep' => $result['recovered_upkeep'],
            ];
        }

        return $recovered;
    }

    /**
     * Remove troops from a single reinforcement garrison.
     *
     * @return array{recovered_upkeep: int, killed_units: array<string, int>}
     */
    private function cullGarrison(Village $village, ReinforcementGarrison $garrison, int &$shortage): array
    {
        $composition = (array) ($garrison->unit_composition ?? []);
        $tribe = $this->resolveGarrisonTribe($village, $garrison);
        $orderedSlots = $this->sortCompositionByUpkeep($composition, $tribe);

        $killed = [];
        $recovered = 0;

        foreach ($orderedSlots as $slot => $entry) {
            if ($shortage <= 0) {
                break;
            }

            $quantity = (int) $entry['quantity'];
            $perUnit = max(1, (int) $entry['upkeep']);

            if ($quantity <= 0) {
                continue;
            }

            $kill = min($quantity, (int) ceil($shortage / $perUnit));

            if ($kill <= 0) {
                continue;
            }

            $composition[$slot] = $quantity - $kill;

            if ($composition[$slot] <= 0) {
                unset($composition[$slot]);
            }

            $recovered += $kill * $perUnit;
            $shortage = max(0, $shortage - ($kill * $perUnit));
            $killed[$slot] = ($killed[$slot] ?? 0) + $kill;
        }

        if ($killed !== []) {
            $garrison->unit_composition = $composition;
            $garrison->upkeep = $this->unitCatalog->calculateCompositionUpkeep($tribe, $composition);
            $garrison->save();
        }

        return [
            'recovered_upkeep' => $recovered,
            'killed_units' => $killed,
        ];
    }

    /**
     * Resolve the tribe context for reinforcements.
     */
    private function resolveGarrisonTribe(Village $village, ReinforcementGarrison $garrison): ?int
    {
        $metadata = (array) ($garrison->metadata ?? []);
        $candidates = [
            $metadata['race'] ?? null,
            $metadata['tribe'] ?? null,
            $garrison->owner?->tribe,
            $village->owner?->tribe,
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }
        }

        return null;
    }

    /**
     * Sort a reinforcement composition by per-unit upkeep so expensive troops die first.
     *
     * @param array<string, int> $composition
     * @return array<string, array{quantity: int, upkeep: int}>
     */
    private function sortCompositionByUpkeep(array $composition, ?int $tribe): array
    {
        $mapped = [];

        foreach ($composition as $slot => $quantity) {
            $normalized = max(0, (int) $quantity);

            if ($normalized <= 0) {
                continue;
            }

            $slotNumber = $this->normalizeSlotKey($slot);
            $perUnit = $slotNumber !== null
                ? $this->unitCatalog->upkeepForSlot($tribe, $slotNumber)
                : 0;

            $mapped[$slot] = [
                'quantity' => $normalized,
                'upkeep' => max(1, $perUnit),
            ];
        }

        uasort($mapped, static fn (array $left, array $right): int => $right['upkeep'] <=> $left['upkeep']);

        return $mapped;
    }

    /**
     * Convert slot keys like `u1` into integers for catalogue lookups.
     */
    private function normalizeSlotKey(int|string $slot): ?int
    {
        $trimmed = trim((string) $slot);

        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, 'u')) {
            $trimmed = substr($trimmed, 1);
        }

        if (! is_numeric($trimmed)) {
            return null;
        }

        return (int) $trimmed;
    }

    /**
     * Thin the home garrison using the same upkeep-first approach.
     *
     * @param Collection<int, VillageUnit> $units
     * @param array<int, array<string, mixed>> $casualties
     */
    private function starveHomeUnits(Collection $units, int &$shortage, array &$casualties): int
    {
        $recovered = 0;

        $sorted = $units->filter(fn (VillageUnit $unit): bool => (int) ($unit->unitType?->upkeep ?? 0) > 0)
            ->sortByDesc(fn (VillageUnit $unit): int => (int) ($unit->unitType?->upkeep ?? 0));

        foreach ($sorted as $unit) {
            if ($shortage <= 0) {
                break;
            }

            $perUnit = max(1, (int) ($unit->unitType?->upkeep ?? 0));
            $quantity = max(0, (int) ($unit->quantity ?? 0));

            if ($quantity <= 0) {
                continue;
            }

            $kill = min($quantity, (int) ceil($shortage / $perUnit));

            if ($kill <= 0) {
                continue;
            }

            $this->troopRepository->reduceUnit($unit, $kill);

            $recovered += $kill * $perUnit;
            $shortage = max(0, $shortage - ($kill * $perUnit));

            $casualties[] = [
                'category' => 'home_garrison',
                'unit_type_id' => (int) $unit->unit_type_id,
                'killed' => $kill,
                'recovered_upkeep' => $kill * $perUnit,
            ];
        }

        return $recovered;
    }

    /**
     * Cancel upkeep from outbound movements by trimming their payloads.
     *
     * @param Collection<int, MovementOrder> $movements
     * @param array<int, array<string, mixed>> $casualties
     */
    private function starveMovements(Collection $movements, Village $village, int &$shortage, array &$casualties): int
    {
        $recovered = 0;

        foreach ($movements as $movement) {
            if ($shortage <= 0) {
                break;
            }

            $composition = (array) data_get($movement->payload, 'units', []);
            $tribe = $this->resolveMovementTribe($movement, $village);
            $ordered = $this->sortCompositionByUpkeep($composition, $tribe);
            $killedUnits = [];
            $movementRecovered = 0;

            foreach ($ordered as $slot => $entry) {
                if ($shortage <= 0) {
                    break;
                }

                $quantity = (int) $entry['quantity'];
                $perUnit = max(1, (int) $entry['upkeep']);

                if ($quantity <= 0) {
                    continue;
                }

                $kill = min($quantity, (int) ceil($shortage / $perUnit));

                if ($kill <= 0) {
                    continue;
                }

                $composition[$slot] = $quantity - $kill;

                if ($composition[$slot] <= 0) {
                    unset($composition[$slot]);
                }

                $recoveredAmount = $kill * $perUnit;
                $recovered += $recoveredAmount;
                $movementRecovered += $recoveredAmount;
                $shortage = max(0, $shortage - $recoveredAmount);
                $killedUnits[$slot] = ($killedUnits[$slot] ?? 0) + $kill;
            }

            if ($killedUnits === []) {
                continue;
            }

            $payload = (array) ($movement->payload ?? []);
            $payload['units'] = $composition;
            $movement->payload = $payload;
            $movement->save();

            $casualties[] = [
                'category' => 'movement',
                'movement_id' => (int) $movement->getKey(),
                'units' => $killedUnits,
                'recovered_upkeep' => $movementRecovered,
            ];
        }

        return $recovered;
    }

    /**
     * Reset crop debt and clear any granary depletion timers stored in metadata.
     */
    private function refreshVillageStorage(Village $village, int $cropBalance): void
    {
        $balances = (array) ($village->resource_balances ?? []);
        $balances['crop'] = max(0, $cropBalance);

        $storage = (array) ($village->storage ?? []);
        $keys = [
            'granary_empty_eta',
            'granary_empty_at',
            'granary.empty_eta',
            'granary.empty_at',
            'timers.granary_empty',
        ];

        foreach ($keys as $key) {
            data_set($storage, $key, null);
        }

        $village->resource_balances = $balances;
        $village->storage = $storage;
        $village->save();
    }

    /**
     * Create a system report describing the starvation outcome.
     *
     * @param array<int, array<string, mixed>> $casualties
     */
    private function logStarvationReport(
        Village $village,
        array $casualties,
        int $recoveredUpkeep,
        int $clearedDebt,
        int $initialShortage,
        int $remainingShortage
    ): void {
        if ($casualties === [] && $clearedDebt === 0 && $recoveredUpkeep === 0) {
            return;
        }

        $report = Report::query()->create([
            'origin_village_id' => $village->getKey(),
            'target_village_id' => $village->getKey(),
            'report_type' => 'system.starvation',
            'category' => 'village',
            'is_system_generated' => true,
            'is_persistent' => false,
            'payload' => [
                'village_id' => $village->getKey(),
                'population' => (int) $village->population,
                'production_per_hour' => (int) data_get($village->production, 'crop', 0),
                'cleared_crop_debt' => $clearedDebt,
                'initial_shortage' => $initialShortage,
                'remaining_shortage' => $remainingShortage,
                'recovered_upkeep_per_hour' => $recoveredUpkeep,
                'killed' => array_values($casualties),
            ],
            'triggered_at' => now(),
        ]);

        $recipientIds = collect([
            $village->user_id,
            $village->watcher_user_id,
        ])->filter(fn ($id) => $id !== null)->unique()->all();

        foreach ($recipientIds as $recipientId) {
            ReportRecipient::query()->create([
                'report_id' => $report->getKey(),
                'recipient_id' => $recipientId,
                'visibility_scope' => 'personal',
                'status' => 'unread',
            ]);
        }
    }
}
