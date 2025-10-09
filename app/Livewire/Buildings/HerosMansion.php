<?php

namespace App\Livewire\Buildings;

class HerosMansion extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 37;
    }

    public static function buildingName(): string
    {
        return 'Hero\'s Mansion';
    }
}
