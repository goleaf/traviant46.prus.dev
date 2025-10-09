<?php

namespace App\Livewire\Buildings;

class Sawmill extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 5;
    }

    public static function buildingName(): string
    {
        return 'Sawmill';
    }
}
