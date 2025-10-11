<?php

namespace App\Livewire\Village;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Livewire component that replaces the legacy Dorf2Ctrl controller.
 *
 * It exposes the currently selected building slot and a placeholder payload for
 * the queued construction tasks. The component is intentionally lightweight so
 * that the remaining imperative logic from the controller can be ported in
 * smaller follow-up steps without blocking the Livewire migration.
 */
class BuildingView extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $building = [
        'slot' => null,
        'name' => null,
        'level' => 0,
        'queue' => [],
    ];

    public function render(): View
    {
        return view('livewire.village.building-view', [
            'building' => $this->building,
        ]);
    }
}
