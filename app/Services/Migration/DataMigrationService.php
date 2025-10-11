<?php

namespace App\Services\Migration;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use function collect;

class DataMigrationService
{
    public function run(
        string $feature,
        string $sourceConnection,
        string $targetConnection,
        bool $truncateBeforeImport = false
    ): DataMigrationReport {
        $tables = Config::get(sprintf('migration.features.%s.tables', $feature), []);

        if ($tables === [] || $tables === null) {
            throw new InvalidArgumentException(sprintf('No data migration tables configured for feature [%s].', $feature));
        }

        $report = new DataMigrationReport($feature, $sourceConnection, $targetConnection, $truncateBeforeImport);

        $this->assertConnection($sourceConnection);
        $this->assertConnection($targetConnection);

        $report->addEvent('database_connections', [
            'source' => $sourceConnection,
            'target' => $targetConnection,
        ]);

        foreach ($tables as $tableConfig) {
            $normalised = $this->normaliseTableConfig($tableConfig);

            $report->addEvent('table_started', [
                'feature' => $feature,
                'source_table' => $normalised['source'],
                'target_table' => $normalised['target'],
                'mode' => $normalised['mode'],
            ]);

            $stats = $this->migrateTable(
                $sourceConnection,
                $targetConnection,
                $normalised,
                $truncateBeforeImport || $normalised['truncate']
            );

            $report->addTableResult($normalised['target'], $stats);
            $report->addEvent('table_complete', array_merge([
                'feature' => $feature,
                'source_table' => $normalised['source'],
                'target_table' => $normalised['target'],
            ], $stats));
        }

        return $report;
    }

    protected function assertConnection(string $name): ConnectionInterface
    {
        $connection = DB::connection($name);
        $connection->getPdo();

        return $connection;
    }

