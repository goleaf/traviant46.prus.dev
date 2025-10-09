<?php

namespace App\Console;

use App\Console\Commands\GameEngineCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        GameEngineCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $logPath = $this->resolveStoragePath('logs/game-engine-scheduler.log');

        $schedule
            ->command('game:engine --runtime=55 --sleep=1')
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo($logPath);

        $schedule
            ->command('game:engine --runtime=300 --sleep=5')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo($logPath);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }

    /**
     * Resolve a path within the storage directory without relying on helper functions.
     */
    protected function resolveStoragePath(string $relativePath): string
    {
        if (function_exists('storage_path')) {
            return storage_path($relativePath);
        }

        $basePath = dirname(__DIR__, 2);

        return $basePath . '/storage/' . ltrim($relativePath, '/');
    }
}
