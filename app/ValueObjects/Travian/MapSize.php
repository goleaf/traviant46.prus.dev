<?php

namespace App\ValueObjects\Travian;

final class MapSize
{
    public function __construct(private readonly int $value)
    {
    }

    public function value(): int
    {
        return $this->value;
    }

    public static function resolve(): self
    {
        return app(self::class);
    }

    public static function value(): int
    {
        return self::resolve()->value();
    }
}
