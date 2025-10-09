<?php

namespace App\ValueObjects\Game\Resources;

class ResourceTickResult
{
    public function __construct(
        public readonly VillageProduction $previous,
        public readonly VillageProduction $current,
        /** @var array{wood: float, clay: float, iron: float, crop: float} */
        public readonly array $delta,
    ) {
    }
}
