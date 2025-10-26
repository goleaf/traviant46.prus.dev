<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Security\UserSessionManager;
use Illuminate\Auth\Events\Logout;

class RemoveUserSession
{
    public function __construct(
        protected UserSessionManager $sessionManager,
    ) {}

    public function handle(Logout $event): void
    {
        $request = request();

        if (! $request->hasSession()) {
            return;
        }

        $this->sessionManager->forget($request->session()->getId());
    }
}
