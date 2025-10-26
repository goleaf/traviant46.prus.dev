<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class IpLookupService
{
    public function __construct(
        protected CacheRepository $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $ipAddress): array
    {
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('The provided IP address is invalid.');
        }

        $provider = config('multiaccount.ip_lookup.provider', 'ip-api');
        $cacheTtl = (int) config('multiaccount.ip_lookup.cache_ttl', 86400);
        $cacheKey = sprintf('multiaccount:ip_lookup:%s:%s', $provider, sha1($ipAddress));

        return $this->cache->remember($cacheKey, $cacheTtl, function () use ($provider, $ipAddress): array {
            return match ($provider) {
                'ip-api' => $this->lookupIpApi($ipAddress),
                default => $this->lookupIpApi($ipAddress),
            };
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function lookupIpApi(string $ipAddress): array
    {
        $response = Http::timeout(5)->get('http://ip-api.com/json/'.$ipAddress, [
            'fields' => 'status,message,country,regionName,city,isp,as,org,timezone,proxy,mobile,hosting,query,lat,lon',
            'lang' => 'en',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Unable to reach the geo lookup provider.');
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['status'] ?? 'fail') !== 'success') {
            $message = is_array($payload) ? ($payload['message'] ?? 'Lookup failed.') : 'Lookup failed.';

            throw new RuntimeException((string) $message);
        }

        return [
            'ip' => $payload['query'] ?? $ipAddress,
            'country' => $payload['country'] ?? null,
            'region' => $payload['regionName'] ?? null,
            'city' => $payload['city'] ?? null,
            'isp' => $payload['isp'] ?? null,
            'asn' => $payload['as'] ?? null,
            'organization' => $payload['org'] ?? null,
            'timezone' => $payload['timezone'] ?? null,
            'latitude' => $payload['lat'] ?? null,
            'longitude' => $payload['lon'] ?? null,
            'proxy' => (bool) ($payload['proxy'] ?? false),
            'mobile' => (bool) ($payload['mobile'] ?? false),
            'hosting' => (bool) ($payload['hosting'] ?? false),
        ];
    }
}
