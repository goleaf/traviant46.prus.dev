<?php

namespace App\ValueObjects\Game\Hero;

class HeroItem
{
    /**
     * @param array<string, int|float> $attributes
     */
    public function __construct(
        public readonly int $id,
        public readonly int $type,
        public readonly int $category,
        public readonly array $attributes = [],
    ) {
    }

    public function get(string $key, float $default = 0): float
    {
        return (float) ($this->attributes[$key] ?? $default);
    }
}
