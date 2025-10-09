<?php

namespace App\Livewire\Buildings;

class Woodcutter extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 1;
    }

    public static function buildingName(): string
    {
        return 'Woodcutter';
    }
}
