<?php

declare(strict_types=1);

namespace App\Livewire\Buildings;

use App\Enums\BuildingType;

class IronFoundry extends BuildingComponent
{
    public static function building(): BuildingType
    {
        return BuildingType::IRON_FOUNDRY;
    }
}
