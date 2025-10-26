<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\LoginActivity;
use App\Models\User;
use App\Notifications\NewDeviceLogin;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;

class DeviceVerificationService
{
    public function __construct(private readonly CacheRepository $cache) {}

    public function notifyIfNewDevice(User $user, LoginActivity $activity, bool $actingAsSitter): void
    {
        if (! config('security.device_verification.enabled', true)) {
            return;
        }

        $ip = $activity->ip_address;
        $userAgent = $activity->user_agent;

        if ($ip === null || $ip === '' || $userAgent === null || $userAgent === '') {
            return;
        }

        $fingerprint = $this->fingerprint($ip, $userAgent);
        $cacheKey = sprintf('security:device:%s:%s', $user->getKey(), $fingerprint);

        if ($this->cache->has($cacheKey)) {
            return;
        }

        $alreadySeen = LoginActivity::query()
            ->where('user_id', $user->getKey())
            ->where('ip_address', $ip)
            ->where('user_agent', $userAgent)
            ->whereKeyNot($activity->getKey())
            ->exists();

        $cacheTtl = (int) config('security.device_verification.cache_ttl_minutes', 720);

        if ($alreadySeen) {
            $this->cache->put($cacheKey, true, now()->addMinutes($cacheTtl));

            return;
        }

        $user->notify(new NewDeviceLogin(
            ipAddress: $ip,
            userAgent: $userAgent,
            actingAsSitter: $actingAsSitter,
            actingSitterId: $activity->acting_sitter_id,
            timestamp: $activity->logged_at ?? $activity->created_at ?? Carbon::now(),
        ));

        $this->cache->put($cacheKey, true, now()->addMinutes($cacheTtl));
    }

    private function fingerprint(string $ip, string $userAgent): string
    {
        return hash('sha256', $ip.'|'.$userAgent);
    }
}
