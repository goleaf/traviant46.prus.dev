<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::disableForeignKeyConstraints();

    foreach (['alliance_members', 'alliance_roles', 'alliances', 'users'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::enableForeignKeyConstraints();

    $migrations = [
        base_path('database/migrations/0001_01_01_000000_create_users_table.php'),
        base_path('database/migrations/2025_10_26_212536_create_alliances_table.php'),
        base_path('database/migrations/2025_10_26_212544_create_alliance_members_table.php'),
    ];

    foreach ($migrations as $migrationFile) {
        $migration = require $migrationFile;
        $migration->up();
    }
});

it('creates the alliances table with expected columns', function () {
    expect(Schema::hasColumns('alliances', [
        'id',
        'name',
        'tag',
        'description',
        'message_of_day',
        'founder_id',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('creates the alliance roles table with expected columns', function () {
    expect(Schema::hasColumns('alliance_roles', [
        'id',
        'alliance_id',
        'name',
        'permissions',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('creates the alliance members table with expected columns', function () {
    expect(Schema::hasColumns('alliance_members', [
        'id',
        'alliance_id',
        'user_id',
        'alliance_role_id',
        'role',
        'joined_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
