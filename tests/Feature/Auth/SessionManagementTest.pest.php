<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'session.driver' => 'array',
        'session.connection' => null,
        'session.store' => null,
    ]);
});

it('signs the authenticated user out of all sessions', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $currentSessionId = session()->getId();

    UserSession::query()->create([
        'id' => $currentSessionId,
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.1',
        'last_activity_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    UserSession::query()->create([
        'id' => 'secondary-session',
        'user_id' => $user->getKey(),
        'ip_address' => '10.0.0.2',
        'last_activity_at' => now()->subMinutes(10),
        'expires_at' => now()->addHours(2),
    ]);

    $response = $this->post(route('sessions.sign-out-everywhere'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
    $this->assertGuest();

    expect(UserSession::query()->where('user_id', $user->getKey())->exists())->toBeFalse();
});
