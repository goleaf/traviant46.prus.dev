<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Game\ServerTaskStatus;
use App\Jobs\Concerns\InteractsWithShardResolver;
use App\Models\Game\ServerTask;
use App\Services\ServerTasks\ServerTaskProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessServerTasks implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public string $queue = 'automation';

    /**
     * @param int $chunkSize Number of tasks to process per execution.
     * @param int $shard     Allows the scheduler to scope the job to a shard.
     */
    public function __construct(private readonly int $chunkSize = 25, int $shard = 0)
    {
        $this->initializeShardPartitioning($shard);
    }

    public function handle(ServerTaskProcessor $processor): void
    {
        ServerTask::query()
            ->due()
            ->orderBy('available_at')
            ->limit($this->chunkSize)
            ->get()
            ->each(function (ServerTask $task) use ($processor): void {
                $this->processTask($task, $processor);
            });
    }

    private function processTask(ServerTask $task, ServerTaskProcessor $processor): void
    {
        try {
            $lockedTask = null;

            DB::transaction(function () use ($task, &$lockedTask): void {
                $lockedTask = ServerTask::query()
                    ->whereKey($task->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedTask === null) {
                    return;
                }

                if ($lockedTask->status !== ServerTaskStatus::Pending) {
                    return;
                }

                if ($lockedTask->available_at?->isFuture()) {
                    return;
                }

                $lockedTask->markProcessing();
                $lockedTask->save();
            }, 5);

            if ($lockedTask === null || $lockedTask->status !== ServerTaskStatus::Processing) {
                return;
            }

            $processor->handle($lockedTask->fresh() ?? $lockedTask);

            $latestTask = $lockedTask->fresh();
            if ($latestTask === null) {
                return;
            }

            if ($latestTask->status === ServerTaskStatus::Processing) {
                $latestTask->markCompleted();
                $latestTask->save();
            }
        } catch (Throwable $throwable) {
            $latestTask = $task->fresh();
            if ($latestTask !== null) {
                $latestTask->markFailed($throwable->getMessage());
                $latestTask->save();
            }

            Log::error('Server task processing failed.', [
                'task_id' => $task->getKey(),
                'type' => $task->type,
                'exception' => $throwable,
            ]);

            throw $throwable;
        }
    }
}
