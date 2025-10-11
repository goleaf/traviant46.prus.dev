<?php

namespace App\Jobs;

use App\Services\Maintenance\ReportCleanupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupOldReports implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 240;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(ReportCleanupService $service): void
    {
        $result = $service->handle();

        Log::info('maintenance.reports.cleaned', $result);
    }
}
