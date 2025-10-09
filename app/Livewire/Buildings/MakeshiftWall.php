<?php

namespace App\Livewire\Buildings;

class MakeshiftWall extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 43;
    }

    public static function buildingName(): string
    {
        return 'Makeshift Wall';
    }
}
