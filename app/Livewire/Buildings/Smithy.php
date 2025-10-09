<?php

namespace App\Livewire\Buildings;

class Smithy extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 13;
    }

    public static function buildingName(): string
    {
        return 'Smithy';
    }
}
