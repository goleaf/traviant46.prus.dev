<?php

namespace App\Livewire\Buildings;

class HorseDrinkingTrough extends BuildingComponent
{
    public static function buildingId(): int
    {
        return 41;
    }

    public static function buildingName(): string
    {
        return 'Horse Drinking Trough';
    }
}
