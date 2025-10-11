<?php

namespace App\Services\Maintenance;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseBackupService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function run(?string $connectionName = null): string
    {
        $connectionName ??= Config::get('maintenance.backup.connection', Config::get('database.default'));
        $connection = $this->db->connection($connectionName);

        $directory = Config::get('maintenance.backup.directory', storage_path('app/backups'));
        File::ensureDirectoryExists($directory);

        $timestamp = now()->format('Ymd_His');
        $filename = sprintf('%s_%s.json', $connectionName, $timestamp);
        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $payload = [
            'connection' => $connectionName,
            'driver' => $connection->getConfig('driver'),
            'generated_at' => now()->toIso8601String(),
            'tables' => [],
        ];

        foreach ($this->listTables($connection) as $table) {
            $payload['tables'][$table] = $this->dumpTable($connection, $table);
        }

        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        Log::info('maintenance.backup.created', [
            'connection' => $connectionName,
            'path' => $path,
        ]);

        return $path;
    }

    /**
     * @return array<int, string>
     */
    protected function listTables(ConnectionInterface $connection): array
    {
        $driver = $connection->getConfig('driver');

        return match ($driver) {
            'sqlite' => collect($connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('name')
                ->sort()
                ->values()
                ->all(),
            default => collect($connection->select('SHOW TABLES'))
                ->map(function ($row) {
                    $values = (array) $row;

                    return reset($values);
                })
                ->filter()
                ->sort()
                ->values()
                ->all(),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function dumpTable(ConnectionInterface $connection, string $table): array
    {
        $builder = $connection->table($table);
        $connectionName = $connection->getName();

        if (Schema::connection($connectionName)->hasColumn($table, 'id')) {
            $builder->orderBy('id');
        }

        return $builder->get()->map(fn ($row) => (array) $row)->all();
    }
}
