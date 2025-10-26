<?php

namespace App\Services\Security;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthEventBroadcaster
{
    public function enabled(): bool
    {
        return (bool) Config::get('security.auth_events.enabled', false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $event, array $payload): void
    {
        if (! $this->enabled()) {
            return;
        }

        $driver = Config::get('security.auth_events.driver', 'webhook');

        if ($driver === 'webhook') {
            $this->sendWebhook($event, $payload);

            return;
        }

        if ($driver === 'eventbridge') {
            $this->sendEventBridge($event, $payload);

            return;
        }

        Log::warning('auth-events.unknown-driver', ['driver' => $driver, 'event' => $event]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendWebhook(string $event, array $payload): void
    {
        $config = Config::get('security.auth_events.webhook', []);
        $url = Arr::get($config, 'url');

        if (! is_string($url) || $url === '') {
            Log::debug('auth-events.webhook.missing-url', ['event' => $event]);

            return;
        }

        $body = [
            'event' => $event,
            'occurred_at' => CarbonImmutable::now()->toIso8601String(),
            'payload' => $payload,
        ];

        $secret = (string) Arr::get($config, 'secret', '');
        $signature = $secret !== '' ? hash_hmac('sha256', json_encode($body, JSON_THROW_ON_ERROR), $secret) : null;

        try {
            $request = Http::timeout((int) Arr::get($config, 'timeout', 5))->asJson();

            if (! Arr::get($config, 'verify_ssl', true)) {
                $request = $request->withoutVerifying();
            }

            $headers = [
                'X-Auth-Event' => $event,
            ];

            if ($signature !== null) {
                $headers['X-Auth-Event-Signature'] = $signature;
            }

            $request->withHeaders($headers)->post($url, $body)->throw();
        } catch (Throwable $exception) {
            Log::warning('auth-events.webhook.failed', [
                'event' => $event,
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendEventBridge(string $event, array $payload): void
    {
        $config = Config::get('security.auth_events.eventbridge', []);
        $bus = Arr::get($config, 'bus_name');

        if (! is_string($bus) || $bus === '') {
            Log::debug('auth-events.eventbridge.missing-bus', ['event' => $event]);

            return;
        }

        if (! class_exists('Aws\\EventBridge\\EventBridgeClient')) {
            Log::notice('auth-events.eventbridge.unavailable', ['event' => $event, 'reason' => 'aws-sdk-missing']);

            return;
        }

        $region = Arr::get($config, 'region', 'us-east-1');
        $source = Arr::get($config, 'source', 'traviant.auth');

        try {
            $client = new \Aws\EventBridge\EventBridgeClient([
                'version' => 'latest',
                'region' => $region,
            ]);

            $client->putEvents([
                'Entries' => [[
                    'EventBusName' => $bus,
                    'Source' => $source,
                    'DetailType' => $event,
                    'Detail' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'Time' => CarbonImmutable::now(),
                ]],
            ]);
        } catch (Throwable $exception) {
            Log::warning('auth-events.eventbridge.failed', [
                'event' => $event,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
