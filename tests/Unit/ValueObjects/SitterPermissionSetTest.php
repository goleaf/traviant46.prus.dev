<?php

declare(strict_types=1);

use App\Enums\SitterPermission;
use App\ValueObjects\SitterPermissionSet;

it('constructs a permission set from keys and exposes helper accessors', function (): void {
    $set = SitterPermissionSet::fromArray(['farm', 'send_troops']);

    expect($set->canFarm())->toBeTrue()
        ->and($set->canBuild())->toBeFalse()
        ->and($set->canSendTroops())->toBeTrue()
        ->and($set->toArray())->toBe(['farm', 'send_troops'])
        ->and($set->toBitmask())->toBe(SitterPermission::Farm->value | SitterPermission::SendTroops->value);
});

it('restores permissions from an integer mask', function (): void {
    $mask = SitterPermission::Trade->value | SitterPermission::SpendGold->value;

    $set = SitterPermissionSet::fromInt($mask);

    expect($set->canTrade())->toBeTrue()
        ->and($set->canSpendGold())->toBeTrue()
        ->and($set->canFarm())->toBeFalse()
        ->and($set->toArray())->toBe(['trade', 'spend_gold'])
        ->and($set->toBitmask())->toBe($mask);
});

it('rejects unknown permission keys', function (): void {
    expect(fn () => SitterPermissionSet::fromArray(['not-a-permission']))->toThrow(\InvalidArgumentException::class);
});
