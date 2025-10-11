<?php

namespace App\Livewire\Village;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Livewire replacement for the legacy Dorf1Ctrl controller.
 *
 * The component acts as the entry point for the village overview page and is
 * responsible for assembling the data that was previously prepared inside the
 * traditional controller. For now we only expose a lightweight data structure
 * that mirrors the essential pieces required by the new blade template. The
 * heavy lifting that used to live in the controller can be progressively moved
 * into dedicated services and injected here.
 */
class Overview extends Component
{
    /**
     * Basic state that can be hydrated from dedicated services later on.
     *
     * @var array<string, mixed>
     */
    public array $overview = [
        'villageName' => 'Unnamed Village',
        'population' => 0,
        'coordinates' => ['x' => 0, 'y' => 0],
        'production' => [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ],
    ];

    public function render(): View
    {
        return view('livewire.village.overview', [
            'overview' => $this->overview,
        ]);
    }
}
