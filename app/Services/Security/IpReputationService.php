<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\ValueObjects\Security\IpReputationReport;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;

class IpReputationService
{
    public function __construct(protected CacheRepository $cache) {}

    public function evaluate(string $ip): IpReputationReport
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return IpReputationReport::clean($ip, ['reason' => 'invalid_ip']);
        }

        $settings = Config::get('security.ip_reputation', []);

        if (! Arr::get($settings, 'enabled', false)) {
            return IpReputationReport::clean($ip, ['reason' => 'disabled']);
        }

        $cacheTtl = max(0, (int) Arr::get($settings, 'cache_ttl_seconds', 900));
        $cacheKey = 'security:ip-reputation:'.hash('sha256', $ip);

        if ($cacheTtl > 0) {
            return $this->cache->remember($cacheKey, $cacheTtl, fn (): IpReputationReport => $this->evaluateFresh($ip, $settings));
        }

        return $this->evaluateFresh($ip, $settings);
    }

    public function shouldBlock(IpReputationReport $report): bool
    {
        $threshold = (int) Config::get('security.ip_reputation.block_score', 70);

        return $report->shouldBlock($threshold);
    }

    protected function evaluateFresh(string $ip, array $settings): IpReputationReport
    {
        if ($this->matchesList($ip, Arr::get($settings, 'allow', []))) {
            return IpReputationReport::clean($ip, ['reason' => 'allowlist']);
        }

        if ($this->matchesList($ip, Arr::get($settings, 'block', []))) {
            return IpReputationReport::blocked($ip, 100, ['blocklist'], ['config'], ['reason' => 'static_block']);
        }

        $mockScores = Arr::get($settings, 'mock_scores', []);

        if (is_array($mockScores) && array_key_exists($ip, $mockScores)) {
            $mock = $mockScores[$ip];

            if (is_array($mock)) {
                $score = (int) ($mock['score'] ?? 100);
                $flags = array_values((array) ($mock['flags'] ?? ['mock']));
                $sources = array_values((array) ($mock['sources'] ?? ['mock']));

                return new IpReputationReport($ip, $score, $flags, $sources, ['reason' => 'mock']);
            }

            return new IpReputationReport($ip, (int) $mock, ['mock'], ['mock'], ['reason' => 'mock']);
        }

        $httpReport = $this->queryHttpProvider($ip, $settings);

        if ($httpReport instanceof IpReputationReport) {
            return $httpReport;
        }

        return IpReputationReport::clean($ip);
    }

    protected function matchesList(string $ip, array $list): bool
    {
        $ips = array_filter(Arr::get($list, 'ips', []), static fn ($value) => is_string($value) && $value !== '');

        if (in_array($ip, $ips, true)) {
            return true;
        }

        foreach (Arr::get($list, 'cidrs', []) as $cidr) {
            if (is_string($cidr) && $cidr !== '' && IpUtils::checkIp($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    protected function queryHttpProvider(string $ip, array $settings): ?IpReputationReport
    {
        $config = Arr::get($settings, 'providers.http');

        if (! is_array($config) || empty($config['endpoint'])) {
            return null;
        }

        try {
            $timeout = max(1, (int) ($config['timeout'] ?? 3));
            $request = Http::timeout($timeout)->asJson();

            if (! empty($config['token'])) {
                $request = $request->withToken($config['token']);
            }

            $response = $request->get($config['endpoint'], ['ip' => $ip]);

            if (! $response->successful()) {
                Log::debug('ip-reputation.http.failed', [
                    'ip' => $ip,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $payload = (array) $response->json();
            $score = (int) ($payload['score'] ?? 0);
            $flags = array_values(array_filter((array) ($payload['flags'] ?? []), static fn ($value) => $value !== ''));
            $sources = array_values(array_filter((array) ($payload['sources'] ?? ['http'])));
            $meta = (array) ($payload['meta'] ?? []);

            return new IpReputationReport($ip, max(0, min(100, $score)), $flags, $sources, array_merge($meta, ['reason' => 'http_provider']));
        } catch (Throwable $exception) {
            Log::warning('ip-reputation.http.exception', [
                'ip' => $ip,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
