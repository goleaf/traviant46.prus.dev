<?php

namespace App\ValueObjects\Game\Resources;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class VillageProduction
{
    public function __construct(
        public readonly int $villageId,
        public readonly int $ownerId,
        public readonly float $wood,
        public readonly float $clay,
        public readonly float $iron,
        public readonly float $crop,
        public readonly int $woodPerHour,
        public readonly int $clayPerHour,
        public readonly int $ironPerHour,
        public readonly int $cropPerHour,
        public readonly int $upkeep,
        public readonly int $population,
        public readonly int $maxStore,
        public readonly int $maxCrop,
        public readonly CarbonImmutable $lastUpdatedAt,
    ) {
        if ($maxStore <= 0 || $maxCrop <= 0) {
            throw new InvalidArgumentException('Storage capacity must be positive.');
        }
    }

    /**
     * @return array{wood: float, clay: float, iron: float, crop: float}
     */
    public function asArray(): array
    {
        return [
            'wood' => $this->wood,
            'clay' => $this->clay,
            'iron' => $this->iron,
            'crop' => $this->crop,
        ];
    }
}
