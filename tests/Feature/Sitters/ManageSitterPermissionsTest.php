<?php

declare(strict_types=1);

use App\Events\DelegationAssigned;
use App\Events\DelegationRevoked;
use App\Events\DelegationUpdated;
use App\Jobs\ExpireSitterDelegations;
use App\Models\SitterDelegation;
use App\Models\User;
use App\Notifications\SitterDelegationExpired;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('assigns a sitter and dispatches the assigned event', function (): void {
    Event::fake();

    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    $payload = [
        'sitter_username' => $sitter->username,
        'permissions' => ['farm', 'send_troops'],
        'expires_at' => Carbon::now()->addHours(3)->toIso8601String(),
    ];

    $response = actingAs($owner)->postJson('/api/v1/sitters', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.sitter.username', $sitter->username)
        ->assertJsonPath('data.permissions', ['farm', 'send_troops']);

    Event::assertDispatched(DelegationAssigned::class, function (DelegationAssigned $event) use ($owner, $sitter): bool {
        return $event->delegation->owner->is($owner)
            && $event->delegation->sitter->is($sitter);
    });

    expect(SitterDelegation::query()->forAccount($owner)->forSitter($sitter)->exists())->toBeTrue();
});

it('rejects invalid sitter usernames and banned users', function (): void {
    $owner = User::factory()->create();
    $banned = User::factory()->state(['is_banned' => true])->create();

    actingAs($owner)
        ->postJson('/api/v1/sitters', [
            'sitter_username' => $owner->username,
            'permissions' => ['farm'],
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('sitter_username');

    actingAs($owner)
        ->postJson('/api/v1/sitters', [
            'sitter_username' => $banned->username,
            'permissions' => ['farm'],
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('sitter_username');
});

it('updates an existing delegation and emits the updated event', function (): void {
    Event::fake();

    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    $delegation = SitterDelegation::factory()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => ['farm'],
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    $response = actingAs($owner)->postJson('/api/v1/sitters', [
        'sitter_username' => $sitter->username,
        'permissions' => ['farm', 'build'],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.permissions', ['farm', 'build']);

    Event::assertDispatched(DelegationUpdated::class, function (DelegationUpdated $event) use ($delegation): bool {
        return $event->delegation->is($delegation)
            && in_array('permissions', $event->changedAttributes, true);
    });
});

it('validates permissions against the allowed set', function (): void {
    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    actingAs($owner)
        ->postJson('/api/v1/sitters', [
            'sitter_username' => $sitter->username,
            'permissions' => ['not-real'],
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('permissions.0');
});

it('revokes delegations when they expire and notifies both parties', function (): void {
    Notification::fake();
    Event::fake([DelegationRevoked::class]);

    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    $delegation = SitterDelegation::factory()->expired()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => ['build'],
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    (new ExpireSitterDelegations)->handle();

    Event::assertDispatched(DelegationRevoked::class, function (DelegationRevoked $event) use ($delegation): bool {
        return $event->delegation->is($delegation) && $event->reason === 'expired';
    });

    Notification::assertSentTo($owner, SitterDelegationExpired::class);
    Notification::assertSentTo($sitter, SitterDelegationExpired::class);

    expect(SitterDelegation::query()->find($delegation->getKey()))->toBeNull();
});

it('allows owners to revoke delegations manually', function (): void {
    Event::fake();

    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    $delegation = SitterDelegation::factory()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => ['farm'],
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    actingAs($owner)
        ->deleteJson('/api/v1/sitters/'.$delegation->getKey())
        ->assertNoContent();

    Event::assertDispatched(DelegationRevoked::class, function (DelegationRevoked $event) use ($delegation): bool {
        return $event->delegation->is($delegation) && $event->reason === 'manual';
    });

    expect(SitterDelegation::query()->forAccount($owner)->forSitter($sitter)->exists())->toBeFalse();
});
