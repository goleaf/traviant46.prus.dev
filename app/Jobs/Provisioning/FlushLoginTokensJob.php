<?php

declare(strict_types=1);

namespace App\Jobs\Provisioning;

use App\Enums\Game\ServerTaskStatus;
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

class FlushLoginTokensJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public string $queue;

    public function __construct(public readonly ?int $taskId = null, public readonly array $payload = [])
    {
        $this->queue = config('provisioning.queue', 'provisioning');
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->overlapKey(), 300)];
    }

    public function handle(ServerProvisioningService $service): void
    {
        $task = null;

        if ($this->taskId !== null) {
            $task = ServerTask::query()->find($this->taskId);

            if ($task === null) {
                Log::warning('Flush login tokens job skipped because the server task no longer exists.', [
                    'task_id' => $this->taskId,
                ]);

                return;
            }

            if ($task->status === ServerTaskStatus::Completed) {
                return;
            }
        }

        try {
            $service->flushLoginTokens($task, $this->payload);

            if ($task !== null) {
                $task->markCompleted();
                $task->save();
            }
        } catch (Throwable $throwable) {
            if ($task !== null) {
                $task->markFailed($throwable->getMessage());
                $task->save();
            }

            throw $throwable;
        }
    }

    public function failed(Throwable $throwable): void
    {
        if ($this->taskId === null) {
            return;
        }

        $task = ServerTask::query()->find($this->taskId);

        if ($task === null) {
            return;
        }

        $task->markFailed($throwable->getMessage());
        $task->save();
    }

    private function overlapKey(): string
    {
        return $this->taskId === null
            ? 'provisioning-flush-login-tokens'
            : sprintf('provisioning-flush-login-tokens-%d', $this->taskId);
    }
}
