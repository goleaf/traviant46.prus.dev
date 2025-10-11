<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAdventures;
use App\Jobs\ProcessAuctionEnd;
use App\Jobs\ProcessBuildingCompletion;
use App\Jobs\ProcessMerchantReturn;
use App\Jobs\ProcessTradeRoutes;
use App\Jobs\ProcessTroopTraining;
use Illuminate\Console\Command;

class GameEngineCommand extends Command
{
    protected $signature = 'game:engine {--sync : Run the jobs synchronously instead of queueing them}';

    protected $description = 'Dispatch the primary background jobs that drive the Travian game loop.';

    public function handle(): int
    {
        $dispatchSync = (bool) $this->option('sync');

        $this->dispatchJob($dispatchSync, ProcessBuildingCompletion::class);
        $this->dispatchJob($dispatchSync, ProcessTroopTraining::class);
        $this->dispatchJob($dispatchSync, ProcessAdventures::class);
        $this->dispatchJob($dispatchSync, ProcessTradeRoutes::class);
        $this->dispatchJob($dispatchSync, ProcessMerchantReturn::class);
        $this->dispatchJob($dispatchSync, ProcessAuctionEnd::class);

        $this->components->info('Game engine jobs dispatched successfully.');

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
