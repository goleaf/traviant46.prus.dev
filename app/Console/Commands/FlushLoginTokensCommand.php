<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Provisioning\FlushLoginTokensJob;
use Illuminate\Console\Command;

class FlushLoginTokensCommand extends Command
{
    protected $signature = 'provisioning:flush-login-tokens {--queue=} {--sync : Execute the job synchronously}';

    protected $description = 'Refresh login tokens across all provisioned game worlds.';

    public function handle(): int
    {
        $queue = $this->option('queue') ?? config('provisioning.queue', 'provisioning');
        $sync = (bool) $this->option('sync');

        if ($sync) {
            FlushLoginTokensJob::dispatchSync(null);
        } else {
            FlushLoginTokensJob::dispatch(null)
                ->onQueue($queue);
        }

        $this->components->info('Login token refresh has been dispatched.');

        return self::SUCCESS;
    }
}
