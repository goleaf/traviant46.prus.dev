<?php

namespace App\Livewire\Buildings;

class Cropland extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 4;
    }

    public static function buildingName(): string
    {
        return 'Cropland';
    }
}
