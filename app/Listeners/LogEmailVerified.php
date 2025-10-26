<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\AuthEventType;
use App\Models\AuthEvent;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class LogEmailVerified
{
    public function handle(Verified $event): void
    {
        /** @var Request|null $request */
        $request = request();

        $email = method_exists($event->user, 'getEmailForVerification')
            ? $event->user->getEmailForVerification()
            : ($event->user->email ?? null);

        AuthEvent::query()->create([
            'user_id' => method_exists($event->user, 'getAuthIdentifier') ? $event->user->getAuthIdentifier() : null,
            'event_type' => AuthEventType::EmailVerified,
            'identifier' => $email,
            'ip_address' => $request?->ip(),
            'user_agent' => (string) ($request?->userAgent()),
            'meta' => [
                'method' => 'link',
            ],
            'occurred_at' => now(),
        ]);
    }
}
