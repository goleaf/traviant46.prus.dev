<?php

declare(strict_types=1);

namespace App\Monitoring\Metrics;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class LogMetricRecorder implements MetricRecorder
{
    public function __construct(
        private readonly string $channel = 'metrics',
    ) {}

    public function increment(string $metric, float $value = 1.0, array $tags = []): void
    {
        $this->write('counter', $metric, $value, $tags);
    }

    public function gauge(string $metric, float $value, array $tags = []): void
    {
        $this->write('gauge', $metric, $value, $tags);
    }

    private function write(string $type, string $metric, float $value, array $tags): void
    {
        Log::channel($this->channel)->info('metric.recorded', array_filter([
            'metric' => $this->normalizeMetricName($metric),
            'value' => $value,
            'tags' => $this->normalizeTags($tags),
            'type' => $type,
            'recorded_at' => now()->toIso8601String(),
        ], static fn ($value) => $value !== null && $value !== []));
    }

    private function normalizeMetricName(string $metric): string
    {
        $trimmed = trim($metric);

        if ($trimmed === '') {
            return 'custom.metric';
        }

        return str_replace(' ', '_', $trimmed);
    }

    /**
     * @param array<string, scalar|bool|int|float|null> $tags
     * @return array<string, string>
     */
    private function normalizeTags(array $tags): array
    {
        $filtered = [];

        foreach ($tags as $key => $value) {
            if ($value === null) {
                continue;
            }

            $key = (string) $key;

            if ($key === '') {
                continue;
            }

            $filtered[$this->normalizeTagKey($key)] = (string) $value;
        }

        return Arr::sortKeys($filtered);
    }

    private function normalizeTagKey(string $key): string
    {
        return str_replace([' ', '/'], ['_', '.'], strtolower($key));
    }
}
