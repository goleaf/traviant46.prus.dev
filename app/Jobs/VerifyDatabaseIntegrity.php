<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithShardResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VerifyDatabaseIntegrity implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use InteractsWithShardResolver;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * @param string|null $connection Optional connection name and shard scope.
     * @param int         $shard      Allows the scheduler to scope the job to a shard.
     */
    public function __construct(private readonly ?string $connection = null, int $shard = 0)
    {
        $this->initializeShardPartitioning($shard);
        $this->onQueue('automation');
    }

    public function handle(): void
    {
        $connectionName = $this->connection ?? config('database.default');
        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();

        match ($driver) {
            'mysql' => $this->checkMysql($connection),
            'sqlite' => $this->checkSqlite($connection),
            default => $this->pingConnection($connection),
        };
    }

    protected function checkMysql(Connection $connection): void
    {
        $database = $connection->getDatabaseName();
        $tables = collect(
            $connection->select(
                "SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ? AND TABLE_TYPE = 'BASE TABLE'",
                [$database],
            ),
        )->map(static fn ($row): ?string => $row->TABLE_NAME ?? $row->table_name ?? null)
            ->filter()
            ->values();

        $tables->each(function (string $table) use ($connection): void {
            $quoted = sprintf('`%s`', str_replace('`', '``', $table));
            $results = $connection->select("CHECK TABLE {$quoted}");

            foreach ($results as $result) {
                $status = $result->Msg_text ?? $result->msg_text ?? null;
                if ($status === null || ! in_array($status, ['OK', 'Table is already up to date'], true)) {
                    throw new RuntimeException(sprintf('Integrity check failed for table [%s]: %s', $table, $status ?? 'unknown error'));
                }
            }
        });
    }

    protected function checkSqlite(Connection $connection): void
    {
        $results = collect($connection->select('PRAGMA integrity_check'));

        $status = $results->map(function ($row) {
            return $row->integrity_check ?? $row->integrityCheck ?? null;
        })->filter()->first();

        if ($status !== 'ok') {
            throw new RuntimeException(sprintf('SQLite integrity check failed with status: %s', $status ?? 'unknown'));
        }
    }

    protected function pingConnection(Connection $connection): void
    {
        $connection->select('SELECT 1');
    }
}
