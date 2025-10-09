<?php

namespace App\Livewire\Buildings;

class ClayPit extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 2;
    }

    public static function buildingName(): string
    {
        return 'Clay Pit';
    }
}
