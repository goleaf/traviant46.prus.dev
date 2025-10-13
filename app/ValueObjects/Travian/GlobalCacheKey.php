<?php

namespace App\ValueObjects\Travian;

final class GlobalCacheKey
{
    public function __construct(private readonly string $value)
    {
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function resolve(): self
    {
        return app(self::class);
    }

    public static function value(): string
    {
        return self::resolve()->value();
    }
}
