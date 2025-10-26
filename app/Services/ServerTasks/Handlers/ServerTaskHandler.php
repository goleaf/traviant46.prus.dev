<?php

namespace App\Services\ServerTasks\Handlers;

use App\Models\Game\ServerTask;

interface ServerTaskHandler
{
    public function handle(ServerTask $task): void;
}
