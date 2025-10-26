<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\SitterPermission;
use InvalidArgumentException;

final class SitterPermissionSet
{
    public function __construct(private int $bitmask = 0)
    {
        if ($this->bitmask < 0) {
            throw new InvalidArgumentException('Permission bitmask must be non-negative.');
        }
    }

    public static function none(): self
    {
        return new self;
    }

    public static function fromInt(int $bitmask): self
    {
        return new self($bitmask);
    }

    /**
     * @param array<int, int|string|SitterPermission> $permissions
     */
    public static function fromArray(array $permissions): self
    {
        $bitmask = 0;

        foreach ($permissions as $permission) {
            $bitmask |= match (true) {
                $permission instanceof SitterPermission => $permission->value,
                is_int($permission) => $permission,
                is_string($permission) => self::permissionFromKey($permission)->value,
                default => throw new InvalidArgumentException('Invalid sitter permission value.'),
            };
        }

        return new self($bitmask);
    }

    public function toBitmask(): int
    {
        return $this->bitmask;
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        $granted = [];

        foreach (SitterPermission::cases() as $permission) {
            if ($this->allows($permission)) {
                $granted[] = $permission->key();
            }
        }

        return $granted;
    }

    public function allows(SitterPermission $permission): bool
    {
        return ($this->bitmask & $permission->value) === $permission->value;
    }

    public function canFarm(): bool
    {
        return $this->allows(SitterPermission::Farm);
    }

    public function canBuild(): bool
    {
        return $this->allows(SitterPermission::Build);
    }

    public function canSendTroops(): bool
    {
        return $this->allows(SitterPermission::SendTroops);
    }

    public function canTrade(): bool
    {
        return $this->allows(SitterPermission::Trade);
    }

    public function canSpendGold(): bool
    {
        return $this->allows(SitterPermission::SpendGold);
    }

    private static function permissionFromKey(string $key): SitterPermission
    {
        $permission = SitterPermission::fromKey($key);

        if ($permission === null) {
            throw new InvalidArgumentException(sprintf('Unknown sitter permission "%s".', $key));
        }

        return $permission;
    }
}
