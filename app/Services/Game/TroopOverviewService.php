<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Enums\Game\UnitTrainingBatchStatus;
use App\Models\Game\ReinforcementGarrison;
use App\Models\Game\TroopType;
use App\Models\Game\UnitTrainingBatch;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

class TroopOverviewService
{
    /**
     * @var array<int, array<int, TroopType>>
     */
    private array $tribeCatalog = [];

    public function __construct(private readonly TroopTrainingService $trainingService) {}

    /**
     * Summarise owned and stationed troops for the village.
     *
     * @return array{
     *     owned: list<array{
     *         unit_type_id: int,
     *         name: string,
     *         code: ?string,
     *         quantity: int,
     *         upkeep: ?int,
     *         total_upkeep: ?int
     *     }>,
     *     reinforcements: list<array{
     *         id: int,
     *         owner: string|null,
     *         home_village: string|null,
     *         tribe: int|null,
     *         units: list<array{
     *             slot: string,
     *             unit_type_id: int|null,
     *             name: string,
     *             code: string|null,
     *             quantity: int
     *         }>,
     *         total: int,
     *         deployed_at: ?string
     *     }>,
     *     totals: array{owned: int, reinforcements: int, overall: int}
     * }
     */
    public function garrisonSummary(Village $village): array
    {
        $village->loadMissing([
            'units' => static function (Relation $relation): void {
                $relation->getQuery()->orderBy('unit_type_id');
            },
            'units.unitType',
            'stationedReinforcements' => static function (Relation $relation): void {
                $relation->getQuery()->active()->orderBy('id');
            },
            'stationedReinforcements.owner',
            'stationedReinforcements.homeVillage',
        ]);

        $ownedUnits = $village->units
            ->map(fn ($unit) => $this->mapOwnedUnit($unit))
            ->filter(fn ($unit) => $unit['quantity'] > 0)
            ->values();

        $reinforcements = $village->stationedReinforcements
            ->map(function (ReinforcementGarrison $garrison) {
                return $this->mapReinforcement($garrison);
            })
            ->filter(fn (array $garrison) => $garrison['total'] > 0)
            ->values();

        $ownedTotal = (int) $ownedUnits->sum('quantity');
        $reinforcementTotal = (int) $reinforcements->sum('total');

        return [
            'owned' => $ownedUnits->all(),
            'reinforcements' => $reinforcements->all(),
            'totals' => [
                'owned' => $ownedTotal,
                'reinforcements' => $reinforcementTotal,
                'overall' => $ownedTotal + $reinforcementTotal,
            ],
        ];
    }

    /**
     * Build a queue snapshot for current and future training batches.
     *
     * @return array{
     *     village_id: int,
     *     entries: list<array{
     *         id: int,
     *         unit_type_id: int,
     *         unit_name: string,
     *         quantity: int,
     *         queue_position: int,
     *         status: string,
     *         is_active: bool,
     *         starts_at: ?string,
     *         completes_at: ?string,
     *         remaining_seconds: int,
     *         per_unit_seconds: int|null,
     *         training_building: ?string
     *     }>,
     *     active_entry: array<string, mixed>|null,
     *     next_completion_at: ?string
     * }
     */
    public function trainingQueue(Village $village): array
    {
        $now = Carbon::now();

        $village->loadMissing([
            'trainingBatches' => static function (Relation $relation): void {
                $relation->getQuery()
                    ->orderBy('queue_position')
                    ->orderBy('id');
            },
            'trainingBatches.unitType',
        ]);

        $entries = $village->trainingBatches
            ->map(fn (UnitTrainingBatch $batch, int $index) => $this->mapTrainingBatch($batch, $index, $now))
            ->values();

        $activeEntry = $entries->firstWhere('is_active', true);
        $nextCompletion = $entries
            ->pluck('completes_at')
            ->filter()
            ->sort()
            ->first();

        return [
            'village_id' => (int) $village->getKey(),
            'entries' => $entries->all(),
            'active_entry' => $activeEntry ?: null,
            'next_completion_at' => $nextCompletion,
        ];
    }

