<?php

namespace App\Livewire\Buildings;

class MainBuilding extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 15;
    }

    public static function buildingName(): string
    {
        return 'Main Building';
    }
}
