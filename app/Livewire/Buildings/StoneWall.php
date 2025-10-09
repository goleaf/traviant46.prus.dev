<?php

namespace App\Livewire\Buildings;

class StoneWall extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 42;
    }

    public static function buildingName(): string
    {
        return 'Stone Wall';
    }
}
