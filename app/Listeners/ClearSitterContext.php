<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class ClearSitterContext
{
    public function handle(Logout $event): void
    {
        if (! app()->bound('session')) {
            return;
        }

        session()->forget([
            'auth.acting_as_sitter',
            'auth.sitter_id',
        ]);
    }
}
