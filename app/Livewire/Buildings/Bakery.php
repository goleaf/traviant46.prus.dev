<?php

namespace App\Livewire\Buildings;

class Bakery extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 9;
    }

    public static function buildingName(): string
    {
        return 'Bakery';
    }
}
