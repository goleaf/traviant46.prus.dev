<?php

namespace App\ValueObjects\Travian;

final class TaskWorkerRuntime
{
    public function __construct(
        public readonly string $root,
        public readonly string $include,
        public readonly string $phpBinary,
    ) {
    }

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

    public static function phpBinary(): string
    {
        return self::resolve()->phpBinary;
    }
}
