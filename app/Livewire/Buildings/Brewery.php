<?php

namespace App\Livewire\Buildings;

class Brewery extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 35;
    }

    public static function buildingName(): string
    {
        return 'Brewery';
    }
}
