<?php

namespace App\Services\ServerTasks\Handlers;

use App\Jobs\Provisioning\StartEngineJob;
use App\Models\Game\ServerTask;
use Illuminate\Support\Facades\Log;
use Throwable;

class StartEngineHandler implements ServerTaskHandler
{
    public function handle(ServerTask $task): void
    {
        try {
            StartEngineJob::dispatch($task->getKey())
                ->onQueue(config('provisioning.queue', 'provisioning'));

            Log::info('Queued start engine job for provisioning task.', [
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
