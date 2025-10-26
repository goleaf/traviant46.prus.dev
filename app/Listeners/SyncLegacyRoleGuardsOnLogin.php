<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;

class SyncLegacyRoleGuardsOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $preserveAdmin = $this->shouldPreserveAdminGuard();

        if ($event->guard !== 'admin') {
            $this->syncGuard('admin', $user->isAdmin(), $user, $preserveAdmin);
        }

        if ($event->guard !== 'multihunter') {
            $this->syncGuard('multihunter', $user->isMultihunter(), $user, false);
        }
    }

    private function syncGuard(string $guard, bool $shouldBeLoggedIn, User $user, bool $preserveAdmin): void
    {
        $authGuard = Auth::guard($guard);

        if ($guard === 'admin' && $preserveAdmin && ! $shouldBeLoggedIn) {
            return;
        }

        if ($shouldBeLoggedIn) {
            if (! $authGuard->check() || (int) $authGuard->id() !== (int) $user->getKey()) {
                $authGuard->login($user);
            }

            return;
        }

        if ($authGuard->check()) {
            $authGuard->logout();
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
