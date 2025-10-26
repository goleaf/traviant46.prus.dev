<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;

class SyncLegacyRoleGuardsOnLogout
{
    public function handle(Logout $event): void
    {
        if ($event->guard !== 'admin' && Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
        }

        if ($event->guard !== 'multihunter' && Auth::guard('multihunter')->check()) {
            Auth::guard('multihunter')->logout();
        }
    }
}
