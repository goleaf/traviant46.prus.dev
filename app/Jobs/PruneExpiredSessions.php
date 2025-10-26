<?php

declare(strict_types=1);

namespace App\Jobs;

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
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct()
    {
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
