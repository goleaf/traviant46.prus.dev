<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Auth\AuthLookupCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\NullLogger;

class FakeAuthLookupCache
{
    public function rememberUser(string $identifier, callable $resolver): ?User
    {
        return tap(new User, function (User $user) use ($identifier): void {
            $user->setAttribute('id', crc32($identifier));
        });
    }

    public function forgetUser(User $user): void {}

    public function rememberActivation(string $identifier, callable $resolver): ?object
    {
        return null;
    }

    public function forgetActivation($activation): void {}

    public function rememberSitterPermissions(int $accountId, callable $resolver)
    {
        return collect();
    }

    public function forgetSitterPermissions(int $accountId): void {}
}

if (! function_exists('loginThrottleKey')) {
    function loginThrottleKey(string $identifier, string $ip = '127.0.0.1'): string
    {
        $raw = Str::transliterate(Str::lower($identifier).'|'.$ip);

        return md5('login'.$raw);
    }
}

if (! function_exists('loginIpThrottleKey')) {
    function loginIpThrottleKey(string $ip = '127.0.0.1'): string
    {
        return md5('login'.'ip:'.$ip);
    }
}

if (! function_exists('loginUserThrottleKey')) {
    function loginUserThrottleKey(int $userId): string
    {
        return md5('login'.'user:'.$userId);
    }
}

beforeEach(function (): void {
    Log::swap(new NullLogger);

    app()->instance(AuthLookupCache::class, new FakeAuthLookupCache);
});

dataset('login-throttle-requests', [
    'first ip' => ['ip' => '127.0.0.1'],
    'second ip' => ['ip' => '10.0.0.2'],
]);

it('applies per-user login limits across different ips without double counting', function (): void {
    config()->set('security.rate_limits.login.max_attempts', 10);
    config()->set('security.rate_limits.login.decay_minutes', 1);
    config()->set('security.rate_limits.login.per_ip.max_attempts', 100);
    config()->set('security.rate_limits.login.per_ip.decay_minutes', 1);
    config()->set('security.rate_limits.login.per_device.max_attempts', 100);
    config()->set('security.rate_limits.login.per_device.decay_minutes', 1);
    config()->set('security.rate_limits.login.per_user.max_attempts', 2);
    config()->set('security.rate_limits.login.per_user.decay_minutes', 1);

    $identifier = 'throttle-user';
    $perUserKey = loginUserThrottleKey((int) crc32($identifier));

    RateLimiter::clear(loginThrottleKey($identifier, '127.0.0.1'));
    RateLimiter::clear(loginThrottleKey($identifier, '10.0.0.2'));
    RateLimiter::clear(loginThrottleKey($identifier, '10.0.0.3'));
    RateLimiter::clear(loginIpThrottleKey('127.0.0.1'));
    RateLimiter::clear(loginIpThrottleKey('10.0.0.2'));
    RateLimiter::clear(loginIpThrottleKey('10.0.0.3'));
    RateLimiter::clear($perUserKey);

    $simulateAttempt = function (string $ip) use ($identifier): void {
        $request = Request::create('/login', 'POST', [
            'login' => $identifier,
            'password' => 'irrelevant',
        ]);

        $request->server->set('REMOTE_ADDR', $ip);

        $limits = collect(RateLimiter::limiter('login')($request));

        $limits->each(function ($limit) {
            $hashedKey = md5('login'.$limit->key);
            RateLimiter::hit($hashedKey, $limit->decaySeconds);
        });
    };

    $simulateAttempt('127.0.0.1');
    $simulateAttempt('10.0.0.2');
    $simulateAttempt('10.0.0.3');

    expect(RateLimiter::tooManyAttempts($perUserKey, 2))->toBeTrue();
});

it('provides wait metadata when per-user login limit is exceeded', function (): void {
    config()->set('security.rate_limits.login.max_attempts', 10);
    config()->set('security.rate_limits.login.decay_minutes', 1);
    config()->set('security.rate_limits.login.per_ip.max_attempts', 100);
    config()->set('security.rate_limits.login.per_ip.decay_minutes', 1);
    config()->set('security.rate_limits.login.per_device.max_attempts', 100);
    config()->set('security.rate_limits.login.per_device.decay_minutes', 1);
    config()->set('security.rate_limits.login.per_user.max_attempts', 1);
    config()->set('security.rate_limits.login.per_user.decay_minutes', 1);

    $identifier = 'json-throttle-user';
    $perUserKey = loginUserThrottleKey((int) crc32($identifier));

    RateLimiter::clear(loginThrottleKey($identifier));
    RateLimiter::clear($perUserKey);

    $request = Request::create('/login', 'POST', [
        'login' => $identifier,
        'password' => 'irrelevant',
    ], [], [], ['HTTP_ACCEPT' => 'application/json']);

    $limits = collect(RateLimiter::limiter('login')($request));

    $limits->each(function ($limit) {
        $hashedKey = md5('login'.$limit->key);
        RateLimiter::hit($hashedKey, $limit->decaySeconds);
    });

    $jsonResponse = collect(RateLimiter::limiter('login')($request))
        ->firstWhere('key', 'user:'.crc32($identifier))->responseCallback;

    $response = $jsonResponse();

    expect($response->getStatusCode())->toBe(429)
        ->and($response->getData(true)['meta']['wait_seconds'])->toBeGreaterThanOrEqual(1);
});
