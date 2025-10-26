<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\Security\UserSessionManager;
use Illuminate\Auth\Events\Login;

class RegisterUserSession
{
    public function __construct(
        protected UserSessionManager $sessionManager,
    ) {}

    public function handle(Login $event): void
    {
        $user = $event->user;
        $request = request();

        if (! $user instanceof User || ! $request->hasSession()) {
            return;
        }

        $absoluteLifetime = (int) config('session.absolute_lifetime', 0);

        $this->sessionManager->register($user, $request, $absoluteLifetime);
    }
}
