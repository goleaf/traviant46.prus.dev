<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Shard\CropStarvation;
use App\Jobs\Shard\MovementResolver;
use App\Jobs\Shard\OasisRespawn;
use App\Jobs\Shard\QueueCompleter;
use App\Jobs\Shard\ResourceTick;
use Illuminate\Console\Command;

class GameTick extends Command
{
    protected $signature = 'game:tick {--sync : Run the shard jobs synchronously instead of queueing them}';

    protected $description = 'Dispatch the shard jobs that drive the minute-based game tick.';

    public function handle(): int
    {
        $dispatchSync = (bool) $this->option('sync');

        $this->dispatchJob($dispatchSync, ResourceTick::class);
        $this->dispatchJob($dispatchSync, QueueCompleter::class);
        $this->dispatchJob($dispatchSync, MovementResolver::class);
        $this->dispatchJob($dispatchSync, OasisRespawn::class);
        $this->dispatchJob($dispatchSync, CropStarvation::class);

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
