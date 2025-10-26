<?php

declare(strict_types=1);

use App\Monitoring\Metrics\MetricRecorder;
use App\Providers\FortifyServiceProvider;
use App\Services\Auth\LegacyLoginService;
use App\Services\Security\IpReputationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Mockery;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('blocks authentication when IP reputation crosses the threshold', function (): void {
    Config::set('security.ip_reputation', [
        'enabled' => true,
        'block_score' => 70,
        'cache_ttl_seconds' => 0,
        'allow' => ['ips' => [], 'cidrs' => []],
        'block' => ['ips' => [], 'cidrs' => []],
        'mock_scores' => [
            '203.0.113.200' => ['score' => 95],
        ],
        'providers' => [],
    ]);

    $mockLegacy = Mockery::mock(LegacyLoginService::class);
    $mockLegacy->shouldNotReceive('attempt');
    app()->instance(LegacyLoginService::class, $mockLegacy);

    $metricsMock = Mockery::mock(MetricRecorder::class);
    $metricsMock->shouldReceive('increment')->andReturnNull();
    app()->instance(MetricRecorder::class, $metricsMock);

    $ipReputation = app(IpReputationService::class);

    $provider = new FortifyServiceProvider(app());
    $provider->boot($mockLegacy, $ipReputation);

    $request = Request::create('/login', 'POST', [
        'login' => 'blocked@example.com',
        'password' => 'secret',
    ], [], [], [
        'REMOTE_ADDR' => '203.0.113.200',
    ]);

    $session = app('session')->driver();
    $session->start();
    $request->setLaravelSession($session);

    $reflection = new ReflectionClass(Fortify::class);
    $property = $reflection->getProperty('authenticateUsingCallback');
    $property->setAccessible(true);
    $callback = $property->getValue();

    expect(fn () => $callback($request))->toThrow(ValidationException::class);
});
