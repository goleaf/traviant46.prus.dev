<?php

use App\Services\ServerTasks\Handlers\FlushTokensHandler;
use App\Services\ServerTasks\Handlers\InstallServerHandler;
use App\Services\ServerTasks\Handlers\RestartEngineHandler;
use App\Services\ServerTasks\Handlers\StartEngineHandler;
use App\Services\ServerTasks\Handlers\StopEngineHandler;
use App\Services\ServerTasks\Handlers\UninstallServerHandler;

return [
    'task_handlers' => [
        'install' => InstallServerHandler::class,
        'uninstall' => UninstallServerHandler::class,
        'flushTokens' => FlushTokensHandler::class,
        'restart-engine' => RestartEngineHandler::class,
        'start-engine' => StartEngineHandler::class,
        'stop-engine' => StopEngineHandler::class,
    ],
];
