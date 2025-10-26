<?php

declare(strict_types=1);

namespace App\Services\Provisioning;

use App\Models\Game\ServerTask;
use Illuminate\Support\Facades\Log;

class ServerProvisioningService
{
    public function __construct(private readonly InfrastructureApiClient $client) {}

    public function install(ServerTask $task): void
    {
        $payload = $this->payloadForTask($task);

        Log::info('Dispatching infrastructure install workflow.', [
            'task_id' => $task->getKey(),
            'payload' => $payload,
        ]);

        $this->client->installGameWorld($payload);
    }

    public function uninstall(ServerTask $task): void
    {
        $payload = $this->payloadForTask($task);

        Log::info('Dispatching infrastructure uninstall workflow.', [
            'task_id' => $task->getKey(),
            'payload' => $payload,
        ]);

        $this->client->uninstallGameWorld($payload);
    }

    public function restartEngine(ServerTask $task): void
    {
        $this->runEngineAction($task, 'restart');
    }

    public function startEngine(ServerTask $task): void
    {
        $this->runEngineAction($task, 'start');
    }

    public function stopEngine(ServerTask $task): void
    {
        $this->runEngineAction($task, 'stop');
    }

    public function flushLoginTokens(?ServerTask $task = null, array $extraPayload = []): void
    {
        $payload = $task !== null ? $this->payloadForTask($task) : [];
        $payload = array_merge($payload, $extraPayload);

        Log::info('Dispatching login token refresh workflow.', [
            'task_id' => $task?->getKey(),
            'payload' => $payload,
        ]);

        $this->client->refreshLoginTokens($payload);
    }

    private function runEngineAction(ServerTask $task, string $action): void
    {
        $payload = $this->payloadForTask($task);
        $payload['action'] = $action;

        Log::info('Dispatching engine control workflow.', [
            'task_id' => $task->getKey(),
            'action' => $action,
            'payload' => $payload,
        ]);

        $this->client->controlEngine($payload);
    }

    private function payloadForTask(ServerTask $task): array
    {
        $payload = $task->payload ?? [];
        $payload['task_id'] = $task->getKey();
        $payload['task_type'] = $task->type;

        return $payload;
    }
}
