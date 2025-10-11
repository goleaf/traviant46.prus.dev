<?php

declare(strict_types=1);

namespace App\Livewire\Buildings;

use App\Enums\BuildingType;

class Smithy extends BuildingComponent
{
    public static function building(): BuildingType
    {
        return BuildingType::SMITHY;
    }
}
