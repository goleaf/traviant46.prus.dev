<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\IpUtils;

class MultiAccountRules
{
    /**
     * @param array{ip_addresses: list<string>, cidr_ranges: list<string>, device_hashes: list<string>} $allowlist
     * @param array{user_agent_keywords?: list<string>, cidr_ranges?: list<string>} $vpnIndicators
     */
    public function __construct(
        protected array $allowlist,
        protected array $vpnIndicators,
    ) {}

    public function allowlistReason(?string $ipAddress, ?string $deviceHash): ?string
    {
        if ($ipAddress !== null && $ipAddress !== '') {
            if (in_array($ipAddress, $this->allowlist['ip_addresses'], true)) {
                return 'allowlisted_ip';
            }

            if ($this->matchesRange($ipAddress, $this->allowlist['cidr_ranges'])) {
                return 'allowlisted_cidr';
            }
        }

        if ($deviceHash !== null && $deviceHash !== '') {
            if (in_array($deviceHash, $this->allowlist['device_hashes'], true)) {
                return 'allowlisted_device';
            }
        }

        return null;
    }

    public function isLikelyVpn(?string $ipAddress, ?string $userAgent): bool
    {
        if ($ipAddress !== null && $ipAddress !== '' && $this->matchesRange($ipAddress, Arr::wrap($this->vpnIndicators['cidr_ranges'] ?? []))) {
            return true;
        }

        if ($userAgent === null || $userAgent === '') {
            return false;
        }

        $keywords = Arr::wrap($this->vpnIndicators['user_agent_keywords'] ?? []);
        $haystack = mb_strtolower($userAgent);

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $ranges
     */
    protected function matchesRange(string $ipAddress, array $ranges): bool
    {
        if ($ranges === []) {
            return false;
        }

        return IpUtils::checkIp($ipAddress, $ranges);
    }
}
