<?php

namespace App\Services\ServerTasks\Handlers;

use App\Models\Game\ServerTask;
use Illuminate\Support\Facades\Log;
use Throwable;

class DistributeMedalsHandler implements ServerTaskHandler
{
    public function handle(ServerTask $task): void
    {
        try {
            Log::info('Distributing medals to eligible players.', [
                'task_id' => $task->getKey(),
                'payload' => $task->payload,
            ]);

            $task->markCompleted();
            $task->save();
        } catch (Throwable $exception) {
            $task->markFailed($exception->getMessage());
            $task->save();

            throw $exception;
        }
    }
}