    /**
     * Determine the troop types available for training in this village.
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     code: string,
     *     tribe: int,
     *     upkeep: int,
     *     carry: int,
     *     train_cost: array<string, int>
     * }>
     */
    public function availableUnits(Village $village): array
    {
        $village->loadMissing([
            'units.unitType',
            'trainingBatches',
            'owner',
        ]);

        $unitTypeIds = collect();

        if ($village->relationLoaded('units')) {
            $unitTypeIds = $unitTypeIds->merge(
                $village->units->pluck('unit_type_id'),
            );
        }

        if ($village->relationLoaded('trainingBatches')) {
            $unitTypeIds = $unitTypeIds->merge(
                $village->trainingBatches->pluck('unit_type_id'),
            );
        }

        $unitTypeIds = $unitTypeIds->filter()->unique();

        if ($unitTypeIds->isEmpty()) {
            $ownerTribe = $this->resolveOwnerTribe($village);

            $catalogQuery = TroopType::query()
                ->when($ownerTribe !== null, fn ($query) => $query->where('tribe', $ownerTribe))
                ->orderBy('tribe')
                ->orderBy('id');

            $unitTypes = $catalogQuery->limit(10)->get();
        } else {
            $unitTypes = TroopType::query()
                ->whereIn('id', $unitTypeIds->all())
                ->orderBy('tribe')
                ->orderBy('id')
                ->get();
        }

        return $unitTypes
            ->mapWithKeys(static function (TroopType $type): array {
                return [
                    (int) $type->getKey() => [
                        'id' => (int) $type->getKey(),
                        'name' => $type->name,
                        'code' => $type->code,
                        'tribe' => (int) $type->tribe,
                        'upkeep' => (int) $type->upkeep,
                        'carry' => (int) $type->carry,
                        'train_cost' => array_map('intval', (array) $type->train_cost),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function trainingBuildingOptions(): array
    {
        return $this->trainingService->trainingBuildingOptions();
    }

    /**
     * @return array{unit_type_id: int, name: string, code: ?string, quantity: int, upkeep: ?int}
     */
    private function mapOwnedUnit(VillageUnit $unit): array
    {
        $type = $unit->unitType;

        $quantity = (int) $unit->quantity;
        $perUnitUpkeep = $type?->upkeep !== null ? (int) $type->upkeep : null;

        return [
            'unit_type_id' => (int) $unit->unit_type_id,
            'name' => $type?->name ?? __('Unit :id', ['id' => $unit->unit_type_id]),
            'code' => $type?->code,
            'quantity' => $quantity,
            'upkeep' => $perUnitUpkeep,
            'total_upkeep' => $perUnitUpkeep !== null ? $perUnitUpkeep * $quantity : null,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     owner: string|null,
     *     home_village: string|null,
     *     tribe: int|null,
     *     units: list<array{slot: string, unit_type_id: int|null, name: string, code: string|null, quantity: int}>,
     *     total: int,
     *     deployed_at: ?string
     * }
     */
    private function mapReinforcement(ReinforcementGarrison $garrison): array
    {
        $tribe = $this->resolveGarrisonTribe($garrison);
        $units = collect((array) $garrison->unit_composition)
            ->map(fn (int|string $quantity, string $slot) => $this->mapReinforcementUnit($tribe, (int) $quantity, $slot))
            ->filter(fn (array $unit) => $unit['quantity'] > 0)
            ->values();

        return [
            'id' => (int) $garrison->getKey(),
            'owner' => $garrison->owner?->username,
            'home_village' => $garrison->homeVillage?->name,
            'tribe' => $tribe,
            'units' => $units->all(),
            'total' => (int) $units->sum('quantity'),
            'deployed_at' => $garrison->deployed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{slot: string, unit_type_id: int|null, name: string, code: string|null, quantity: int}
     */
    private function mapReinforcementUnit(?int $tribe, int $quantity, string $slot): array
    {
        $slotNumber = $this->resolveSlotNumber($slot);
        $type = ($tribe !== null && $slotNumber !== null)
            ? $this->resolveTroopTypeForSlot($tribe, $slotNumber)
            : null;

        $label = $type?->name ?? $this->fallbackSlotLabel($slotNumber ?? $slot);

        return [
            'slot' => $slot,
            'unit_type_id' => $type?->getKey(),
            'name' => $label,
            'code' => $type?->code,
            'quantity' => max(0, $quantity),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     unit_type_id: int,
     *     unit_name: string,
     *     quantity: int,
     *     queue_position: int,
     *     status: string,
     *     is_active: bool,
     *     starts_at: ?string,
     *     completes_at: ?string,
     *     remaining_seconds: int,
     *     per_unit_seconds: int|null,
     *     training_building: ?string
     * }
     */
    private function mapTrainingBatch(UnitTrainingBatch $batch, int $index, Carbon $now): array
    {
        $queuePosition = $batch->queue_position ?? ($index + 1);
        $status = $batch->status instanceof UnitTrainingBatchStatus
            ? $batch->status->value
            : (string) $batch->status;

        $isActive = match ($batch->status) {
            UnitTrainingBatchStatus::Processing => true,
            UnitTrainingBatchStatus::Pending => (int) $queuePosition === 1,
            default => false,
        };

        $remainingSeconds = 0;
        if ($batch->completes_at !== null) {
            $remainingSeconds = max(0, (int) $now->diffInSeconds($batch->completes_at, false));
        }

        $calc = (array) ($batch->metadata['calculation'] ?? []);
        $perUnitSeconds = isset($calc['per_unit_seconds'])
            ? (int) $calc['per_unit_seconds']
            : null;

        return [
            'id' => (int) $batch->getKey(),
            'unit_type_id' => (int) $batch->unit_type_id,
            'unit_name' => $batch->unitType?->name ?? __('Unit :id', ['id' => $batch->unit_type_id]),
            'quantity' => (int) $batch->quantity,
            'queue_position' => (int) $queuePosition,
            'status' => $status,
            'is_active' => $isActive,
            'starts_at' => $batch->starts_at?->toIso8601String(),
            'completes_at' => $batch->completes_at?->toIso8601String(),
            'remaining_seconds' => $remainingSeconds,
            'per_unit_seconds' => $perUnitSeconds,
            'training_building' => $batch->training_building,
        ];
    }

    private function resolveTroopTypeForSlot(int $tribe, int $slot): ?TroopType
    {
        if ($tribe <= 0 || $slot <= 0) {
            return null;
        }

        if (! isset($this->tribeCatalog[$tribe])) {
            $this->tribeCatalog[$tribe] = TroopType::query()
                ->where('tribe', $tribe)
                ->orderBy('id')
                ->get()
                ->values()
                ->mapWithKeys(fn (TroopType $type, int $index) => [$index + 1 => $type])
                ->all();
        }

        return $this->tribeCatalog[$tribe][$slot] ?? null;
    }

    private function resolveSlotNumber(string $slot): ?int
    {
        if (preg_match('/^u(?P<slot>\d{1,2})$/', $slot, $matches) !== 1) {
            return null;
        }

        return (int) ($matches['slot'] ?? 0);
    }

    private function fallbackSlotLabel(int|string $slot): string
    {
        if ((int) $slot === 11) {
            return __('Hero');
        }

        return __('Slot :slot', ['slot' => $slot]);
    }

    private function resolveGarrisonTribe(ReinforcementGarrison $garrison): ?int
    {
        $metadataRace = $garrison->metadata['race'] ?? null;

        if (is_numeric($metadataRace)) {
            return (int) $metadataRace;
        }

        $ownerRace = $garrison->owner?->getAttribute('tribe')
            ?? $garrison->owner?->getAttribute('race');

        return is_numeric($ownerRace) ? (int) $ownerRace : null;
    }

    private function resolveOwnerTribe(Village $village): ?int
    {
        $owner = $village->owner;

        if ($owner === null) {
            return null;
        }

        $tribe = $owner->getAttribute('tribe')
            ?? $owner->getAttribute('race');

        return is_numeric($tribe) ? (int) $tribe : null;
    }
}
