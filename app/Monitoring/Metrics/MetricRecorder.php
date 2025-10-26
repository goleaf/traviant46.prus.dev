<?php

declare(strict_types=1);

namespace App\Monitoring\Metrics;

interface MetricRecorder
{
    public function increment(string $metric, float $value = 1.0, array $tags = []): void;

    public function gauge(string $metric, float $value, array $tags = []): void;
}
