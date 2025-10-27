<?php

declare(strict_types=1);

use App\Enums\SitterPermissionPreset;
use App\Enums\StaffRole;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('cache.default', 'array');

    if (! Route::has('testing.sitter-context')) {
        Route::middleware(['web', 'context.sitter'])
            ->get('/testing/sitter-context', function (Request $request) {
                return response()->json([
                    'acting_as' => $request->attributes->get('sitter.acting_as'),
                    'acted_by' => $request->attributes->get('sitter.acted_by'),
                    'owner' => $request->attributes->get('sitter.owner'),
                    'context' => [
                        'acting_as_sitter' => Context::get('acting_as_sitter'),
                        'acting_sitter_username' => Context::get('acting_sitter_username'),
                    ],
                ]);
            })
            ->name('testing.sitter-context');
    }
});

/**
 * Ensure the sitter context middleware exposes metadata for UI banners and audit logs.
 */
it('shares sitter context across the request lifecycle', function (): void {
    $owner = User::factory()->create([
        'username' => 'owner-account',
        'role' => StaffRole::Admin,
    ]);

    $sitter = User::factory()->create([
        'username' => 'sitter-helper',
    ]);

    SitterDelegation::query()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => SitterPermissionPreset::Guardian->permissionKeys(),
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    $this->actingAs($owner);

    session()->put('auth.acting_as_sitter', true);
    session()->put('auth.sitter_id', $sitter->getKey());

    $response = $this->get('/testing/sitter-context');

    $response->assertOk();
    $response->assertJsonPath('acting_as', true);
    $response->assertJsonPath('acted_by.username', 'sitter-helper');
    $response->assertJsonPath('owner.username', 'owner-account');
    $response->assertJsonPath('context.acting_as_sitter', true);
    $response->assertJsonPath('context.acting_sitter_username', 'sitter-helper');

    /** @var ViewFactory $factory */
    $factory = View::getFacadeRoot();
    expect($factory->shared('sitterContext'))
        ->toMatchArray([
            'active' => true,
            'account' => [
                'username' => 'owner-account',
            ],
            'sitter' => [
                'username' => 'sitter-helper',
            ],
        ]);
});

/**
 * Confirm the middleware clears sitter metadata for owner-only sessions.
 */
it('clears sitter metadata when not acting as a sitter', function (): void {
    $owner = User::factory()->create([
        'username' => 'owner-only',
        'role' => StaffRole::Admin,
    ]);

    $this->actingAs($owner);

    session()->forget('auth.acting_as_sitter');
    session()->forget('auth.sitter_id');

    $response = $this->get('/testing/sitter-context');

    $response->assertOk();
    $response->assertJsonPath('acting_as', false);
    $response->assertJsonPath('acted_by', []);
    $response->assertJsonPath('owner', []);
});
