<?php

namespace App\Http\Livewire;

use Livewire\Component;

class StockBar extends Component
{
    public bool $showStockbar = false;
    public array $stockBar = [];
    public string $bodyCssClass = '';

    public function mount(bool $showStockbar = false, array $stockBar = [], string $bodyCssClass = ''): void
    {
        $this->showStockbar = $showStockbar;
        $this->stockBar = $stockBar;
        $this->bodyCssClass = $bodyCssClass;
    }

    public function render()
    {
        return view('livewire.stock-bar');
    }
}
