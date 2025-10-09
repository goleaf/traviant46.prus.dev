<?php

namespace App\Livewire\Buildings;

class Treasury extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 27;
    }

    public static function buildingName(): string
    {
        return 'Treasury';
    }
}
