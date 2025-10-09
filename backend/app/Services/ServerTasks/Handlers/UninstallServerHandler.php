<?php

namespace App\Services\ServerTasks\Handlers;

use App\Models\Game\ServerTask;
use Illuminate\Support\Facades\Log;

class UninstallServerHandler implements ServerTaskHandler
{
    public function handle(ServerTask ): void
    {
        Log::info('Processed server task.', [
            'task_id' => ->getKey(),
            'type' => ->type,
            'payload' => ->payload,
        ]);

        ->markCompleted();
        ->save();
    }
}
