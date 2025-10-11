<?php

declare(strict_types=1);

namespace App\Livewire\Buildings;

use App\Enums\BuildingType;

class HorseDrinkingTrough extends BuildingComponent
{
    public static function building(): BuildingType
    {
        return BuildingType::HORSE_DRINKING_TROUGH;
    }
}
