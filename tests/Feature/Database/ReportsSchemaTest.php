<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

it('creates reports table with expected schema', function (): void {
    $connection = 'reports_schema';
    $databasePath = database_path('reports_schema.sqlite');

    if (file_exists($databasePath)) {
        unlink($databasePath);
    }

    touch($databasePath);

    try {
        config()->set("database.connections.{$connection}", [
            'driver' => 'sqlite',
            'url' => null,
            'database' => $databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ]);

        $runMigration = static function (string $path) use ($connection): void {
            artisan('migrate', [
                '--database' => $connection,
                '--path' => $path,
                '--force' => true,
            ]);
        };

        $runMigration('database/migrations/0001_01_01_000000_create_users_table.php');
        $runMigration('database/migrations/2025_10_26_222231_create_worlds_table.php');
        $runMigration('database/migrations/2025_10_26_212645_create_reports_table.php');

        expect(Schema::connection($connection)->hasTable('reports'))->toBeTrue();
        expect(Schema::connection($connection)->hasColumns('reports', [
            'id',
            'world_id',
            'kind',
            'for_user_id',
            'data',
            'created_at',
        ]))->toBeTrue();

        $indexes = collect(DB::connection($connection)->select("PRAGMA index_list('reports')"))
            ->pluck('name')
            ->map(static fn ($name) => (string) $name)
            ->all();

        expect($indexes)->toContain('reports_for_user_id_index');
    } finally {
        DB::purge($connection);

        if (file_exists($databasePath)) {
            unlink($databasePath);
        }
    }
});
