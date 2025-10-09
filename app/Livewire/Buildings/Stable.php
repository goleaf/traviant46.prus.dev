<?php

namespace App\Livewire\Buildings;

class Stable extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 20;
    }

    public static function buildingName(): string
    {
        return 'Stable';
    }
}
