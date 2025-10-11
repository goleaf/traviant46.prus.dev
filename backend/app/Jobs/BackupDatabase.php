<?php

namespace App\Jobs;

use App\Services\Maintenance\DatabaseBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BackupDatabase implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(DatabaseBackupService $service): void
    {
        $service->run();
    }
}
