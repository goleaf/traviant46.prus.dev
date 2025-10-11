<?php

declare(strict_types=1);

namespace App\Livewire\Buildings;

use App\Enums\BuildingType;

class StonemasonsLodge extends BuildingComponent
{
    public static function building(): BuildingType
    {
        return BuildingType::STONEMASONS_LODGE;
    }
}
