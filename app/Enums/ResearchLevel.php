<?php

declare(strict_types=1);

namespace App\Enums;

enum ResearchLevel: int
{
    case LEVEL_0 = 0;
    case LEVEL_1 = 1;
    case LEVEL_2 = 2;
    case LEVEL_3 = 3;
    case LEVEL_4 = 4;
    case LEVEL_5 = 5;
    case LEVEL_6 = 6;
    case LEVEL_7 = 7;
    case LEVEL_8 = 8;
    case LEVEL_9 = 9;
    case LEVEL_10 = 10;
    case LEVEL_11 = 11;
    case LEVEL_12 = 12;
    case LEVEL_13 = 13;
    case LEVEL_14 = 14;
    case LEVEL_15 = 15;
    case LEVEL_16 = 16;
    case LEVEL_17 = 17;
    case LEVEL_18 = 18;
    case LEVEL_19 = 19;
    case LEVEL_20 = 20;

    public function label(): string
    {
        return 'Level ' . $this->value;
    }

    public function isMax(): bool
    {
        return $this === self::LEVEL_20;
    }

    public function isZero(): bool
    {
        return $this === self::LEVEL_0;
    }

    public function next(): self
    {
        return $this === self::LEVEL_20
            ? $this
            : self::from($this->value + 1);
    }

    public function previous(): self
    {
        return $this === self::LEVEL_0
            ? $this
            : self::from($this->value - 1);
    }

    public static function fromInt(int $level): self
    {
        return self::from($level);
    }

    public static function normalize(int $level): self
    {
        if ($level <= self::LEVEL_0->value) {
            return self::LEVEL_0;
        }

        if ($level >= self::LEVEL_20->value) {
            return self::LEVEL_20;
        }

        return self::from($level);
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
