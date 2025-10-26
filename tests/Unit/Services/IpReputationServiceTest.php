<?php

declare(strict_types=1);

use App\Services\Security\IpReputationService;
use App\ValueObjects\Security\IpReputationReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    Config::set('security.ip_reputation', [
        'enabled' => true,
        'block_score' => 70,
        'cache_ttl_seconds' => 0,
        'allow' => ['ips' => [], 'cidrs' => []],
        'block' => ['ips' => [], 'cidrs' => []],
        'mock_scores' => [],
        'providers' => [],
    ]);
});

test('allowlist bypasses further evaluation', function (): void {
    Config::set('security.ip_reputation.allow.ips', ['10.0.0.5']);

    $service = app(IpReputationService::class);
    $report = $service->evaluate('10.0.0.5');

    expect($report)->toBeInstanceOf(IpReputationReport::class);
    expect($report->score)->toBe(0);
});

test('blocklist immediately blocks an address', function (): void {
    Config::set('security.ip_reputation.block.ips', ['203.0.113.9']);

    $service = app(IpReputationService::class);
    $report = $service->evaluate('203.0.113.9');

    expect($report->score)->toBeGreaterThanOrEqual(70);
    expect($service->shouldBlock($report))->toBeTrue();
});

test('mocked scores allow deterministic testing', function (): void {
    Config::set('security.ip_reputation.mock_scores', [
        '198.51.100.10' => ['score' => 45, 'flags' => ['tor']],
    ]);

    $service = app(IpReputationService::class);
    $report = $service->evaluate('198.51.100.10');

    expect($report->score)->toBe(45);
    expect($report->flags)->toContain('tor');
    expect($service->shouldBlock($report))->toBeFalse();
});
