<?php

declare(strict_types=1);

namespace App\ValueObjects\Travian;

final class LegacyPaths
{
    public function __construct(
        public readonly string $root,
        public readonly string $include,
        public readonly string $publicInternal,
        public readonly string $resources,
        public readonly string $locale,
        public readonly string $templates,
    ) {}

    public static function resolve(): self
    {
        return app(self::class);
    }

    public static function root(): string
    {
        return self::resolve()->root;
    }

    public static function includePath(): string
    {
        return self::resolve()->include;
    }

    public static function publicInternal(): string
    {
        return self::resolve()->publicInternal;
    }

    public static function resources(): string
    {
        return self::resolve()->resources;
    }

    public static function locale(): string
    {
        return self::resolve()->locale;
    }

    public static function templates(): string
    {
        return self::resolve()->templates;
    }
}
