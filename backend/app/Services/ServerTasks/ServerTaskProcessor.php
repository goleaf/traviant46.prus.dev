<?php

namespace App\Services\ServerTasks;

use App\Models\Game\ServerTask;
use App\Services\ServerTasks\Handlers\ServerTaskHandler;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class ServerTaskProcessor
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(ServerTask $task): void
    {
        $handlerClass = config("automation.task_handlers.{$task->type}");

        if ($handlerClass === null) {
            throw new RuntimeException(sprintf('No server task handler registered for [%s].', $task->type));
        }

        $handler = $this->container->make($handlerClass);

        if (!$handler instanceof ServerTaskHandler) {
            throw new RuntimeException(sprintf(
                'Server task handler for [%s] must implement %s.',
                $task->type,
                ServerTaskHandler::class
            ));
        }

        $handler->handle($task);
    }
}
