<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithShardResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Session;

class PruneExpiredSessions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    /**
     * @param int $shard Allows the scheduler to scope the job to a shard.
     */
    public function __construct(int $shard = 0)
    {
        $this->initializeShardPartitioning($shard);
        $this->onQueue('automation');
    }

    public function handle(): void
    {
        $handler = Session::getHandler();
        if ($handler === null) {
            return;
        }

        $lifetime = (int) config('session.lifetime', 120) * 60;

        if (method_exists($handler, 'gc')) {
            $handler->gc($lifetime);
        }
    }
}
