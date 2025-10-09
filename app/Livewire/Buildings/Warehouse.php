<?php

namespace App\Livewire\Buildings;

class Warehouse extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 10;
    }

    public static function buildingName(): string
    {
        return 'Warehouse';
    }
}
