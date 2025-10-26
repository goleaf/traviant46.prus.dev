<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessAdventures;
use App\Jobs\ProcessBuildingCompletion;
use App\Jobs\ProcessTroopTraining;
use App\Support\ShardResolver;
use Illuminate\Console\Command;

class GameEngineCommand extends Command
{
    protected $signature = 'game:engine {--sync : Run the jobs synchronously instead of queueing them}';

    protected $description = 'Dispatch the primary background jobs that drive the Travian game loop.';

    public function handle(): int
    {
        $dispatchSync = (bool) $this->option('sync');
        $shards = app(ShardResolver::class)->shards();

        foreach ($shards as $shard) {
            $this->dispatchJob($dispatchSync, ProcessBuildingCompletion::class, $shard);
            $this->dispatchJob($dispatchSync, ProcessTroopTraining::class, $shard);
            $this->dispatchJob($dispatchSync, ProcessAdventures::class, $shard);
        }

        $this->components->info('Game engine jobs dispatched successfully.');

        return self::SUCCESS;
    }

    /**
     * @param class-string $job
     */
    private function dispatchJob(bool $sync, string $job, int $shard): void
    {
        if ($sync) {
            $job::dispatchSync(shard: $shard);

            return;
        }

        $job::dispatch(shard: $shard);
    }
}
