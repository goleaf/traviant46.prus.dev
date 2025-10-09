<?php

namespace App\Livewire\Village;

use Livewire\Component;

class BuildingQueue extends Component
{
    public array $queue = [];

    public function mount(array $queue): void
    {
        $this->queue = $queue;
    }

    public function render()
    {
        return view('livewire.village.building-queue');
    }
}
