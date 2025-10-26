<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Auth\LoginFailed as LoginFailedEvent;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class LogFailedLoginAttempt
{
    public function __construct(private readonly LoginRateLimiter $limiter) {}

    public function handle(Failed $event): void
    {
        $request = request();

        if (! $request instanceof Request) {
            return;
        }

        $identifierField = Fortify::username();
        $identifier = (string) ($event->credentials[$identifierField] ?? $request->input($identifierField, ''));

        if ($identifier === '' && $request->filled('email')) {
            $identifier = (string) $request->input('email');
        }

        $attemptsBefore = (int) $this->limiter->attempts($request);
        $attemptsAfter = $attemptsBefore + 1;

        $loginRateLimit = config('security.rate_limits.login', []);
        $maxAttempts = max(1, (int) ($loginRateLimit['max_attempts'] ?? 5));
        $decayMinutes = max(1, (int) ($loginRateLimit['decay_minutes'] ?? 1));
        $decaySeconds = max(60, $decayMinutes * 60);

        $lockoutSeconds = (int) $this->limiter->availableIn($request);

        if ($attemptsAfter >= $maxAttempts && $lockoutSeconds <= 0) {
            $lockoutSeconds = $decaySeconds;
        }

        event(new LoginFailedEvent(
            $event->user,
            $identifier,
            (string) $event->guard,
            (string) $request->ip(),
            $request->userAgent(),
            $attemptsAfter,
            $maxAttempts,
            $lockoutSeconds,
        ));

        $identifierHash = $identifier === '' ? null : hash('sha256', Str::lower($identifier));
        $identifierType = $identifier === '' ? null : (filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username');

        Log::channel('structured')->warning('auth.login.failed', [
            'user_id' => optional($event->user)->getKey(),
            'guard' => (string) $event->guard,
            'identifier_hash' => $identifierHash,
            'identifier_type' => $identifierType,
            'ip_address' => (string) $request->ip(),
            'user_agent' => $request->userAgent(),
            'attempts' => $attemptsAfter,
            'max_attempts' => $maxAttempts,
            'lockout_seconds' => max(0, $lockoutSeconds),
        ]);
    }
}
