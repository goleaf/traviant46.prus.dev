<?php

declare(strict_types=1);

use App\Enums\SitterPermission;
use App\Models\SitterDelegation;
use App\ValueObjects\SitterPermissionSet;

it('evaluates delegated permissions assigned via keys', function (): void {
    $delegation = new SitterDelegation();
    $delegation->permissions = ['farm', 'trade'];

    expect($delegation->canFarm())->toBeTrue()
        ->and($delegation->canTrade())->toBeTrue()
        ->and($delegation->canBuild())->toBeFalse()
        ->and($delegation->canSendTroops())->toBeFalse()
        ->and($delegation->allows(SitterPermission::Trade))->toBeTrue()
        ->and($delegation->allows(SitterPermission::SendTroops))->toBeFalse();
});

it('accepts value objects and maintains helper accessors', function (): void {
    $delegation = new SitterDelegation();
    $delegation->permissions = SitterPermissionSet::fromInt(
        SitterPermission::SendTroops->value | SitterPermission::SpendGold->value
    );

    expect($delegation->canSendTroops())->toBeTrue()
        ->and($delegation->canSpendGold())->toBeTrue()
        ->and($delegation->canFarm())->toBeFalse();
});
