<?php

namespace App\Console;

use App\Jobs\CheckGameFinish;
use App\Jobs\ProcessAdventures;
use App\Jobs\ProcessAllianceBonus;
use App\Jobs\ProcessArtifactEffects;
use App\Jobs\ProcessBuildingCompletion;
use App\Jobs\ProcessDailyQuests;
use App\Jobs\ProcessMedals;
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

        $schedule->job(new ProcessDailyQuests())
            ->name('automation:daily-quests')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ProcessMedals())
            ->name('automation:medals')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ProcessAllianceBonus())
            ->name('automation:alliance-bonus')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ProcessArtifactEffects())
            ->name('automation:artifact-effects')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new CheckGameFinish())
            ->name('automation:check-game-finish')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
