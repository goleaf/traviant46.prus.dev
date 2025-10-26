<?php

declare(strict_types=1);

use App\Models\SitterDelegation;
use App\Models\User;
use App\ValueObjects\SitterPermissionSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('supports creating, listing, and removing sitter delegations through the api', function (): void {
    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    actingAs($owner);

    $expiry = Carbon::now()->addDays(2)->startOfSecond();

    $expectedBitmask = SitterPermissionSet::fromArray(['send_troops'])->toBitmask();

    $createResponse = postJson('/api/v1/sitters', [
        'sitter_username' => $sitter->username,
        'permissions' => ['send_troops'],
        'expires_at' => $expiry->toIso8601String(),
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.permissions', ['send_troops'])
        ->assertJsonPath('data.bitmask', $expectedBitmask)
        ->assertJsonPath('data.expires_at', $expiry->toIso8601String());

    $delegationId = $createResponse->json('data.id');

    $indexResponse = getJson('/api/v1/sitters');

    $indexResponse->assertOk()
        ->assertJsonPath('data.0.sitter.username', $sitter->username)
        ->assertJsonPath('data.0.permissions', ['send_troops'])
        ->assertJsonPath('data.0.bitmask', $expectedBitmask);

    $deleteResponse = deleteJson('/api/v1/sitters/'.$delegationId);

    $deleteResponse->assertNoContent();
    expect(SitterDelegation::query()->whereKey($delegationId)->exists())->toBeFalse();
});

it('filters sitter delegations by sitter username fragment', function (): void {
    $owner = User::factory()->create();
    $matchingSitter = User::factory()->create(['username' => 'AlphaWolf']);
    $otherSitter = User::factory()->create(['username' => 'BravoFox']);

    SitterDelegation::query()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $matchingSitter->getKey(),
        'permissions' => ['send_troops'],
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    SitterDelegation::query()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $otherSitter->getKey(),
        'permissions' => ['trade'],
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    actingAs($owner);

    $response = getJson('/api/v1/sitters?search=Alpha');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sitter.username', $matchingSitter->username);
});
