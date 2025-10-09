<?php

namespace App\Livewire\Buildings;

use Illuminate\Contracts\View\View;
use Livewire\Component;

abstract class BuildingComponent extends Component
{
    abstract public static function buildingId(): int;

    abstract public static function buildingName(): string;

    public function render(): View
    {
        return view('livewire.buildings.building', [
            'building_id' => static::buildingId(),
            'building_name' => static::buildingName(),
        ]);
    }
}
