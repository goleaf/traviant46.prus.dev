<?php

namespace App\Livewire\Buildings;

class RallyPoint extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 16;
    }

    public static function buildingName(): string
    {
        return 'Rally Point';
    }
}
