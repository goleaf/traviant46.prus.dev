<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CropStarvationJob;
use App\Jobs\MovementResolverJob;
use App\Jobs\OasisRespawnJob;
use App\Jobs\ResourceTickJob;
use App\Jobs\Shard\QueueCompleterJob;
use Illuminate\Console\Command;

class GameTick extends Command
{
    protected $signature = 'game:tick {--sync : Run the shard jobs synchronously instead of queueing them}';

    protected $description = 'Dispatch the shard jobs that drive the minute-based game tick.';

    public function handle(): int
    {
        $dispatchSync = (bool) $this->option('sync');

        $this->dispatchJob($dispatchSync, ResourceTickJob::class);
        $this->dispatchJob($dispatchSync, QueueCompleterJob::class);
        $this->dispatchJob($dispatchSync, MovementResolverJob::class);
        $this->dispatchJob($dispatchSync, OasisRespawnJob::class);
        $this->dispatchJob($dispatchSync, CropStarvationJob::class);

        $this->components->info('Shard jobs dispatched successfully.');

        return self::SUCCESS;
    }

    /**
     * @param class-string $job
     */
    private function dispatchJob(bool $sync, string $job): void
    {
        if ($sync) {
            $job::dispatchSync();

            return;
        }

        $job::dispatch();
    }
}
