<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\AuthEventType;
use App\Models\AuthEvent;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;

class LogPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        /** @var Request|null $request */
        $request = request();

        $email = method_exists($event->user, 'getEmailForPasswordReset')
            ? $event->user->getEmailForPasswordReset()
            : ($event->user->email ?? null);

        AuthEvent::query()->create([
            'user_id' => method_exists($event->user, 'getAuthIdentifier') ? $event->user->getAuthIdentifier() : null,
            'event_type' => AuthEventType::PasswordResetCompleted,
            'identifier' => $email,
            'ip_address' => $request?->ip(),
            'user_agent' => (string) ($request?->userAgent()),
            'meta' => [
                'status' => 'completed',
            ],
            'occurred_at' => now(),
        ]);
    }
}
