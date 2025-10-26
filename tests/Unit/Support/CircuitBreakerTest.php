<?php

declare(strict_types=1);

use App\Support\Queue\CircuitBreaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\app;

beforeEach(function (): void {
    Config::set('queue.backpressure', [
        'max_attempts' => 3,
        'max_runtime' => 120,
        'failure_threshold' => 2,
        'cooldown_seconds' => 60,
        'cache_store' => null,
    ]);
    Carbon::setTestNow(null);
});

it('opens the circuit after threshold of failures and cools down', function (): void {
    /** @var CircuitBreaker $breaker */
    $breaker = app(CircuitBreaker::class);

    expect($breaker->isOpen('database'))->toBeFalse();

    $breaker->recordFailure('database');
    expect($breaker->isOpen('database'))->toBeFalse();

    $breaker->recordFailure('database');
    expect($breaker->isOpen('database'))->toBeTrue();

    Carbon::setTestNow(Carbon::now()->addSeconds(61));
    expect($breaker->isOpen('database'))->toBeFalse();
});

it('resets failure counts when a success is recorded', function (): void {
    /** @var CircuitBreaker $breaker */
    $breaker = app(CircuitBreaker::class);

    $breaker->recordFailure('database');
    $breaker->recordFailure('database');
    expect($breaker->isOpen('database'))->toBeTrue();

    $breaker->recordSuccess('database');
    expect($breaker->isOpen('database'))->toBeFalse();
});
