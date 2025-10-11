<?php

namespace App\Services\ServerTasks;

use App\Models\Game\ServerTask;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ServerTaskScheduler
{
    public function schedule(string $type, array $payload = [], ?CarbonInterface $availableAt = null): ServerTask
    {
        $availableAt = $availableAt ? Carbon::make($availableAt) : Carbon::now();

        $payload = $this->normalisePayload($payload, $availableAt);

        $pendingTask = ServerTask::query()
            ->where('type', $type)
            ->where('status', ServerTask::STATUS_PENDING)
            ->orderBy('available_at')
            ->first();

        if ($pendingTask !== null) {
            $this->updatePendingTask($pendingTask, $payload, $availableAt);

            return $pendingTask;
        }

        return ServerTask::query()->create([
            'type' => $type,
            'payload' => $payload,
            'available_at' => $availableAt,
        ]);
    }

    protected function normalisePayload(array $payload, CarbonInterface $availableAt): array
    {
        $payload['scheduled_at'] = $availableAt->toISOString();

        return Arr::undot($payload);
    }

    protected function updatePendingTask(ServerTask $task, array $payload, CarbonInterface $availableAt): void
    {
        if ($task->available_at === null || $task->available_at->greaterThan($availableAt)) {
            $task->available_at = $availableAt;
        }

        $task->payload = array_merge($task->payload ?? [], $payload);
        $task->save();
    }
}
