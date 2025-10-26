<?php

declare(strict_types=1);

namespace App\Enums;

use InvalidArgumentException;

enum AttackMissionType: int
{
    case Spy = 1;
    case Reinforcement = 2;
    case Normal = 3;
    case Raid = 4;
    case Settlers = 5;
    case Evasion = 6;
    case Adventure = 7;

    public static function fromLegacyValue(int $value): self
    {
        return self::tryFrom($value) ?? throw new InvalidArgumentException(sprintf('Unknown attack mission type [%d].', $value));
    }
}
