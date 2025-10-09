<?php

namespace App\ValueObjects\Game\Combat;

use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Represents a single wave of units from a specific tribe.
 */
class ArmyComposition
{
    /**
     * @param array<int,int> $units keyed by unit slot (1-11)
     */
    public function __construct(
        public readonly int $tribe,
        public readonly array $units,
    ) {
        if ($tribe < 1 || $tribe > 7) {
            throw new InvalidArgumentException('Unsupported tribe identifier.');
        }

        foreach ($units as $slot => $amount) {
            if (!is_int($slot) || $slot < 1 || $slot > 11) {
                throw new InvalidArgumentException('Unit slots must be integers between 1 and 11.');
            }

            if ($amount < 0) {
                throw new InvalidArgumentException('Unit amount cannot be negative.');
            }
        }
    }

    public function totalUnits(): int
    {
        return array_sum($this->units);
    }

    public function amountForSlot(int $slot): int
    {
        return Arr::get($this->units, $slot, 0);
    }
}
