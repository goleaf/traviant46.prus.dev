<?php

declare(strict_types=1);

namespace App\Repositories\Game;

use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use Illuminate\Support\Collection;

class TroopRepository
{
    /**
     * @return Collection<int, VillageUnit>
     */
    public function unitsWithUpkeep(Village $village): Collection
    {
        $village->loadMissing('units.unitType');

        return $village->units
            ->filter(static function (VillageUnit $unit): bool {
                return ($unit->quantity ?? 0) > 0 && ($unit->unitType?->upkeep ?? 0) > 0;
            })
            ->values();
    }

    public function reduceUnit(VillageUnit $unit, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $unit->quantity = max(0, ($unit->quantity ?? 0) - $amount);
        $unit->save();
    }
}
