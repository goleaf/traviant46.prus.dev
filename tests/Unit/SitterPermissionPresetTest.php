<?php

declare(strict_types=1);

use App\Enums\SitterPermission;
use App\Enums\SitterPermissionPreset;

it('provides labelled permission presets', function (): void {
    $preset = SitterPermissionPreset::Guardian;

    expect($preset->value)->toBe('guardian')
        ->and($preset->label())->toContain('Guardian')
        ->and($preset->description())->not->toBe('');

    $permissions = $preset->permissionValues();

    expect($permissions)->toBeArray()
        ->and($permissions)->toContain(SitterPermission::Reinforce->value)
        ->and($permissions)->toContain(SitterPermission::SendResources->value);
});

it('detects presets from stored permissions', function (): void {
    expect(SitterPermissionPreset::detectFromPermissions(null))
        ->toBe(SitterPermissionPreset::FullAccess);

    $guardian = SitterPermissionPreset::Guardian->permissionValues();

    expect(SitterPermissionPreset::detectFromPermissions($guardian))
        ->toBe(SitterPermissionPreset::Guardian);

    $custom = [
        SitterPermission::SpendGold->value,
        SitterPermission::AllianceContribute->value,
    ];

    expect(SitterPermissionPreset::detectFromPermissions($custom))
        ->toBeNull();
});
