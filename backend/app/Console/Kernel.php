<?php

namespace App\Console;

use App\Jobs\BackupDatabase;
use App\Jobs\CleanupInactivePlayers;
use App\Jobs\CleanupOldMessages;
use App\Jobs\CleanupOldReports;
use App\Jobs\ProcessAdventures;
use App\Jobs\ProcessBuildingCompletion;
use App\Jobs\ProcessServerTasks;
use App\Jobs\ProcessTroopTraining;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new ProcessBuildingCompletion())
            ->name('automation:buildings')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ProcessTroopTraining())
            ->name('automation:training')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ProcessAdventures())
            ->name('automation:adventures')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ProcessServerTasks())
            ->name('automation:server-tasks')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new CleanupInactivePlayers())
            ->name('maintenance:cleanup-inactive-players')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new CleanupOldReports())
            ->name('maintenance:cleanup-reports')
            ->hourlyAt(15)
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new CleanupOldMessages())
            ->name('maintenance:cleanup-messages')
            ->hourlyAt(30)
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new BackupDatabase())
            ->name('maintenance:backup-database')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
