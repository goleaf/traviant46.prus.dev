<?php

namespace App\Livewire\Buildings;

class Residence extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 25;
    }

    public static function buildingName(): string
    {
        return 'Residence';
    }
}
