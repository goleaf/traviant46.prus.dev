<?php

namespace App\Livewire\Hero;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class HeroAdventures extends Component
{
    public function render(): View
    {
        return view('livewire.hero.hero-adventures');
    }
}
