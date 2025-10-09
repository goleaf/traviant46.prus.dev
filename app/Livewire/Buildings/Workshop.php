<?php

namespace App\Livewire\Buildings;

class Workshop extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 21;
    }

    public static function buildingName(): string
    {
        return 'Workshop';
    }
}
