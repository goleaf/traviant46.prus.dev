<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Models\Game\ReinforcementGarrison;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Calculate troop upkeep pressure for a village across owned units and reinforcements.
 */
class VillageUpkeepService
{
    private const RESOURCE_KEY = 'crop';

    private const SECONDS_PER_HOUR = 3600;

    public function calculate(Village $village): array
    {
        if (! Schema::hasTable('village_units') || ! Schema::hasTable('reinforcement_garrisons')) {
            // Default to zero upkeep when unit tables are unavailable (for example during lightweight testing schemas).
            return $this->buildEmptyPayload($village);
        }

        $village->loadMissing([
            'units.unitType',
            'stationedReinforcements' => static fn ($query): mixed => $query->active(),
        ]);

        $ownUpkeep = $this->sumUnitUpkeep($village->units);
        $reinforcementUpkeep = $this->sumReinforcementUpkeep($village->stationedReinforcements);

        $totalPerHour = $ownUpkeep + $reinforcementUpkeep;
        $tickInterval = (float) config('game.tick_interval_seconds', 60);
        $perTick = $totalPerHour * ($tickInterval / self::SECONDS_PER_HOUR);

        return [
            'village_id' => (int) $village->getKey(),
            'resource' => self::RESOURCE_KEY,
            'per_tick' => $perTick,
            'per_hour' => $totalPerHour,
            'sources' => [
                'own_units' => $ownUpkeep,
                'reinforcements' => $reinforcementUpkeep,
            ],
        ];
    }

    /**
     * Provide a zeroed upkeep payload when unit tables are unavailable.
     */
    private function buildEmptyPayload(Village $village): array
    {
        return [
            'village_id' => (int) $village->getKey(),
            'resource' => self::RESOURCE_KEY,
            'per_tick' => 0.0,
            'per_hour' => 0.0,
            'sources' => [
                'own_units' => 0.0,
                'reinforcements' => 0.0,
            ],
        ];
    }

    /**
     * @param Collection<int, VillageUnit> $units
     */
    private function sumUnitUpkeep(Collection $units): float
    {
        return $units->reduce(
            static function (float $carry, VillageUnit $unit): float {
                $upkeep = (int) ($unit->unitType?->upkeep ?? 0);

                return $carry + ((int) $unit->quantity * $upkeep);
            },
            0.0,
        );
    }

    /**
     * @param Collection<int, ReinforcementGarrison> $reinforcements
     */
    private function sumReinforcementUpkeep(Collection $reinforcements): float
    {
        return $reinforcements
            ->filter(static fn (ReinforcementGarrison $garrison): bool => $garrison->is_active)
            ->sum(static fn (ReinforcementGarrison $garrison): int => (int) $garrison->upkeep);
    }
}
