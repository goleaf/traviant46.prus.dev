<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithShardResolver;
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
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public string $queue = 'automation';

    /**
     * @param int $shard Allows the scheduler to scope the job to a shard.
     */
    public function __construct(int $shard = 0)
    {
        $this->initializeShardPartitioning($shard);
    }

    public function handle(ServerTaskScheduler $scheduler): void
    {
        $scheduler->schedule('distribute-medals', [
            'source' => 'scheduler',
            'task' => 'medal-distribution',
        ]);
    }
}
