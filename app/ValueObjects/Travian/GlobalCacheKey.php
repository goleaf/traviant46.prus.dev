<?php

declare(strict_types=1);

namespace App\ValueObjects\Travian;

final class GlobalCacheKey
{
    public function __construct(private readonly string $value) {}

    public function toString(): string
    {
        return $this->value;
    }

    public static function resolve(): self
    {
        return app(self::class);
    }

    public static function value(): string
    {
        return self::resolve()->toString();
    }
}
