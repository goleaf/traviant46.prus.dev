<?php

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithShardResolver;
use App\Services\Security\AuthEventBroadcaster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAuthEventNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $payload Event payload forwarded to downstream systems.
     * @param int                  $shard   Allows the scheduler to scope the job to a shard.
     */
    public function __construct(
        protected string $event,
        protected array $payload,
        int $shard = 0,
    ) {
        $this->initializeShardPartitioning($shard);
    }

    public function handle(AuthEventBroadcaster $broadcaster): void
    {
        $broadcaster->dispatch($this->event, $this->payload);
    }
}
