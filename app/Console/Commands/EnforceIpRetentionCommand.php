<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Security\IpRetentionEnforcer;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class EnforceIpRetentionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'privacy:enforce-ip-retention';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymize and purge stored IP addresses according to privacy retention policies.';

    /**
     * Execute the console command.
     */
    public function handle(IpRetentionEnforcer $enforcer): int
    {
        $results = $enforcer->enforce();

        $rows = [];

        foreach ($results as $context => $metrics) {
            $rows[] = [
                'context' => $context,
                'backfilled' => (string) Arr::get($metrics, 'backfilled', 0),
                'anonymized' => (string) Arr::get($metrics, 'anonymized', 0),
                'deleted' => (string) Arr::get($metrics, 'deleted', 0),
            ];
        }

        if ($rows !== []) {
            $this->table(['Dataset', 'Hashes Added', 'Plaintext Redacted', 'Records Purged'], $rows);
        }

        $this->components->info('IP retention enforcement completed.');

        return self::SUCCESS;
    }
}
