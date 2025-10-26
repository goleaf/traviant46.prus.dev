<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::disableForeignKeyConstraints();
    Schema::dropAllTables();
    Schema::enableForeignKeyConstraints();

    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    (require database_path('migrations/2025_10_26_223642_create_quests_table.php'))->up();
    (require database_path('migrations/2025_10_26_223647_create_quest_progress_table.php'))->up();
});

it('creates quests table schema', function (): void {
    expect(Schema::hasTable('quests'))->toBeTrue();
    expect(Schema::hasColumns('quests', [
        'id',
        'quest_code',
        'title',
        'description',
        'is_repeatable',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('creates quest progress table schema', function (): void {
    expect(Schema::hasTable('quest_progress'))->toBeTrue();
    expect(Schema::hasColumns('quest_progress', [
        'id',
        'user_id',
        'quest_id',
        'state',
        'progress',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
