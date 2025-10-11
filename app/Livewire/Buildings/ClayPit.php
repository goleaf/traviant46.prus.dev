<?php

declare(strict_types=1);

namespace App\Livewire\Buildings;

use App\Enums\BuildingType;

class ClayPit extends BuildingComponent
{
    public static function building(): BuildingType
    {
        return BuildingType::CLAY_PIT;
    }
}
