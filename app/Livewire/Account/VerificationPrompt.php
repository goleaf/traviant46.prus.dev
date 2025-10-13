<?php

namespace App\Livewire\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VerificationPrompt extends Component
{
    public bool $resent = false;

    public function mount(): void
    {
        $this->resent = session('status') === 'verification-link-sent';
    }

    public function render(): View
    {
        return view('livewire.account.verification-prompt', [
            'user' => Auth::user(),
            'resent' => $this->resent,
        ]);
    }
}
