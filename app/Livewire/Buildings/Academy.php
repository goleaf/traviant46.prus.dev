<?php

namespace App\Livewire\Buildings;

class Academy extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 22;
    }

    public static function buildingName(): string
    {
        return 'Academy';
    }
}
