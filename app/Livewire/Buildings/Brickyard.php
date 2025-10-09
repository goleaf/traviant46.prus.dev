<?php

namespace App\Livewire\Buildings;

class Brickyard extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 6;
    }

    public static function buildingName(): string
    {
        return 'Brickyard';
    }
}
