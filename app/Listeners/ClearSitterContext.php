<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Auth\UserLoggedOut;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class ClearSitterContext
{
    public function handle(Logout $event): void
    {
        if (app()->bound('session')) {
            session()->forget([
                'auth.acting_as_sitter',
                'auth.sitter_id',
            ]);
        }

        $request = request();
        $ipAddress = $request instanceof Request ? (string) $request->ip() : '';
        $userAgent = $request instanceof Request ? $request->userAgent() : null;

        event(new UserLoggedOut(
            $event->user instanceof User ? $event->user : null,
            (string) $event->guard,
            $ipAddress,
            $userAgent,
        ));
    }
}
