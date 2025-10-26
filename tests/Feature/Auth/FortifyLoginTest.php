<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

use function Pest\Laravel\post;

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

dataset('fortify-login-identifiers', [
    'email field fallback' => static function (User $user, string $password): array {
        return [
            'payload' => [
                'email' => $user->email,
                'password' => $password,
            ],
            'throttle_key' => loginThrottleKey($user->email),
        ];
    },
    'uppercase email identifier' => static function (User $user, string $password): array {
        return [
            'payload' => [
                'login' => strtoupper($user->email),
                'password' => $password,
            ],
            'throttle_key' => loginThrottleKey($user->email),
        ];
    },
    'uppercase username identifier' => static function (User $user, string $password): array {
        return [
            'payload' => [
                'login' => strtoupper($user->username),
                'password' => $password,
            ],
            'throttle_key' => loginThrottleKey($user->username),
        ];
    },
]);

it('authenticates using alternate identifiers', function (callable $payloadFactory): void {
    $password = 'ValidPass#123';
    $user = User::factory()->create([
        'username' => 'alt-identifier',
        'email' => 'alt@example.com',
        'password' => Hash::make($password),
    ]);

    ['payload' => $payload, 'throttle_key' => $throttleKey] = $payloadFactory($user, $password);

    RateLimiter::clear($throttleKey);

    $response = post('/login', $payload);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
})->with('fortify-login-identifiers');

it('authenticates and redirects with valid credentials', function (): void {
    $password = 'ValidPass#123';
    $user = User::factory()->create([
        'username' => 'fortify-user',
        'password' => Hash::make($password),
    ]);

    RateLimiter::clear(loginThrottleKey($user->username));

    $response = post('/login', [
        'login' => $user->username,
        'password' => $password,
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
    $this->assertFalse(session()->get('auth.acting_as_sitter', false));
});

it('rejects invalid credentials', function (): void {
    $user = User::factory()->create([
        'username' => 'invalid-attempt',
        'password' => Hash::make('Correct#Pass1'),
    ]);

    RateLimiter::clear(loginThrottleKey($user->username));

    $response = post('/login', [
        'login' => $user->username,
        'password' => 'WrongPassword',
    ]);

    $response->assertSessionHasErrors(['login']);
    $this->assertGuest();
});

it('locks further attempts after repeated failures', function (): void {
    $user = User::factory()->create([
        'username' => 'lockout-user',
        'password' => Hash::make('Secure#Pass1'),
    ]);

    $throttleKey = loginThrottleKey($user->username);
    RateLimiter::clear($throttleKey);

    foreach (range(1, 5) as $attempt) {
        post('/login', [
            'login' => $user->username,
            'password' => 'Incorrect#'.$attempt,
        ]);
    }

    $lockoutResponse = post('/login', [
        'login' => $user->username,
        'password' => 'Incorrect#Final',
    ]);

    $lockoutResponse->assertSessionHasErrors(['login']);
    expect(session()->get('errors')->first('login'))->toContain('Too many login attempts');
    expect(RateLimiter::tooManyAttempts($throttleKey, 5))->toBeTrue();
});
