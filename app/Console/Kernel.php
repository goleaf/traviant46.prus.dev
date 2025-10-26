<?php

namespace App\Console;

use App\Jobs\ProcessAdventures;
use App\Jobs\ProcessBuildingCompletion;
use App\Jobs\ProcessServerTasks;
use App\Jobs\ProcessTroopTraining;
use App\Jobs\ScheduleDailyQuestReset;
use App\Jobs\ScheduleMedalDistribution;
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

        $schedule->job(new ScheduleDailyQuestReset())
            ->name('automation:daily-quests')
            ->dailyAt(config('automation.daily_quests.reset_at', '12:05'))
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ScheduleMedalDistribution())
            ->name('automation:medals')
            ->cron(config('automation.medals.cron', '0 */6 * * *'))
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
