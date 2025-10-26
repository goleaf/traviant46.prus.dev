<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('displays active sessions to administrators', function (): void {
    $admin = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
        'username' => 'game-admin',
        'email' => 'admin@example.com',
    ]);

    $playerOne = User::factory()->create(['username' => 'player-one']);
    $playerTwo = User::factory()->create(['username' => 'player-two']);

    UserSession::query()->create([
        'id' => 'session-player-one',
        'user_id' => $playerOne->getKey(),
        'ip_address' => '203.0.113.10',
        'user_agent' => 'Firefox',
        'last_activity_at' => now()->subMinutes(5),
        'expires_at' => now()->addHours(3),
    ]);

    UserSession::query()->create([
        'id' => 'session-player-two',
        'user_id' => $playerTwo->getKey(),
        'ip_address' => '198.51.100.22',
        'user_agent' => 'Chrome',
        'last_activity_at' => now()->subMinute(),
        'expires_at' => now()->addHours(2),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.sessions.index'));

    $response->assertOk();
    $response->assertSee('Active Sessions');
    $response->assertSee($playerOne->username);
    $response->assertSee($playerTwo->username);
    $response->assertSee('Users Online');
});

it('restricts session viewer to admin guard', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.sessions.index'));

    $response->assertRedirect(route('login'));
});
