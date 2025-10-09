<?php

namespace App\Livewire\Buildings;

class Waterworks extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 45;
    }

    public static function buildingName(): string
    {
        return 'Waterworks';
    }
}
