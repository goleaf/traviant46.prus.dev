<?php

namespace App\Livewire\Buildings;

class TownHall extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 24;
    }

    public static function buildingName(): string
    {
        return 'Town Hall';
    }
}
