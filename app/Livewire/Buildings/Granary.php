<?php

namespace App\Livewire\Buildings;

class Granary extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 11;
    }

    public static function buildingName(): string
    {
        return 'Granary';
    }
}
