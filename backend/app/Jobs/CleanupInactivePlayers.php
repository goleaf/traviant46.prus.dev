<?php

namespace App\Jobs;

use App\Services\Maintenance\InactivePlayerCleanupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupInactivePlayers implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(InactivePlayerCleanupService $service): void
    {
        $result = $service->handle();

        Log::info('maintenance.inactive_players.cleaned', $result);
    }
}
