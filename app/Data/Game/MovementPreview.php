<?php

declare(strict_types=1);

namespace App\Data\Game;

use Illuminate\Support\Carbon;

final class MovementPreview
{
    /**
     * @param array<string, ?float> $unitSpeeds
     */
    public function __construct(
        public readonly float $distance,
        public readonly int $durationSeconds,
        public readonly int $slowestUnitSpeed,
        public readonly float $speedFactor,
        public readonly float $worldSpeedFactor,
        public readonly bool $wrapAround,
        public readonly array $unitSpeeds,
        public readonly Carbon $departAt,
        public readonly Carbon $arriveAt,
        public readonly ?Carbon $returnAt,
        public readonly int $upkeep,
    ) {}
}
