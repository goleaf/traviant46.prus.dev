<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Security\DeviceFingerprintService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter as BaseLoginRateLimiter;

class LoginRateLimiter extends BaseLoginRateLimiter
{
    public function __construct(
        RateLimiter $limiter,
        protected DeviceFingerprintService $fingerprint,
        protected AuthLookupCache $lookupCache
    ) {
        parent::__construct($limiter);
    }

    public function attempts(Request $request)
    {
        return $this->limiter->attempts($this->identifierKey($request));
    }

    public function tooManyAttempts(Request $request)
    {
        if ($this->limiter->tooManyAttempts($this->identifierKey($request), $this->maxAttempts())) {
            return true;
        }

        $ipKey = $this->ipKey($request);

        if ($ipKey !== null && $this->limiter->tooManyAttempts($ipKey, $this->ipMaxAttempts())) {
            return true;
        }

        $deviceKey = $this->deviceKey($request);

        if ($deviceKey !== null && $this->limiter->tooManyAttempts($deviceKey, $this->deviceMaxAttempts())) {
            return true;
        }

        $userKey = $this->userKey($request);

        if ($userKey !== null && $this->limiter->tooManyAttempts($userKey, $this->userMaxAttempts())) {
            return true;
        }

        return false;
    }

    public function increment(Request $request)
    {
        $this->limiter->hit($this->identifierKey($request), $this->decaySeconds());

        if (($ipKey = $this->ipKey($request)) !== null) {
            $this->limiter->hit($ipKey, $this->ipDecaySeconds());
        }

        if (($deviceKey = $this->deviceKey($request)) !== null) {
            $this->limiter->hit($deviceKey, $this->deviceDecaySeconds());
        }

        if (($userKey = $this->userKey($request)) !== null) {
            $this->limiter->hit($userKey, $this->userDecaySeconds());
        }
    }

    public function availableIn(Request $request)
    {
        $waits = [$this->limiter->availableIn($this->identifierKey($request))];

        if (($ipKey = $this->ipKey($request)) !== null) {
            $waits[] = $this->limiter->availableIn($ipKey);
        }

        if (($deviceKey = $this->deviceKey($request)) !== null) {
            $waits[] = $this->limiter->availableIn($deviceKey);
        }

        if (($userKey = $this->userKey($request)) !== null) {
            $waits[] = $this->limiter->availableIn($userKey);
        }

        return max($waits);
    }

    public function clear(Request $request)
    {
        $this->limiter->clear($this->identifierKey($request));

        if (($ipKey = $this->ipKey($request)) !== null) {
            $this->limiter->clear($ipKey);
        }

        if (($deviceKey = $this->deviceKey($request)) !== null) {
            $this->limiter->clear($deviceKey);
        }

        if (($userKey = $this->userKey($request)) !== null) {
            $this->limiter->clear($userKey);
        }
    }

    protected function throttleKey(Request $request)
    {
        return $this->identifierKey($request);
    }

    private function identifierKey(Request $request): string
    {
        $identifier = $this->identifierFromRequest($request);
        $ip = (string) $request->ip();

        return $identifier.'|'.$ip;
    }

    private function ipKey(Request $request): ?string
    {
        if ($this->ipMaxAttempts() <= 0) {
            return null;
        }

        return 'ip:'.(string) $request->ip();
    }

    private function deviceKey(Request $request): ?string
    {
        if ($this->deviceMaxAttempts() <= 0) {
            return null;
        }

        return 'device:'.$this->fingerprint->hash($request);
    }

    private function userKey(Request $request): ?string
    {
        if ($this->userMaxAttempts() <= 0) {
            return null;
        }

        $userId = $this->resolveUserId($request);

        if ($userId === null) {
            return null;
        }

        return 'user:'.$userId;
    }

    private function identifierFromRequest(Request $request): string
    {
        return $this->normalizedIdentifier($request) ?? 'guest';
    }

    private function normalizedIdentifier(Request $request): ?string
    {
        $identifierField = Fortify::username();
        $identifier = (string) $request->input($identifierField, '');

        if ($identifier === '' && $request->filled('email')) {
            $identifier = (string) $request->input('email');
        }

        $normalized = Str::transliterate(Str::lower(trim($identifier)));

        return $normalized !== '' ? $normalized : null;
    }

    private function settings(): array
    {
        return config('security.rate_limits.login', []);
    }

    private function maxAttempts(): int
    {
        return max(1, (int) Arr::get($this->settings(), 'max_attempts', 5));
    }

    private function decaySeconds(): int
    {
        return max(60, (int) (Arr::get($this->settings(), 'decay_minutes', 1) * 60));
    }

    private function ipSettings(): array
    {
        $settings = Arr::get($this->settings(), 'per_ip');

        return is_array($settings) ? $settings : [];
    }

    private function ipMaxAttempts(): int
    {
        return max(0, (int) Arr::get($this->ipSettings(), 'max_attempts', 0));
    }

    private function ipDecaySeconds(): int
    {
        return max(60, (int) (Arr::get($this->ipSettings(), 'decay_minutes', Arr::get($this->settings(), 'decay_minutes', 1)) * 60));
    }

    private function deviceSettings(): array
    {
        $settings = Arr::get($this->settings(), 'per_device');

        return is_array($settings) ? $settings : [];
    }

    private function deviceMaxAttempts(): int
    {
        return max(0, (int) Arr::get($this->deviceSettings(), 'max_attempts', 0));
    }

    private function deviceDecaySeconds(): int
    {
        return max(60, (int) (Arr::get($this->deviceSettings(), 'decay_minutes', Arr::get($this->settings(), 'decay_minutes', 1)) * 60));
    }

    private function userSettings(): array
    {
        $settings = Arr::get($this->settings(), 'per_user');

        return is_array($settings) ? $settings : [];
    }

    private function userMaxAttempts(): int
    {
        return max(0, (int) Arr::get($this->userSettings(), 'max_attempts', 0));
    }

    private function userDecaySeconds(): int
    {
        $fallback = Arr::get($this->settings(), 'decay_minutes', 1);

        return max(60, (int) (Arr::get($this->userSettings(), 'decay_minutes', $fallback) * 60));
    }

    private function resolveUserId(Request $request): ?int
    {
        $normalized = $this->normalizedIdentifier($request);

        if ($normalized === null) {
            return null;
        }

        $user = $this->lookupCache->rememberUser($normalized, function () use ($normalized) {
            return User::query()
                ->where(function ($query) use ($normalized) {
                    $query->whereRaw('LOWER(username) = ?', [$normalized])
                        ->orWhereRaw('LOWER(email) = ?', [$normalized]);
                })
                ->first();
        });

        return $user?->getKey();
    }
}
