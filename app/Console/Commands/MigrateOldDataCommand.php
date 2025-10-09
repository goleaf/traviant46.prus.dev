<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MigrateOldDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'legacy:migrate-old-data
        {--old-connection=legacy : Database connection name for the legacy schema}
        {--new-connection=mysql : Database connection name for the target schema}
        {--chunk=1000 : Amount of records to migrate per chunk}
        {--table=* : Limit the migration to the provided table(s)}
        {--dry-run : Simulate the migration without writing to the target schema}';

    /**
     * The console command description.
     */
    protected $description = 'Migrates data from the legacy schema to the new schema using a zero-downtime, chunked strategy.';

    /**
     * Cached instance of the legacy connection.
     */
    protected ConnectionInterface $legacy;

    /**
     * Cached instance of the target connection.
     */
    protected ConnectionInterface $target;

    /**
     * Internal store of successfully migrated tables for validation purposes.
     *
     * @var array<int, array{old_table:string,new_table:string,unique_key:array<int,string>}>
     */
    protected array $migratedTables = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $tableFilter = array_filter((array) $this->option('table'));

        $this->legacy = DB::connection($this->option('old-connection'));
        $this->target = DB::connection($this->option('new-connection'));

        $this->info(sprintf(
            'Starting legacy migration using chunk size %d (%s mode).',
            $chunkSize,
            $dryRun ? 'dry-run' : 'live'
        ));

        $mappings = $this->filterTableMappings($tableFilter);

        if (empty($mappings)) {
            $this->warn('No tables matched the provided filters. Nothing to migrate.');

            return self::SUCCESS;
        }

        foreach ($mappings as $mapping) {
            try {
                $this->migrateTable($mapping, $chunkSize, $dryRun);
            } catch (Throwable $exception) {
                $this->newLine();
                $this->error(sprintf(
                    'Migration halted for %s ➜ %s: %s',
                    $mapping['old_table'],
                    $mapping['new_table'],
                    $exception->getMessage()
                ));

                return self::FAILURE;
            }
        }

        if ($dryRun) {
            $this->warn('Dry-run complete. Validation skipped because no data was written.');

            return self::SUCCESS;
        }

        $this->validateAllDataIntegrity();

        $this->info('Legacy migration finished successfully.');

        return self::SUCCESS;
    }

    /**
     * Perform the migration for a single table mapping.
     *
     * @param array{old_table:string,new_table:string,chunk_key?:string,unique_by?:array<int,string>} $mapping
     */
    protected function migrateTable(array $mapping, int $chunkSize, bool $dryRun): void
    {
        $oldTable = $mapping['old_table'];
        $newTable = $mapping['new_table'];

        if (! $this->tableExists($this->legacy, $oldTable)) {
            $this->warn(sprintf('Skipping %s: legacy table not found.', $oldTable));

            return;
        }

        if (! $this->tableExists($this->target, $newTable)) {
            $this->warn(sprintf('Skipping %s ➜ %s: target table not found.', $oldTable, $newTable));

            return;
        }

        $chunkKey = $mapping['chunk_key'] ?? $this->resolveChunkKey($this->legacy, $oldTable);
        if ($chunkKey === null) {
            $this->warn(sprintf('Skipping %s: unable to determine a chunk key.', $oldTable));

            return;
        }

        $columnMap = $this->resolveColumnMapping($oldTable, $newTable);
        if (empty($columnMap)) {
            $this->warn(sprintf('Skipping %s ➜ %s: no compatible columns to migrate.', $oldTable, $newTable));

            return;
        }

        $uniqueKey = $mapping['unique_by'] ?? $this->resolveUniqueKey($this->target, $newTable);
        if (empty($uniqueKey)) {
            $this->warn(sprintf('Skipping %s ➜ %s: unable to determine a unique key.', $oldTable, $newTable));

            return;
        }

        $this->info(sprintf('Migrating %s ➜ %s (chunk key: %s)', $oldTable, $newTable, implode(', ', $uniqueKey)));

        $lastKey = null;
        $migrated = 0;

        while (true) {
            $rows = $this->fetchChunk($oldTable, $chunkKey, $chunkSize, $lastKey);

            if ($rows->isEmpty()) {
                break;
            }

            $payload = [];
            foreach ($rows as $row) {
                $rowData = (array) $row;
                $payload[] = $this->mapRow($rowData, $columnMap, $newTable);
                $lastKey = $rowData[$chunkKey];
            }

            if (! $dryRun) {
                $this->persistChunk($newTable, $payload, $uniqueKey);
            }

            $migrated += count($payload);
            $this->output->write('.');
        }

        $this->newLine();
        $this->line(sprintf('✓ %d records processed for %s ➜ %s', $migrated, $oldTable, $newTable));

        if (! $dryRun) {
            $this->migratedTables[] = [
                'old_table' => $oldTable,
                'new_table' => $newTable,
                'unique_key' => $uniqueKey,
            ];
        }
    }

    /**
     * Fetch a chunk of legacy rows ordered by the provided key.
     */
    protected function fetchChunk(string $table, string $chunkKey, int $chunkSize, $lastKey): Collection
    {
        $query = $this->legacy->table($table)
            ->orderBy($chunkKey)
            ->limit($chunkSize);

        if ($lastKey !== null) {
            $query->where($chunkKey, '>', $lastKey);
        }

        return $query->get();
    }

    /**
     * Persist the mapped chunk into the target schema using an idempotent strategy.
     */
    protected function persistChunk(string $table, array $payload, array $uniqueKey): void
    {
        if (empty($payload)) {
            return;
        }

        foreach ($payload as $row) {
            $attributes = Arr::only($row, $uniqueKey);
            $values = Arr::except($row, $uniqueKey);

            $this->target->table($table)->updateOrInsert($attributes, $values);
        }
    }

    /**
     * Map a legacy row into the target schema column structure.
     *
     * @param array<string, mixed> $row
     * @param array<string, string> $columnMap
     *
     * @return array<string, mixed>
     */
    protected function mapRow(array $row, array $columnMap, string $newTable): array
    {
        $mapped = [];
        $transformers = $this->getValueTransformers();

        foreach ($columnMap as $newColumn => $oldColumn) {
            $value = $row[$oldColumn] ?? null;

            if (isset($transformers[$newTable][$newColumn])) {
                $value = $transformers[$newTable][$newColumn]($value, $row);
            }

            $mapped[$newColumn] = $value;
        }

        return $mapped;
    }

    /**
     * Determine the relationship between legacy and target columns.
     *
     * @return array<string, string>
     */
    protected function resolveColumnMapping(string $oldTable, string $newTable): array
    {
        $legacyColumns = $this->getTableColumns($this->legacy, $oldTable);
        $targetColumns = $this->getTableColumns($this->target, $newTable);

        if (empty($legacyColumns) || empty($targetColumns)) {
            return [];
        }

        $legacyLookup = [];
        foreach ($legacyColumns as $column) {
            $legacyLookup[$this->normaliseName($column)] = $column;
        }

        $mapping = [];
        foreach ($targetColumns as $column) {
            $normalised = $this->normaliseName($column);
            if (isset($legacyLookup[$normalised])) {
                $mapping[$column] = $legacyLookup[$normalised];
            }
        }

        $overrides = $this->getColumnOverrides();
        if (isset($overrides[$newTable])) {
            foreach ($overrides[$newTable] as $targetColumn => $legacyColumn) {
                if (! in_array($targetColumn, $targetColumns, true)) {
                    continue;
                }

                if (in_array($legacyColumn, $legacyColumns, true)) {
                    $mapping[$targetColumn] = $legacyColumn;
                }
            }
        }

        return $mapping;
    }

    /**
     * Resolve an appropriate chunk key (defaults to the primary key).
     */
    protected function resolveChunkKey(ConnectionInterface $connection, string $table): ?string
    {
        $primaryKey = $this->getPrimaryKey($connection, $table);

        return $primaryKey[0] ?? null;
    }

    /**
     * Resolve the unique key used for idempotent upserts.
     *
     * @return array<int, string>
     */
    protected function resolveUniqueKey(ConnectionInterface $connection, string $table): array
    {
        $primary = $this->getPrimaryKey($connection, $table);

        if (! empty($primary)) {
            return $primary;
        }

        $unique = $connection->select(
            'SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND NON_UNIQUE = 0 AND INDEX_NAME <> "PRIMARY"
             GROUP BY INDEX_NAME
             ORDER BY INDEX_NAME LIMIT 1',
            [$connection->getDatabaseName(), $table]
        );

        if (empty($unique)) {
            return [];
        }

        return explode(',', $unique[0]->columns ?? '') ?: [];
    }

    /**
     * Retrieve the table columns for a connection.
     *
     * @return array<int, string>
     */
    protected function getTableColumns(ConnectionInterface $connection, string $table): array
    {
        $columns = $connection->select(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$connection->getDatabaseName(), $table]
        );

        return array_map(static fn ($column) => $column->COLUMN_NAME, $columns);
    }

    /**
     * Retrieve the primary key columns.
     *
     * @return array<int, string>
     */
    protected function getPrimaryKey(ConnectionInterface $connection, string $table): array
    {
        $keys = $connection->select(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = "PRIMARY"
             ORDER BY ORDINAL_POSITION',
            [$connection->getDatabaseName(), $table]
        );

        return array_map(static fn ($column) => $column->COLUMN_NAME, $keys);
    }

    /**
     * Determine if the provided table exists on the given connection.
     */
    protected function tableExists(ConnectionInterface $connection, string $table): bool
    {
        return $connection->getSchemaBuilder()->hasTable($table);
    }

    /**
     * Filter table mappings according to the provided table filter.
     *
     * @param array<int, string> $tableFilter
     * @return array<int, array{old_table:string,new_table:string,chunk_key?:string,unique_by?:array<int,string>}>
     */
    protected function filterTableMappings(array $tableFilter): array
    {
        $mappings = $this->getTableMappings();

        if (empty($tableFilter)) {
            return $mappings;
        }

        return array_values(array_filter($mappings, function (array $mapping) use ($tableFilter) {
            return in_array($mapping['old_table'], $tableFilter, true)
                || in_array($mapping['new_table'], $tableFilter, true);
        }));
    }

    /**
     * Retrieve the configured table mappings.
     *
     * @return array<int, array{old_table:string,new_table:string,chunk_key?:string,unique_by?:array<int,string>}>
     */
    protected function getTableMappings(): array
    {
        return [
            ['old_table' => 'activation', 'new_table' => 'activation'],
            ['old_table' => 'banIP', 'new_table' => 'ban_ip'],
            ['old_table' => 'bannerShop', 'new_table' => 'banner_shop'],
            ['old_table' => 'clubMedals', 'new_table' => 'club_medals'],
            ['old_table' => 'config', 'new_table' => 'config'],
            ['old_table' => 'configurations', 'new_table' => 'configurations'],
            ['old_table' => 'email_blacklist', 'new_table' => 'email_blacklist'],
            ['old_table' => 'gameServers', 'new_table' => 'game_servers'],
            ['old_table' => 'goldProducts', 'new_table' => 'gold_products'],
            ['old_table' => 'handshakes', 'new_table' => 'login_handshake'],
            ['old_table' => 'infobox', 'new_table' => 'infobox'],
            ['old_table' => 'locations', 'new_table' => 'locations'],
            ['old_table' => 'mailServer', 'new_table' => 'mail_server'],
            ['old_table' => 'news', 'new_table' => 'news'],
            ['old_table' => 'newsletter', 'new_table' => 'newsletter'],
            ['old_table' => 'notifications', 'new_table' => 'notifications'],
            ['old_table' => 'package_codes', 'new_table' => 'package_codes'],
            ['old_table' => 'passwordRecovery', 'new_table' => 'password_recovery'],
            ['old_table' => 'paymentConfig', 'new_table' => 'payment_config'],
            ['old_table' => 'paymentLog', 'new_table' => 'payment_log'],
            ['old_table' => 'paymentProviders', 'new_table' => 'payment_providers'],
            ['old_table' => 'paymentVoucher', 'new_table' => 'payment_voucher'],
            ['old_table' => 'preregistration_keys', 'new_table' => 'preregistration_keys'],
            ['old_table' => 'taskQueue', 'new_table' => 'task_queue'],
            ['old_table' => 'tickets', 'new_table' => 'tickets'],
            ['old_table' => 'transactions', 'new_table' => 'transactions'],
            ['old_table' => 'voting_log', 'new_table' => 'voting_log'],
        ];
    }

    /**
     * Column specific overrides when automatic normalisation does not suffice.
     *
     * @return array<string, array<string, string>>
     */
    protected function getColumnOverrides(): array
    {
        return [
            'activation' => [
                'token' => 'activationCode',
                'created_at' => 'time',
                'updated_at' => 'time',
            ],
            'game_servers' => [
                'url' => 'gameWorldUrl',
                'is_promoted' => 'promoted',
                'is_hidden' => 'hidden',
                'registration_closed' => 'registerClosed',
            ],
            'login_handshake' => [
                'token' => 'token',
            ],
            'mail_server' => [
                'encryption' => 'encryption',
            ],
        ];
    }

    /**
     * Retrieve value transformers for columns requiring additional processing.
     *
     * @return array<string, array<string, callable(mixed, array<string,mixed>):mixed>>
     */
    protected function getValueTransformers(): array
    {
        return [
            'activation' => [
                'created_at' => static function ($value, array $row) {
                    return isset($row['time']) ? (int) $row['time'] : $value;
                },
                'updated_at' => static function ($value, array $row) {
                    return isset($row['time']) ? (int) $row['time'] : $value;
                },
            ],
        ];
    }

    /**
     * Validate row counts, duplicate keys, and foreign key relationships.
     */
    protected function validateAllDataIntegrity(): void
    {
        foreach ($this->migratedTables as $table) {
            $oldCount = $this->legacy->table($table['old_table'])->count();
            $newCount = $this->target->table($table['new_table'])->count();

            if ($oldCount !== $newCount) {
                throw new RuntimeException(sprintf(
                    'Row count mismatch for %s ➜ %s (legacy: %d, target: %d).',
                    $table['old_table'],
                    $table['new_table'],
                    $oldCount,
                    $newCount
                ));
            }

            $duplicateQuery = $this->target->table($table['new_table'])
                ->selectRaw('COUNT(*) as aggregate');

            foreach ($table['unique_key'] as $column) {
                $duplicateQuery->addSelect($column);
                $duplicateQuery->groupBy($column);
            }

            $duplicateCount = $duplicateQuery
                ->having('aggregate', '>', 1)
                ->count();

            if ($duplicateCount > 0) {
                throw new RuntimeException(sprintf(
                    'Duplicate key integrity violation detected on %s.',
                    $table['new_table']
                ));
            }
        }

        $this->validateRelationshipIntegrity();
    }

    /**
     * Ensure migrated tables maintain referential integrity.
     */
    protected function validateRelationshipIntegrity(): void
    {
        $migratedTables = array_column($this->migratedTables, 'new_table');

        if (empty($migratedTables)) {
            return;
        }

        $constraints = $this->target->select(
            'SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$this->target->getDatabaseName()]
        );

        foreach ($constraints as $constraint) {
            $childTable = $constraint->TABLE_NAME;
            $parentTable = $constraint->REFERENCED_TABLE_NAME;

            if (! in_array($childTable, $migratedTables, true) || ! in_array($parentTable, $migratedTables, true)) {
                continue;
            }

            $alias = 'ref_' . Str::snake($parentTable);

            $orphans = $this->target->table($childTable)
                ->leftJoin($parentTable . ' as ' . $alias, $alias . '.' . $constraint->REFERENCED_COLUMN_NAME, '=', $childTable . '.' . $constraint->COLUMN_NAME)
                ->whereNotNull($childTable . '.' . $constraint->COLUMN_NAME)
                ->whereNull($alias . '.' . $constraint->REFERENCED_COLUMN_NAME)
                ->count();

            if ($orphans > 0) {
                throw new RuntimeException(sprintf(
                    'Referential integrity violation detected: %s.%s references missing %s.%s records.',
                    $childTable,
                    $constraint->COLUMN_NAME,
                    $parentTable,
                    $constraint->REFERENCED_COLUMN_NAME
                ));
            }
        }
    }

    /**
     * Normalise a column name for comparison purposes.
     */
    protected function normaliseName(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::snake($name)));
    }
}
