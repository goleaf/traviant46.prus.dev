<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Auth\LoginFailed;
use App\Events\Auth\LoginSucceeded;
use App\Events\Auth\UserLoggedOut;
use App\Jobs\SendAuthEventNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Config;

class DispatchAuthEventNotification implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function handle(LoginSucceeded|LoginFailed|UserLoggedOut $event): void
    {
        if (! Config::get('security.auth_events.enabled', false)) {
            return;
        }

        [$name, $payload] = $this->resolvePayload($event);

        if ($name === null) {
            return;
        }

        $dispatch = SendAuthEventNotification::dispatch($name, $payload);

        $queue = Config::get('security.auth_events.queue');

        if (is_string($queue) && $queue !== '') {
            $dispatch->onQueue($queue);
        }
    }

    /**
     * @return array{0: string|null, 1: array<string, mixed>}
     */
    private function resolvePayload(LoginSucceeded|LoginFailed|UserLoggedOut $event): array
    {
        return match (true) {
            $event instanceof LoginSucceeded => [
                'login.succeeded',
                [
                    'user' => $this->userContext($event->user),
                    'guard' => $event->guard,
                    'ip_address' => $event->ipAddress,
                    'user_agent' => $event->userAgent,
                    'via_sitter' => $event->viaSitter,
                    'acting_sitter_id' => $event->actingSitterId,
                ],
            ],
            $event instanceof LoginFailed => [
                'login.failed',
                [
                    'user' => $this->userContext($event->user),
                    'identifier' => $event->identifier,
                    'guard' => $event->guard,
                    'ip_address' => $event->ipAddress,
                    'user_agent' => $event->userAgent,
                    'attempts' => $event->attempts,
                    'max_attempts' => $event->maxAttempts,
                    'cooldown_seconds' => max(0, $event->secondsUntilNextAttempt),
                ],
            ],
            $event instanceof UserLoggedOut => [
                'logout.completed',
                [
                    'user' => $this->userContext($event->user),
                    'guard' => $event->guard,
                    'ip_address' => $event->ipAddress,
                    'user_agent' => $event->userAgent,
                ],
            ],
            default => [null, []],
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function userContext(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        return array_filter([
            'id' => $user->getKey(),
            'username' => $user->username,
            'email' => $user->email,
            'legacy_id' => $user->legacy_uid,
            'is_admin' => $user->isAdmin(),
            'is_multihunter' => $user->isMultihunter(),
        ], static fn ($value) => $value !== null);
    }
}
