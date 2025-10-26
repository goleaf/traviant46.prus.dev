<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ServerTasks\ServerTaskScheduler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScheduleMedalDistribution implements ShouldQueue
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
        $scheduler->schedule('distribute-medals', [
            'source' => 'scheduler',
            'task' => 'medal-distribution',
        ]);
    }
}
