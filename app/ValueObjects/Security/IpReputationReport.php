<?php

declare(strict_types=1);

namespace App\ValueObjects\Security;

class IpReputationReport
{
    /**
     * @param array<int, string> $flags
     * @param array<int, string> $sources
     */
    public function __construct(
        public readonly string $ip,
        public readonly int $score,
        public readonly array $flags = [],
        public readonly array $sources = [],
        public readonly array $meta = [],
    ) {}

    public static function clean(string $ip, array $meta = []): self
    {
        return new self($ip, 0, [], [], $meta);
    }

    public static function blocked(string $ip, int $score, array $flags, array $sources = [], array $meta = []): self
    {
        return new self($ip, max(0, min(100, $score)), $flags, $sources, $meta);
    }

    public function shouldBlock(int $threshold): bool
    {
        return $this->score >= $threshold;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'score' => $this->score,
            'flags' => $this->flags,
            'sources' => $this->sources,
            'meta' => $this->meta,
        ];
    }
}
