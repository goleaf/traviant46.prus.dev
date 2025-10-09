<?php

namespace App\Livewire\Buildings;

class EarthWall extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 32;
    }

    public static function buildingName(): string
    {
        return 'Earth Wall';
    }
}
