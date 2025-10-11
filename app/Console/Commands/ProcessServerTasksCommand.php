<?php

namespace App\Console\Commands;

use App\Jobs\ProcessServerTasks;
use Illuminate\Console\Command;

class ProcessServerTasksCommand extends Command
{
    protected $signature = 'server:tasks {--sync : Run the task queue synchronously}';

    protected $description = 'Dispatch the queued automation tasks that replace the legacy TaskWorker daemon.';

    public function handle(): int
    {
        $sync = (bool) $this->option('sync');

        if ($sync) {
            ProcessServerTasks::dispatchSync();
        } else {
            ProcessServerTasks::dispatch();
        }

        $this->components->info('Server task queue dispatched successfully.');

        return self::SUCCESS;
    }
}
