<?php

namespace App\Livewire\Buildings;

class Embassy extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 18;
    }

    public static function buildingName(): string
    {
        return 'Embassy';
    }
}
