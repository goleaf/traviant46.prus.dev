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

        // High-frequency loop that keeps resource production and build/training queues moving.
        $schedule
            ->command($this->buildEngineCommand(['runtime' => 58, 'sleep' => 1]))
            ->name('automation:resource-and-queue-processing')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo($logPath);

        // Adventure generation and other exploratory tasks do not need to run every minute.
        $schedule
            ->command($this->buildEngineCommand(['runtime' => 300, 'sleep' => 5]))
            ->name('automation:adventures')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo($logPath);

        // Movements (attacks, reinforcements, returns) are the most latency-sensitive jobs.
        $schedule
            ->command($this->buildEngineCommand(['runtime' => 20, 'sleep' => 1]))
            ->name('automation:movement-processing')
            ->everyTenSeconds()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo($logPath);

        // Reset daily quests precisely at midnight so rewards rotate predictably for players.
        $schedule
            ->command($this->buildEngineCommand(['runtime' => 180, 'sleep' => 10]))
            ->name('automation:daily-quests-reset')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo($logPath);

        // Housekeeping routines such as pruning stale records or refreshing caches.
        $schedule
            ->command($this->buildEngineCommand(['runtime' => 600, 'sleep' => 30]))
            ->name('automation:cleanup')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground()
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

    /**
     * Build the command string for the game engine with the provided options.
     *
     * @param  array<string, scalar>  $options
     */
    protected function buildEngineCommand(array $options = []): string
    {
        $command = ['game:engine'];

        foreach ($options as $option => $value) {
            $command[] = sprintf('--%s=%s', $option, $value);
        }

        return implode(' ', $command);
    }
}
