<?php

namespace App\Jobs;

use App\Services\ServerTasks\ServerTaskScheduler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScheduleDailyQuestReset implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;
    public string $queue = 'automation';

    public function handle(ServerTaskScheduler $scheduler): void
    {
        $scheduler->schedule('reset-daily-quests', [
            'source' => 'scheduler',
            'task' => 'daily-quest-reset',
        ]);
    }
}
