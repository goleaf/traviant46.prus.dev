<?php

namespace App\Livewire\Buildings;

class Barracks extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 19;
    }

    public static function buildingName(): string
    {
        return 'Barracks';
    }
}
