<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\AuthEventType;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use App\Models\AuthEvent;
use App\Models\User;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\RequestPasswordResetLinkViewResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController as BasePasswordResetLinkController;

class PasswordResetLinkController extends BasePasswordResetLinkController
{
    public function create(Request $request): RequestPasswordResetLinkViewResponse
    {
        return parent::create($request);
    }

    public function store(PasswordResetLinkRequest $request): Responsable
    {
        $this->ensureIsNotRateLimited($request);

        $status = $this->broker()->sendResetLink(
            $request->only(Fortify::email()),
        );

        $this->recordAuditEvent($request, $status);

        return $status === Password::RESET_LINK_SENT
            ? app(SuccessfulPasswordResetLinkRequestResponse::class, ['status' => $status])
            : app(FailedPasswordResetLinkRequestResponse::class, ['status' => $status]);
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        $settings = config('security.rate_limits.password_reset', []);
        $maxAttempts = max(1, (int) ($settings['max_attempts'] ?? 5));
        $decayMinutes = max(1, (int) ($settings['decay_minutes'] ?? 10));
        $decaySeconds = $decayMinutes * 60;

        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                Fortify::email() => __('passwords.throttle', [
                    'seconds' => RateLimiter::availableIn($key),
                ]),
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }

    protected function throttleKey(Request $request): string
    {
        $email = Str::lower((string) $request->input(Fortify::email(), ''));

        return $email.'|'.$request->ip();
    }

    protected function recordAuditEvent(Request $request, string $status): void
    {
        $email = (string) $request->input(Fortify::email());

        $user = null;

        if ($status === Password::RESET_LINK_SENT) {
            $user = User::query()
                ->where(Fortify::email(), $email)
                ->first();
        }

        AuthEvent::query()->create([
            'user_id' => $user?->getKey(),
            'event_type' => AuthEventType::PasswordResetRequested,
            'identifier' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta' => [
                'status' => $status,
            ],
            'occurred_at' => now(),
        ]);
    }
}
