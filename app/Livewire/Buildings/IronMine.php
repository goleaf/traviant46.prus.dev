<?php

namespace App\Livewire\Buildings;

class IronMine extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 3;
    }

    public static function buildingName(): string
    {
        return 'Iron Mine';
    }
}
