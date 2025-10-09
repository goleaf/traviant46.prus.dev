<?php

namespace App\Livewire\Buildings;

class CityWall extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 31;
    }

    public static function buildingName(): string
    {
        return 'City Wall';
    }
}
