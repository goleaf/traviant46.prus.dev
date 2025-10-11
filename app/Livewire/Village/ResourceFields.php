<?php

namespace App\Livewire\Village;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Livewire port of the ProductionCtrl controller.
 *
 * The component presents the resource field grid along with aggregated
 * production numbers. Detailed calculations will be delegated to specialised
 * services in later phases; for the time being we expose a structure compatible
 * with the accompanying blade template.
 */
class ResourceFields extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $fields = [];

    /**
     * @var array<string, int>
     */
    public array $production = [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0,
    ];

    public function render(): View
    {
        return view('livewire.village.resource-fields', [
            'fields' => $this->fields,
            'production' => $this->production,
        ]);
    }
}
