<?php

declare(strict_types=1);

use App\Enums\SitterPermission;
use App\Enums\SitterPermissionPreset;

it('exposes consistent keys and labels for sitter permissions', function (): void {
    $keys = SitterPermission::keys();

    expect($keys)->toContain('farm')
        ->and($keys)->toContain('build')
        ->and(count($keys))->toBe(count(SitterPermission::cases()));

    foreach (SitterPermission::cases() as $permission) {
        expect(SitterPermission::fromKey($permission->key()))->toBe($permission)
            ->and($permission->label())->not->toBe('');
    }
});

it('returns null when looking up an unknown permission key', function (): void {
    expect(SitterPermission::fromKey('unknown-feature'))->toBeNull();
});

it('exposes preset metadata and permissions', function (): void {
    $preset = SitterPermissionPreset::Guardian;

    expect($preset->value)->toBe('guardian')
        ->and($preset->label())->toContain('Guardian')
        ->and($preset->permissionKeys())
        ->toContain('send_troops')
        ->toContain('reinforce');
});

it('detects presets from stored permissions', function (): void {
    $guardianKeys = SitterPermissionPreset::Guardian->permissionKeys();
    $fullKeys = SitterPermissionPreset::FullAccess->permissionKeys();

    expect(SitterPermissionPreset::detectFromPermissions($guardianKeys))
        ->toBe(SitterPermissionPreset::Guardian)
        ->and(SitterPermissionPreset::detectFromPermissions($fullKeys))
        ->toBe(SitterPermissionPreset::FullAccess)
        ->and(SitterPermissionPreset::detectFromPermissions(['spend_gold']))
        ->toBeNull();
});
