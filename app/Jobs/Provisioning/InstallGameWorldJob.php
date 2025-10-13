<?php

namespace App\Jobs\Provisioning;

use App\Models\Game\ServerTask;
use App\Services\Provisioning\ServerProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstallGameWorldJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;
    public string $queue;

    public function __construct(public readonly int $taskId)
    {
        $this->queue = config('provisioning.queue', 'provisioning');
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->overlapKey(), 600)];
    }

    public function handle(ServerProvisioningService $service): void
    {
        $task = ServerTask::query()->find($this->taskId);

        if ($task === null) {
            Log::warning('Install provisioning job skipped because the server task no longer exists.', [
                'task_id' => $this->taskId,
            ]);

            return;
        }

        if ($task->status === ServerTask::STATUS_COMPLETED) {
            return;
        }

        try {
            $service->install($task);

            $task->markCompleted();
            $task->save();
        } catch (Throwable $throwable) {
            $task->markFailed($throwable->getMessage());
            $task->save();

            throw $throwable;
        }
    }

    public function failed(Throwable $throwable): void
    {
        $task = ServerTask::query()->find($this->taskId);

        if ($task === null) {
            return;
        }

        $task->markFailed($throwable->getMessage());
        $task->save();
    }

    private function overlapKey(): string
    {
        return sprintf('provisioning-install-%d', $this->taskId);
    }
}
