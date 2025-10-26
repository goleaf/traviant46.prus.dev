<?php

use App\Services\ServerTasks\Handlers\FlushTokensHandler;
use App\Services\ServerTasks\Handlers\DistributeMedalsHandler;
use App\Services\ServerTasks\Handlers\InstallServerHandler;
use App\Services\ServerTasks\Handlers\RestartEngineHandler;
use App\Services\ServerTasks\Handlers\ResetDailyQuestsHandler;
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
        'reset-daily-quests' => ResetDailyQuestsHandler::class,
        'distribute-medals' => DistributeMedalsHandler::class,
    ],
    'daily_quests' => [
        'reset_at' => env('DAILY_QUEST_RESET_AT', '12:05'),
    ],
    'medals' => [
        'cron' => env('MEDAL_DISTRIBUTION_CRON', '0 */6 * * *'),
    ],
];
