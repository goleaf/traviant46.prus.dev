<?php

declare(strict_types=1);

namespace App\Enums;

enum SitterPermission: int
{
    case Farm = 1;
    case Build = 2;
    case SendTroops = 4;
    case Trade = 8;
    case SpendGold = 16;

    public function key(): string
    {
        return match ($this) {
            self::Farm => 'farm',
            self::Build => 'build',
            self::SendTroops => 'send_troops',
            self::Trade => 'trade',
            self::SpendGold => 'spend_gold',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Farm => 'Farm resources and manage fields',
            self::Build => 'Construct or upgrade buildings',
            self::SendTroops => 'Send troops on missions',
            self::Trade => 'Trade resources via the marketplace',
            self::SpendGold => 'Spend gold on premium actions',
        };
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->key(),
            self::cases(),
        );
    }

    public static function fromKey(string $key): ?self
    {
        foreach (self::cases() as $permission) {
            if ($permission->key() === $key) {
                return $permission;
            }
        }

        return null;
    }
}
