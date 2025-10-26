<?php

declare(strict_types=1);

use App\Enums\SitterPermissionPreset;
use App\Enums\StaffRole;
use App\Models\AuditLog;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! Route::has('testing.audit')) {
        Route::middleware(['web', 'log.staff.action'])
            ->post('/testing/audit', function () {
                return response()->json(['status' => 'ok']);
            })
            ->name('testing.audit');
    }
});

it('records acted_by metadata when a sitter performs a staff action', function (): void {
    $owner = User::factory()->create([
        'username' => 'admin-owner',
        'role' => StaffRole::Admin,
    ]);

    $sitter = User::factory()->create([
        'username' => 'trusted-sitter',
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

    $this->post('/testing/audit', ['resource' => 'village'])->assertOk();

    $log = AuditLog::query()->latest('id')->first();

    expect($log)->not()->toBeNull();
    expect($log->metadata)->toHaveKey('acted_by');
    expect($log->metadata['acted_by'])->toMatchArray([
        'id' => $sitter->getKey(),
        'username' => $sitter->username,
    ]);
});
