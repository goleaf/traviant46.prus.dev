<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BannedNotice extends Component
{
    public function render(): View
    {
        return view('livewire.account.banned-notice', [
            'user' => Auth::user(),
        ]);
    }
}
