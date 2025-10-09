<?php

namespace App\ValueObjects\Game\Combat;

use InvalidArgumentException;

/**
 * Immutable data transfer object describing base statistics for a single unit slot.
 */
class UnitStats
{
    public function __construct(
        public readonly int $offense,
        public readonly int $infantryDefense,
        public readonly int $cavalryDefense,
        public readonly int $upkeep,
        public readonly bool $isCavalry,
    ) {
        if ($offense < 0 || $infantryDefense < 0 || $cavalryDefense < 0) {
            throw new InvalidArgumentException('Unit statistics cannot be negative.');
        }

        if ($upkeep < 0) {
            throw new InvalidArgumentException('Upkeep must be zero or positive.');
        }
    }
}
