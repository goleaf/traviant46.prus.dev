<?php

declare(strict_types=1);

use App\Enums\SitterPermission;
use App\Models\Game\Village;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

afterEach(function (): void {
    session()->forget('auth.acting_as_sitter');
    session()->forget('auth.sitter_id');
});

it('allows the owner to view their village resources', function (): void {
    $owner = User::factory()->create();
    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
    ]);

    expect(Gate::forUser($owner)->allows('viewResources', $village))->toBeTrue();
});

it('allows a delegated sitter with farm permission to view village resources', function (): void {
    $owner = User::factory()->create();
    $sitter = User::factory()->create();
    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
    ]);

    $delegation = SitterDelegation::query()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => SitterPermission::Farm->value,
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    session()->put('auth.acting_as_sitter', true);
    session()->put('auth.sitter_id', $sitter->getKey());

    expect(Gate::forUser($owner)->allows('viewResources', $village))->toBeTrue();
});

it('denies a delegated sitter without farm permission from viewing village resources', function (): void {
    $owner = User::factory()->create();
    $sitter = User::factory()->create();
    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
    ]);

    SitterDelegation::query()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => SitterPermission::Build->value,
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    session()->put('auth.acting_as_sitter', true);
    session()->put('auth.sitter_id', $sitter->getKey());

    expect(Gate::forUser($owner)->allows('viewResources', $village))->toBeFalse();
});

it('requires build permission for a sitter to view village infrastructure', function (): void {
    $owner = User::factory()->create();
    $sitter = User::factory()->create();
    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
    ]);

    $delegation = SitterDelegation::query()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => SitterPermission::Farm->value,
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    session()->put('auth.acting_as_sitter', true);
    session()->put('auth.sitter_id', $sitter->getKey());

    expect(Gate::forUser($owner)->allows('viewInfrastructure', $village))->toBeFalse();

    $delegation->update([
        'permissions' => SitterPermission::Build->value,
    ]);

    expect(Gate::forUser($owner)->allows('viewInfrastructure', $village))->toBeTrue();
});

it('denies unrelated users from viewing another village resources screen', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
    ]);

    expect(Gate::forUser($intruder)->allows('viewResources', $village))->toBeFalse();
});
