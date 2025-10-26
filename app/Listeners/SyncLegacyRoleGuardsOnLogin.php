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

        if ($event->guard !== 'admin') {
            $this->syncGuard('admin', $user->isAdmin(), $user);
        }

        if ($event->guard !== 'multihunter') {
            $this->syncGuard('multihunter', $user->isMultihunter(), $user);
        }
    }

    private function syncGuard(string $guard, bool $shouldBeLoggedIn, User $user): void
    {
        $authGuard = Auth::guard($guard);

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
}
