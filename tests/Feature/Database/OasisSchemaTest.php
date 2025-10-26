<?php

declare(strict_types=1);

use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    $database = config('database.default');

    artisan('migrate:fresh', [
        '--database' => $database,
        '--path' => 'database/migrations/0001_01_01_000000_create_users_table.php',
        '--force' => true,
    ]);

    foreach ([
        '2025_03_01_000000_create_villages_table.php',
        '2025_10_26_212609_update_villages_table_structure.php',
        '2025_10_26_222231_create_worlds_table.php',
        '2025_10_26_223658_create_oases_table.php',
        '2025_10_26_223706_create_oasis_ownerships_table.php',
    ] as $migration) {
        artisan('migrate', [
            '--database' => $database,
            '--path' => "database/migrations/{$migration}",
            '--force' => true,
        ]);
    }
});

it('migrates oases table with expected columns', function (): void {
    expect(Schema::hasColumns('oases', [
        'id',
        'world_id',
        'x',
        'y',
        'type',
        'nature_garrison',
        'respawn_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('enforces unique coordinates per world for oases', function (): void {
    $world = World::factory()->create();

    DB::table('oases')->insert([
        'world_id' => $world->id,
        'x' => 12,
        'y' => -7,
        'type' => 3,
        'nature_garrison' => json_encode(['wolves' => 10]),
        'respawn_at' => now()->addDay(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(function () use ($world): void {
        DB::table('oases')->insert([
            'world_id' => $world->id,
            'x' => 12,
            'y' => -7,
            'type' => 3,
            'nature_garrison' => json_encode(['wolves' => 10]),
            'respawn_at' => now()->addDays(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

it('migrates oasis ownerships without surrogate key and prevents duplicates', function (): void {
    $world = World::factory()->create();
    $village = Village::factory()->create();

    $oasisId = DB::table('oases')->insertGetId([
        'world_id' => $world->id,
        'x' => 8,
        'y' => 5,
        'type' => 2,
        'nature_garrison' => json_encode(['rats' => 15]),
        'respawn_at' => now()->addMinutes(30),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(Schema::hasColumn('oasis_ownerships', 'id'))->toBeFalse();

    DB::table('oasis_ownerships')->insert([
        'village_id' => $village->id,
        'oasis_id' => $oasisId,
    ]);

    expect(function () use ($village, $oasisId): void {
        DB::table('oasis_ownerships')->insert([
            'village_id' => $village->id,
            'oasis_id' => $oasisId,
        ]);
    })->toThrow(QueryException::class);
});
