<?php

namespace App\ValueObjects\Game\Construction;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class BuildingQueueEntry
{
    public function __construct(
        public readonly int $villageId,
        public readonly int $slot,
        public readonly CarbonImmutable $commenceAt,
        public readonly bool $isMasterBuilder,
        public readonly array $payload = [],
    ) {
        if ($slot < 1 || $slot > 99) {
            throw new InvalidArgumentException('Building slot must be between 1 and 99.');
        }
    }

    public function isDue(CarbonImmutable $now): bool
    {
        return $this->commenceAt->lessThanOrEqualTo($now);
    }
}
