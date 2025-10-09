<?php

namespace App\Livewire\Buildings;

class GreatStable extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 30;
    }

    public static function buildingName(): string
    {
        return 'Great Stable';
    }
}
