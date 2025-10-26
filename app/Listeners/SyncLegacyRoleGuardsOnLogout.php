<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;

class SyncLegacyRoleGuardsOnLogout
{
    public function handle(Logout $event): void
    {
        $preserveAdmin = $this->shouldPreserveAdminGuard();

        if ($event->guard !== 'admin' && Auth::guard('admin')->check() && ! $preserveAdmin) {
            Auth::guard('admin')->logout();
        }

        if ($event->guard !== 'multihunter' && Auth::guard('multihunter')->check()) {
            Auth::guard('multihunter')->logout();
        }
    }

    private function shouldPreserveAdminGuard(): bool
    {
        $session = request()?->session();

        if ($session === null) {
            return false;
        }

        if (! $session->get('impersonation.active', false)) {
            return false;
        }

        return $session->has('impersonation.admin_user_id');
    }
}
