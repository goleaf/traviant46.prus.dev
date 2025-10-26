<?php

declare(strict_types=1);

namespace App\Services\Provisioning;

use App\Services\Provisioning\Exceptions\ProvisioningException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class InfrastructureApiClient
{
    public function __construct(private readonly array $config) {}

    public function installGameWorld(array $payload): void
    {
        $this->request('post', '/game-worlds/install', $payload);
    }

    public function uninstallGameWorld(array $payload): void
    {
        $this->request('post', '/game-worlds/uninstall', $payload);
    }

    public function controlEngine(array $payload): void
    {
        $this->request('post', '/game-worlds/engine', $payload);
    }

    public function refreshLoginTokens(array $payload = []): void
    {
        $this->request('post', '/game-worlds/refresh-login-tokens', $payload);
    }

    private function request(string $method, string $endpoint, array $payload): void
    {
        $baseUrl = rtrim((string) Arr::get($this->config, 'base_url', ''), '/');

        if ($baseUrl === '') {
            throw new ProvisioningException('Provisioning API base URL is not configured.');
        }

        $timeout = (int) Arr::get($this->config, 'timeout', 30);
        $token = Arr::get($this->config, 'token');

        try {
            $pendingRequest = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->timeout($timeout);

            if ($token !== null && $token !== '') {
                $pendingRequest = $pendingRequest->withToken($token);
            }

            $response = $pendingRequest->{$method}(ltrim($endpoint, '/'), $payload);

            if ($response->failed()) {
                $identifier = (string) Str::uuid();

                Log::error('Provisioning API request failed.', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'payload' => $payload,
                    'response' => $response->json(),
                    'error_reference' => $identifier,
                ]);

                throw new ProvisioningException(sprintf('Provisioning API request failed (%s).', $identifier));
            }
        } catch (Throwable $throwable) {
            if ($throwable instanceof ProvisioningException) {
                throw $throwable;
            }

            $identifier = (string) Str::uuid();

            Log::error('Provisioning API request encountered an exception.', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'error_reference' => $identifier,
                'exception' => $throwable,
            ]);

            throw new ProvisioningException(sprintf('Provisioning API request failed (%s).', $identifier), 0, $throwable);
        }
    }
}
