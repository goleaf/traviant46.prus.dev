<?php

namespace App\Livewire\System;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;
use Livewire\Component;

class MaintenanceNotice extends Component
{
    public function render(): View
    {
        return view('livewire.system.maintenance-notice', [
            'startTime' => Config::get('game.start_time'),
        ]);
    }
}
