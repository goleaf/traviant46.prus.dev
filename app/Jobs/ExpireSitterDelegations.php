<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\DelegationRevoked;
use App\Jobs\Concerns\InteractsWithShardResolver;
use App\Models\SitterDelegation;
use App\Notifications\SitterDelegationExpired;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ExpireSitterDelegations implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public string $queue = 'automation';

    /**
     * @param int $shard Allows the scheduler to scope the job to a shard.
     */
    public function __construct(int $shard = 0)
    {
        $this->initializeShardPartitioning($shard);
    }

    public function handle(): void
    {
        $now = Carbon::now();

        SitterDelegation::query()
            ->with(['owner', 'sitter'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->chunkById(50, function ($delegations): void {
                /** @var iterable<int, SitterDelegation> $delegations */
                foreach ($delegations as $delegation) {
                    $this->revokeDelegation($delegation);
                }
            });
    }

    protected function revokeDelegation(SitterDelegation $delegation): void
    {
        $delegation->loadMissing('owner', 'sitter');

        $owner = $delegation->owner;
        $sitter = $delegation->sitter;

        if ($owner !== null) {
            $owner->notify(new SitterDelegationExpired($delegation));
        }

        if ($sitter !== null) {
            $sitter->notify(new SitterDelegationExpired($delegation));
        }

        event(new DelegationRevoked($delegation, null, 'expired'));

        $delegation->delete();
    }
}
