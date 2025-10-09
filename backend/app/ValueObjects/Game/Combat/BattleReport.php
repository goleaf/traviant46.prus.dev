<?php

namespace App\ValueObjects\Game\Combat;

class BattleReport
{
    public function __construct(
        public readonly int $attackerStrength,
        public readonly int $defenderStrength,
        public readonly float $cavalryShare,
    ) {
    }

    public function attackerVictory(): bool
    {
        return $this->attackerStrength > $this->defenderStrength;
    }

    public function strengthRatio(): float
    {
        if ($this->defenderStrength === 0) {
            return INF;
        }

        return $this->attackerStrength / $this->defenderStrength;
    }
}
