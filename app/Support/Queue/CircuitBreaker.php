<?php

declare(strict_types=1);

namespace App\Support\Queue;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Carbon;

class CircuitBreaker
{
    private CacheRepository $cache;

    private int $failureThreshold;

    private int $cooldownSeconds;

    public function __construct(CacheFactory $cacheFactory, ConfigRepository $config)
    {
        $settings = $config->get('queue.backpressure', []);
        $store = $settings['cache_store'] ?? null;

        $this->cache = $store ? $cacheFactory->store((string) $store) : $cacheFactory->store();
        $this->failureThreshold = max(1, (int) ($settings['failure_threshold'] ?? 5));
        $this->cooldownSeconds = max(1, (int) ($settings['cooldown_seconds'] ?? 60));
    }

    public function isOpen(string $key): bool
    {
        $state = $this->state($key);

        if ($state['opened_at'] === null) {
            return false;
        }

        if (Carbon::parse($state['opened_at'])->addSeconds($this->cooldownSeconds)->isPast()) {
            $this->reset($key);

            return false;
        }

        return true;
    }

    public function cooldownRemaining(string $key): int
    {
        $state = $this->state($key);

        if ($state['opened_at'] === null) {
            return 0;
        }

        $openedAt = Carbon::parse($state['opened_at']);
        $expiresAt = $openedAt->copy()->addSeconds($this->cooldownSeconds);

        return max(0, (int) now()->diffInSeconds($expiresAt, false));
    }

    public function recordFailure(string $key): void
    {
        $state = $this->state($key);
        $state['failures'] = $state['failures'] + 1;

        if ($state['failures'] >= $this->failureThreshold) {
            $state['failures'] = $this->failureThreshold;
            $state['opened_at'] = now()->toDateTimeString();
        }

        $this->cache->forever($this->cacheKey($key), $state);
    }

    public function recordSuccess(string $key): void
    {
        $this->reset($key);
    }

    private function reset(string $key): void
    {
        $this->cache->forever($this->cacheKey($key), [
            'failures' => 0,
            'opened_at' => null,
        ]);
    }

    /**
     * @return array{failures:int, opened_at:string|null}
     */
    private function state(string $key): array
    {
        return $this->cache->get($this->cacheKey($key), [
            'failures' => 0,
            'opened_at' => null,
        ]);
    }

    private function cacheKey(string $key): string
    {
        return 'queue:circuit:'.sha1($key);
    }
}
