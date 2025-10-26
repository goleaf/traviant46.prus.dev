<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Arr;

class IpAnonymizer
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function anonymize(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        $strategy = strtolower((string) Arr::get($this->config, 'anonymization.strategy', 'hash'));

        return match ($strategy) {
            'truncate' => $this->truncate($ipAddress),
            default => $this->hash($ipAddress),
        };
    }

    public function hash(string $ipAddress): string
    {
        $algo = (string) Arr::get($this->config, 'anonymization.hash.algo', 'sha256');
        $key = (string) Arr::get($this->config, 'anonymization.hash.key', '');

        if ($key === '') {
            return hash($algo, $ipAddress);
        }

        return hash_hmac($algo, $ipAddress, $key);
    }

    public function truncate(string $ipAddress): string
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->truncateIpv6($ipAddress);
        }

        return $this->truncateIpv4($ipAddress);
    }

    protected function truncateIpv4(string $ipAddress): string
    {
        $octets = explode('.', $ipAddress);
        $keep = (int) Arr::get($this->config, 'anonymization.truncate.ipv4_prefix_octets', 3);
        $keep = max(0, min(4, $keep));

        $masked = array_slice($octets, 0, $keep);
        while (count($masked) < 4) {
            $masked[] = '0';
        }

        return implode('.', $masked);
    }

    protected function truncateIpv6(string $ipAddress): string
    {
        $expanded = $this->expandIpv6($ipAddress);
        $hextets = explode(':', $expanded);
        $keep = (int) Arr::get($this->config, 'anonymization.truncate.ipv6_prefix_hextets', 4);
        $keep = max(0, min(8, $keep));

        $masked = array_slice($hextets, 0, $keep);
        while (count($masked) < 8) {
            $masked[] = '0000';
        }

        return $this->compressIpv6($masked);
    }

    /**
     * @param list<string> $parts
     */
    protected function compressIpv6(array $parts): string
    {
        $chunks = array_map(static fn (string $segment): string => ltrim($segment, '0') ?: '0', $parts);
        $joined = implode(':', $chunks);

        // Replace the longest sequence of zeros with "::"
        if (! str_contains($joined, '0:0:')) {
            return $joined;
        }

        $pattern = '((?:^|:)0(?::0)+(?::|$))';

        return preg_replace($pattern, '::', $joined, 1) ?? $joined;
    }

    protected function expandIpv6(string $ipAddress): string
    {
        $packed = inet_pton($ipAddress);

        if ($packed === false) {
            return $ipAddress;
        }

        $hex = unpack('H*', $packed);
        $segments = str_split($hex[1], 4);

        return implode(':', $segments);
    }
}
