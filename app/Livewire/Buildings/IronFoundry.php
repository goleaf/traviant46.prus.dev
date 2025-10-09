<?php

namespace App\Livewire\Buildings;

class IronFoundry extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 7;
    }

    public static function buildingName(): string
    {
        return 'Iron Foundry';
    }
}
