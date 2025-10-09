<?php

namespace App\Livewire\Buildings;

class CommandCenter extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 44;
    }

    public static function buildingName(): string
    {
        return 'Command Center';
    }
}
