<?php

namespace App\Livewire\Buildings;

class Palace extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 26;
    }

    public static function buildingName(): string
    {
        return 'Palace';
    }
}
