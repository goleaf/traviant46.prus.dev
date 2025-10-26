<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\BackupDatabase;
use App\Jobs\ExpireSitterDelegations;
use App\Jobs\ProcessAdventures;
use App\Jobs\ProcessBuildingCompletion;
use App\Jobs\ProcessServerTasks;
use App\Jobs\ProcessTroopTraining;
use App\Jobs\PruneExpiredSessions;
use App\Jobs\RebuildMultiAccountAlerts;
use App\Jobs\ScheduleDailyQuestReset;
use App\Jobs\ScheduleMedalDistribution;
use App\Jobs\VerifyDatabaseIntegrity;
use App\Models\LoginActivity;
use App\Models\SitterDelegation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new ProcessBuildingCompletion)
            ->name('automation:buildings')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ProcessTroopTraining)
            ->name('automation:training')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ProcessAdventures)
            ->name('automation:adventures')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ProcessServerTasks)
            ->name('automation:server-tasks')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ExpireSitterDelegations)
            ->name('automation:sitter-expiry')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ScheduleDailyQuestReset)
            ->name('automation:daily-quests')
            ->dailyAt(config('automation.daily_quests.reset_at', '12:05'))
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new ScheduleMedalDistribution)
            ->name('automation:medals')
            ->cron(config('automation.medals.cron', '0 */6 * * *'))
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('provisioning:flush-login-tokens')
            ->name('provisioning:flush-login-tokens')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('privacy:enforce-ip-retention')
            ->name('privacy:enforce-ip-retention')
            ->dailyAt('03:45')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('model:prune', [
            '--model' => LoginActivity::class,
        ])
            ->name('maintenance:prune-login-activities')
            ->dailyAt('01:15')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('model:prune', [
            '--model' => SitterDelegation::class,
        ])
            ->name('maintenance:prune-sitter-delegations')
            ->dailyAt('01:30')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new RebuildMultiAccountAlerts)
            ->name('maintenance:rebuild-multi-account-alerts')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new PruneExpiredSessions)
            ->name('maintenance:prune-sessions')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground();

        if (count(array_filter((array) config('app.previous_keys', []))) > 0) {
            $schedule->command('security:rotate-cookie-keys')
                ->name('maintenance:rotate-cookie-keys')
                ->dailyAt('03:15')
                ->withoutOverlapping()
                ->runInBackground();
        }

        $schedule->job(new BackupDatabase)
            ->name('maintenance:database-backup')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new VerifyDatabaseIntegrity)
            ->name('maintenance:database-integrity-check')
            ->dailyAt('04:30')
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
