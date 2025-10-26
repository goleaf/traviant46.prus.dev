<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoginAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_authenticate_with_username(): void
    {
        $password = 'Secret#1234';
        $user = User::factory()->create([
            'username' => 'WarlordAlpha',
            'password' => Hash::make($password),
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'WarlordAlpha',
            'password' => $password,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_authenticate_with_email_identifier(): void
    {
        $password = 'Secret#1234';
        $user = User::factory()->create([
            'email' => 'General@Alliance.test',
            'password' => Hash::make($password),
        ]);

        $this->from('/login')->post('/login', [
            'login' => 'general@alliance.test',
            'password' => $password,
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->post('/logout');

        $this->from('/login')->post('/login', [
            'email' => 'general@alliance.test',
            'password' => $password,
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rate_limiter_blocks_after_max_attempts(): void
    {
        config()->set('security.rate_limits.login', [
            'max_attempts' => 2,
            'decay_minutes' => 1,
            'per_ip' => [
                'max_attempts' => 2,
                'decay_minutes' => 1,
            ],
            'per_device' => [
                'max_attempts' => 2,
                'decay_minutes' => 1,
            ],
        ]);

        $user = User::factory()->create();

        $firstAttempt = $this->postJson('/login', [
            'login' => $user->username,
            'password' => 'Wrong#123',
        ]);
        $firstAttempt->assertStatus(422);

        $secondAttempt = $this->postJson('/login', [
            'login' => $user->username,
            'password' => 'Wrong#123',
        ]);
        $secondAttempt->assertStatus(422);

        $lockoutAttempt = $this->postJson('/login', [
            'login' => $user->username,
            'password' => 'Wrong#123',
        ]);
        $lockoutAttempt->assertStatus(429);
        $lockoutAttempt->assertJsonFragment([
            'message' => __('Too Many Attempts.'),
        ]);
    }

    public function test_structured_logs_are_emitted_for_authentication_events(): void
    {
        Log::fake(['structured']);

        $password = 'Secret#1234';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $this->post('/login', [
            'login' => $user->username,
            'password' => $password,
        ])->assertRedirect(route('home'));

        Log::channel('structured')->assertLogged('info', function ($message, array $context) use ($user) {
            return $message === 'auth.login.succeeded'
                && ($context['user_id'] ?? null) === $user->getKey()
                && ($context['guard'] ?? null) === (config('fortify.guard') ?: config('auth.defaults.guard', 'web'));
        });

        $this->post('/logout');

        $this->post('/login', [
            'login' => $user->username,
            'password' => 'Wrong#123',
        ]);

        Log::channel('structured')->assertLogged('warning', function ($message, array $context) use ($user) {
            return $message === 'auth.login.failed'
                && ($context['guard'] ?? null) === (config('fortify.guard') ?: config('auth.defaults.guard', 'web'))
                && ($context['identifier_type'] ?? null) === 'username'
                && ($context['identifier_hash'] ?? null) === hash('sha256', strtolower($user->username))
                && ($context['attempts'] ?? 0) >= 1;
        });
    }
}
