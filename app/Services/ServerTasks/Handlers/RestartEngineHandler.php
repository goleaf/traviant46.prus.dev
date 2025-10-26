<?php

declare(strict_types=1);

namespace App\Services\ServerTasks\Handlers;

use App\Jobs\Provisioning\RestartEngineJob;
use App\Models\Game\ServerTask;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestartEngineHandler implements ServerTaskHandler
{
    public function handle(ServerTask $task): void
    {
        try {
            RestartEngineJob::dispatch($task->getKey())
                ->onQueue(config('provisioning.queue', 'provisioning'));

            Log::info('Queued restart engine job for provisioning task.', [
                'task_id' => $task->getKey(),
                'payload' => $task->payload,
            ]);
        } catch (Throwable $exception) {
            $task->markFailed($exception->getMessage());
            $task->save();

            throw $exception;
        }
    }
}
