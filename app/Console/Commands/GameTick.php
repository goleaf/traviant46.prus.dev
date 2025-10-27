<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CropStarvationJob;
use App\Jobs\MovementResolverJob;
use App\Jobs\OasisRespawnJob;
use App\Jobs\ResourceTickJob;
use App\Jobs\Shard\QueueCompleterJob;
use App\Support\ShardResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Console\Command;

/**
 * Coordinate the Travian minute tick by dispatching all shard-aware jobs.
 */
class GameTick extends Command
{
    protected $signature = 'game:tick {--sync : Run the shard jobs synchronously instead of queueing them}';

    protected $description = 'Dispatch the shard jobs that drive the minute-based game tick.';

    public function handle(ShardResolver $shardResolver): int
    {
        $dispatchSync = (bool) $this->option('sync');

        foreach ($shardResolver->shards() as $shard) {
            $this->dispatchJob($dispatchSync, new ResourceTickJob(shard: $shard));
            $this->dispatchJob($dispatchSync, new QueueCompleterJob(shard: $shard));
        }

        $this->dispatchJob($dispatchSync, new MovementResolverJob());
        $this->dispatchJob($dispatchSync, new OasisRespawnJob());
        $this->dispatchJob($dispatchSync, new CropStarvationJob());

        $this->components->info('Shard jobs dispatched successfully.');

        return self::SUCCESS;
    }

    private function dispatchJob(bool $sync, ShouldQueue $job): void
    {
        if ($sync) {
            dispatch_sync($job);

            return;
        }

        dispatch($job);
    }
}
