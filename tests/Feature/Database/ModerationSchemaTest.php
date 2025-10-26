<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::disableForeignKeyConstraints();
    Schema::dropAllTables();
    Schema::enableForeignKeyConstraints();

    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    (require database_path('migrations/2025_10_26_222210_create_map_tiles_table.php'))->up();
});

it('creates bans table with audit metadata', function (): void {
    expect(Schema::hasColumns('bans', [
        'id',
        'user_id',
        'issued_by_user_id',
        'scope',
        'reason',
        'notes',
        'issued_at',
        'expires_at',
        'lifted_at',
        'lifted_by_user_id',
        'lifted_reason',
        'metadata',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('creates mutes table with audit metadata', function (): void {
    expect(Schema::hasColumns('mutes', [
        'id',
        'user_id',
        'muted_by_user_id',
        'scope',
        'reason',
        'notes',
        'muted_at',
        'expires_at',
        'metadata',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('creates multihunter_actions table with audit metadata', function (): void {
    expect(Schema::hasColumns('multihunter_actions', [
        'id',
        'multihunter_id',
        'target_user_id',
        'action',
        'category',
        'reason',
        'notes',
        'performed_at',
        'ip_address',
        'ip_address_hash',
        'metadata',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
