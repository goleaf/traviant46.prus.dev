<?php

use App\Enums\SitterPermission;
use App\Enums\SitterPermissionPreset;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('creates sitter delegation using a preset', function (): void {
    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    $expiry = Carbon::now()->addDay()->toIso8601String();

    $response = actingAs($owner)->postJson(route('sitters.store'), [
        'sitter_username' => $sitter->username,
        'preset' => SitterPermissionPreset::Guardian->value,
        'expires_at' => $expiry,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.preset', SitterPermissionPreset::Guardian->value)
        ->assertJsonPath('data.effective_permissions', SitterPermissionPreset::Guardian->permissionValues());

    $delegation = SitterDelegation::query()
        ->where('owner_user_id', $owner->getKey())
        ->where('sitter_user_id', $sitter->getKey())
        ->first();

    expect($delegation)->not->toBeNull()
        ->and($delegation?->permissions)->toBe(SitterPermissionPreset::Guardian->permissionValues())
        ->and(optional($delegation)->expires_at?->toIso8601String())->toBe($expiry);
});

it('normalises explicit full permissions to the full access preset', function (): void {
    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    $response = actingAs($owner)->postJson(route('sitters.store'), [
        'sitter_username' => $sitter->username,
        'permissions' => \collect(SitterPermission::cases())->map->value->all(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.preset', SitterPermissionPreset::FullAccess->value)
        ->assertJsonPath('data.permissions', null);

    $delegation = SitterDelegation::query()
        ->where('owner_user_id', $owner->getKey())
        ->where('sitter_user_id', $sitter->getKey())
        ->first();

    expect($delegation?->permissions)->toBeNull();
});

it('exposes available permissions and presets when listing delegations', function (): void {
    $owner = User::factory()->create();
    actingAs($owner);

    $response = getJson(route('sitters.index'));

    $response->assertOk()
        ->assertJsonPath('acting_as_sitter', false)
        ->assertJson(fn (array $json) => expect($json['available_permissions'])
            ->toHaveCount(count(SitterPermission::cases()))
            ->and($json['available_permissions'][0])->toHaveKeys(['value', 'label']))
        ->assertJson(fn (array $json) => expect($json['presets'])
            ->not->toBeEmpty()
            ->and($json['presets'][0])->toHaveKeys(['value', 'label', 'description', 'permissions']));
});
