<?php

namespace App\Livewire\Village;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Livewire representation of the Dorf3Ctrl controller.
 *
 * The component renders the account wide village list. The initial state is a
 * simple array of villages which can later be populated from a repository or
 * query object once the persistence layer is migrated.
 */
class VillageList extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $villages = [];

    public function render(): View
    {
        return view('livewire.village.village-list', [
            'villages' => $this->villages,
        ]);
    }
}
