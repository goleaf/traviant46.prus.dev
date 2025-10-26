<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! Route::has('session.ttl.check')) {
        Route::middleware('web')->get('/session-ttl-check', static fn (): string => 'ok')->name('session.ttl.check');
    }

    config([
        'session.driver' => 'array',
        'session.connection' => null,
        'session.store' => null,
    ]);
});

afterEach(function (): void {
    travelBack();
});

it('expires a session after exceeding the idle timeout', function (): void {
    config([
        'session.lifetime' => 1,
        'session.absolute_lifetime' => 30,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/session-ttl-check')
        ->assertOk();

    expect(UserSession::query()->where('user_id', $user->getKey())->exists())->toBeTrue();

    travel(2)->minutes();

    $response = $this->get('/session-ttl-check');

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('session');
    $this->assertGuest();

    expect(UserSession::query()->where('user_id', $user->getKey())->exists())->toBeFalse();

});

it('expires a session after the absolute lifetime regardless of activity', function (): void {
    config([
        'session.lifetime' => 120,
        'session.absolute_lifetime' => 3,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/session-ttl-check')
        ->assertOk();

    travel(4)->minutes();

    $response = $this->get('/session-ttl-check');

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('session');
    $this->assertGuest();

    expect(UserSession::query()->where('user_id', $user->getKey())->exists())->toBeFalse();

});

it('updates session activity metadata while the session remains valid', function (): void {
    config([
        'session.lifetime' => 10,
        'session.absolute_lifetime' => 60,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/session-ttl-check')
        ->assertOk();

    travel(5)->minutes();

    $this->get('/session-ttl-check')
        ->assertOk();

    $session = UserSession::query()->where('user_id', $user->getKey())->firstOrFail();

    expect($session->last_activity_at)->not->toBeNull();
    expect($session->last_activity_at?->diffInSeconds(now()))->toBeLessThan(2);
});
