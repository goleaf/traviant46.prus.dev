<?php

declare(strict_types=1);

use App\Models\SitterDelegation;
use App\Models\User;
use App\Support\Auth\SitterPermissionMatrixResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows actions when the session is not acting as a sitter', function (): void {
    $owner = User::factory()->create();

    $resolver = new SitterPermissionMatrixResolver($owner);

    expect($resolver->restriction('build')->isPermitted())->toBeTrue()
        ->and($resolver->restriction('train')->isPermitted())->toBeTrue();
});

it('blocks restricted actions for sitters and surfaces the configured reason', function (): void {
    $owner = User::factory()->create([
        'sitter_permission_matrix' => [
            'build' => [
                'permission' => 'build',
                'reason' => 'Custom build lockout.',
            ],
        ],
    ]);

    $sitter = User::factory()->create();

    SitterDelegation::query()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => ['farm'],
        'expires_at' => null,
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    actingAs($owner);

    session()->put('auth.acting_as_sitter', true);
    session()->put('auth.sitter_id', $sitter->getKey());

    $resolver = new SitterPermissionMatrixResolver($owner);

    $restriction = $resolver->restriction('build');

    expect($restriction->isPermitted())->toBeFalse()
        ->and($restriction->reason)->toBe('Custom build lockout.');
});

it('uses the default message when the sitter delegation is missing', function (): void {
    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    actingAs($owner);

    session()->put('auth.acting_as_sitter', true);
    session()->put('auth.sitter_id', $sitter->getKey());

    $resolver = new SitterPermissionMatrixResolver($owner);

    $restriction = $resolver->restriction('send');

    expect($restriction->isPermitted())->toBeFalse()
        ->and($restriction->reason)->toBe(__('Dispatching troops is restricted while you are a sitter.'));
});