    /**
     * @param array<string, mixed>|string $config
     * @return array{
     *     source: string,
     *     target: string,
     *     primary_key: ?string,
     *     chunk: int,
     *     mode: string,
     *     unique_by: array<int, string>,
     *     update_columns: array<int, string>|null,
     *     columns: array<int, string>,
     *     where: array<int|string, mixed>,
     *     transform: callable|null,
     *     chunk_by_id: bool,
     *     truncate: bool,
     *     order_by: array<int|string, string>
     * }
     */
    protected function normaliseTableConfig(array|string $config): array
    {
        if (is_string($config)) {
            $config = ['table' => $config];
        }

        $source = $config['source'] ?? $config['table'] ?? $config['target'] ?? null;
        $target = $config['target'] ?? $config['table'] ?? $source;

        if ($source === null || $target === null) {
            throw new InvalidArgumentException('Migration table configuration must define a table name.');
        }

        $primaryKey = $config['primary_key'] ?? 'id';
        $chunkSize = (int) ($config['chunk'] ?? Config::get('migration.chunk_size', 500));
        $chunkSize = $chunkSize > 0 ? $chunkSize : 500;

        $mode = $config['mode'] ?? (($config['unique_by'] ?? null) !== null ? 'upsert' : 'insert');
        $uniqueBy = array_values((array) ($config['unique_by'] ?? ($mode === 'upsert' ? [$primaryKey] : [])));
        $updateColumns = $config['update_columns'] ?? null;
        $columns = $config['columns'] ?? ['*'];
        $orderBy = (array) ($config['order_by'] ?? []);

        return [
            'source' => $source,
            'target' => $target,
            'primary_key' => $primaryKey,
            'chunk' => $chunkSize,
            'mode' => $mode,
            'unique_by' => $uniqueBy,
            'update_columns' => $updateColumns !== null ? array_values((array) $updateColumns) : null,
            'columns' => is_array($columns) ? $columns : [$columns],
            'where' => $config['where'] ?? [],
            'transform' => $config['transform'] ?? null,
            'chunk_by_id' => (bool) ($config['chunk_by_id'] ?? true),
            'truncate' => (bool) ($config['truncate'] ?? false),
            'order_by' => $orderBy,
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    protected function migrateTable(string $sourceConnection, string $targetConnection, array $config, bool $truncate): array
    {
        $sourceQuery = DB::connection($sourceConnection)->table($config['source']);

        if ($config['columns'] !== ['*']) {
            $sourceQuery->select($config['columns']);
        }

        foreach ($config['where'] as $column => $value) {
            if (is_callable($value)) {
                $sourceQuery = $value($sourceQuery);

                continue;
            }

            if (is_array($value) && Arr::isAssoc($value)) {
                $operator = $value['operator'] ?? '=';
                $val = $value['value'] ?? null;
                $sourceQuery->where($column, $operator, $val);

                continue;
            }

            if (is_array($value)) {
                $sourceQuery->whereIn($column, $value);

                continue;
            }

            $sourceQuery->where($column, $value);
        }

        if ($config['order_by'] !== []) {
            foreach ($config['order_by'] as $key => $direction) {
                if (is_int($key)) {
                    $sourceQuery->orderBy($direction);
                } else {
                    $sourceQuery->orderBy($key, $direction);
                }
            }
        } elseif ($config['primary_key'] !== null) {
            $sourceQuery->orderBy($config['primary_key']);
        }

        $stats = [
            'mode' => $config['mode'],
            'rows_read' => 0,
            'rows_written' => 0,
            'chunks' => 0,
            'truncated' => false,
        ];

        $transform = $config['transform'];
        $chunkSize = $config['chunk'];
        $primaryKey = $config['primary_key'];
        $mode = $config['mode'];
        $uniqueBy = $config['unique_by'];
        $updateColumns = $config['update_columns'];
        $targetTable = $config['target'];
        $hasTruncated = false;

        $chunkHandler = function ($rows) use (
            &$stats,
            $transform,
            $truncate,
            &$hasTruncated,
            $targetConnection,
            $targetTable,
            $mode,
            $uniqueBy,
            &$updateColumns
        ): void {
            $stats['chunks']++;

            $rowsArray = collect($rows)
                ->map(function ($row) use ($transform) {
                    $payload = (array) $row;

                    if (is_callable($transform)) {
                        $payload = $transform($payload);
                    }

                    return $payload;
                })
                ->filter(fn ($payload) => is_array($payload) && $payload !== [])
                ->values()
                ->all();

            $stats['rows_read'] += count($rowsArray);

            if ($rowsArray === []) {
                return;
            }

            if ($truncate && ! $hasTruncated) {
                DB::connection($targetConnection)->table($targetTable)->truncate();
                $hasTruncated = true;
                $stats['truncated'] = true;
            }

            DB::connection($targetConnection)->transaction(function () use (
                $rowsArray,
                $mode,
                $targetTable,
                $targetConnection,
                $uniqueBy,
                &$updateColumns
            ): void {
                $builder = DB::connection($targetConnection)->table($targetTable);

                if ($mode === 'upsert') {
                    if ($updateColumns === null) {
                        $updateColumns = array_values(array_diff(array_keys($rowsArray[0]), $uniqueBy));
                    }

                    if ($updateColumns === []) {
                        $builder->insertOrIgnore($rowsArray);

                        return;
                    }

                    $builder->upsert($rowsArray, $uniqueBy, $updateColumns);

                    return;
                }

                if ($mode === 'insert_or_ignore' || $mode === 'insertOrIgnore') {
                    $builder->insertOrIgnore($rowsArray);

                    return;
                }

                $builder->insert($rowsArray);
            });

            $stats['rows_written'] += count($rowsArray);
        };

        if ($config['chunk_by_id'] && $primaryKey !== null) {
            $sourceQuery->chunkById($chunkSize, $chunkHandler, $primaryKey);
        } else {
            $sourceQuery->chunk($chunkSize, $chunkHandler);
        }

        return $stats;
    }
}
