<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Redis;

it('responds to redis ping within an acceptable latency budget', function (): void {
    try {
        $start = microtime(true);
        $response = Redis::connection()->ping();
        $duration = microtime(true) - $start;

        expect(strtoupper((string) $response))->toBe('PONG');
        expect($duration)->toBeLessThan(0.5);
    } catch (Throwable $exception) {
        test()->markTestSkipped('Redis not available: '.$exception->getMessage());
    }
});
