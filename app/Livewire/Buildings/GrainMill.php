<?php

namespace App\Livewire\Buildings;

class GrainMill extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 8;
    }

    public static function buildingName(): string
    {
        return 'Grain Mill';
    }
}
