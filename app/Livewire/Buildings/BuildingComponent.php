<?php

declare(strict_types=1);

namespace App\Livewire\Buildings;

use App\Enums\BuildingType;
use Illuminate\Contracts\View\View;
use Livewire\Component;

abstract class BuildingComponent extends Component
{
    abstract public static function building(): BuildingType;

    public static function buildingId(): int
    {
        return static::building()->value;
    }

    public static function buildingName(): string
    {
        return static::building()->label();
    }

    public function render(): View
    {
        return view('livewire.buildings.building', [
            'building_id' => static::buildingId(),
            'building_name' => static::buildingName(),
        ]);
    }
}
