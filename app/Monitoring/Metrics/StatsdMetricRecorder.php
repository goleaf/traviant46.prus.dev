<?php

declare(strict_types=1);

namespace App\Monitoring\Metrics;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StatsdMetricRecorder implements MetricRecorder
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $namespace = '',
        private readonly ?string $failoverChannel = null,
    ) {}

    public function increment(string $metric, float $value = 1.0, array $tags = []): void
    {
        $this->send($metric, 'c', $value, $tags);
    }

    public function gauge(string $metric, float $value, array $tags = []): void
    {
        $this->send($metric, 'g', $value, $tags);
    }

    private function send(string $metric, string $type, float $value, array $tags = []): void
    {
        $message = $this->formatMessage($metric, $type, $value, $tags);

        try {
            $socket = @fsockopen("udp://{$this->host}", $this->port, $errno, $error);

            if ($socket === false) {
                throw new RuntimeException(sprintf('StatsD connection failed: %s (%d)', $error, $errno));
            }

            fwrite($socket, $message);
            fclose($socket);
        } catch (RuntimeException $exception) {
            $this->logFailure($exception, $metric, $tags);
        }
    }

    /**
     * @param array<string, scalar|bool|int|float|null> $tags
     */
    private function formatMessage(string $metric, string $type, float $value, array $tags): string
    {
        $name = trim($this->namespace) !== ''
            ? $this->namespace.'.'.$this->sanitize($metric)
            : $this->sanitize($metric);

        $formatted = sprintf('%s:%s|%s', $name, $value, $type);

        $normalizedTags = $this->normalizeTags($tags);

        if ($normalizedTags !== []) {
            $formatted .= '|#'.implode(',', $normalizedTags);
        }

        return $formatted;
    }

    /**
     * @param array<string, scalar|bool|int|float|null> $tags
     * @return array<int, string>
     */
    private function normalizeTags(array $tags): array
    {
        $normalized = [];

        foreach ($tags as $key => $value) {
            if ($value === null) {
                continue;
            }

            $key = (string) $key;

            if ($key === '') {
                continue;
            }

            $normalized[] = sprintf('%s:%s', $this->sanitize($key), $this->sanitize((string) $value));
        }

        return Arr::sort($normalized);
    }

    private function sanitize(string $value): string
    {
        return str_replace([' ', '/'], ['_', '.'], strtolower(trim($value)));
    }

    private function logFailure(RuntimeException $exception, string $metric, array $tags): void
    {
        $channel = $this->failoverChannel;

        $context = array_filter([
            'metric' => $metric,
            'tags' => $tags,
            'message' => $exception->getMessage(),
        ], static fn ($value) => $value !== null && $value !== []);

        if ($channel !== null && $channel !== '') {
            Log::channel($channel)->warning('statsd.transmission_failed', $context);

            return;
        }

        Log::warning('statsd.transmission_failed', $context);
    }
}
