<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Monolog\LogRecord;

class RedactSensitiveDataProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->message = $this->redactString($record->message);
        $record->context = $this->redactData($record->context);
        $record->extra = $this->redactData($record->extra);

        return $record;
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function redactData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->redactData($value);

                continue;
            }

            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }

            $stringValue = (string) $value;

            if ($this->isSensitiveKey((string) $key)) {
                $data[$key] = $this->maskValue((string) $key, $stringValue);

                continue;
            }

            $data[$key] = $this->redactString($stringValue);
        }

        return $data;
    }

    private function redactString(string $value): string
    {
        $value = preg_replace(
            pattern: '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i',
            replacement: '[redacted-email]',
            subject: $value,
        ) ?? $value;

        return preg_replace_callback(
            pattern: '/\b((?:\d{1,3}\.){3}\d{1,3}|\[[0-9a-f:]+\])\b/i',
            callback: fn (array $matches): string => $this->hashIp($matches[1]),
            subject: $value,
        ) ?? $value;
    }

    private function maskValue(string $key, string $value): string
    {
        $lowerKey = strtolower($key);

        if (str_contains($lowerKey, 'token') || str_contains($lowerKey, 'secret')) {
            return '[redacted-token]';
        }

        if (str_contains($lowerKey, 'email')) {
            return '[redacted-email]';
        }

        if (str_contains($lowerKey, 'ip')) {
            return $this->hashIp($value);
        }

        if (str_contains($lowerKey, 'password')) {
            return '[redacted-password]';
        }

        return $this->redactString($value);
    }

    private function hashIp(string $ip): string
    {
        $key = config('app.key', 'travian-t46');

        return '[ip:'.substr(hash_hmac('sha256', $ip, $key), 0, 12).']';
    }

    private function isSensitiveKey(string $key): bool
    {
        $needle = strtolower($key);

        return str_contains($needle, 'token')
            || str_contains($needle, 'secret')
            || str_contains($needle, 'password')
            || str_contains($needle, 'email')
            || str_contains($needle, 'ip');
    }
}
