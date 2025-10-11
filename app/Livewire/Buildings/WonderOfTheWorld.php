<?php

declare(strict_types=1);

namespace App\Livewire\Buildings;

use App\Enums\BuildingType;

class WonderOfTheWorld extends BuildingComponent
{
    public static function building(): BuildingType
    {
        return BuildingType::WONDER_OF_THE_WORLD;
    }
}
