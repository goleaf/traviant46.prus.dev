<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::dropIfExists('resource_fields');
    Schema::dropIfExists('villages');

    Schema::create('villages', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
    });

    $migration = require base_path('database/migrations/2025_10_26_223333_create_resource_fields_table.php');
    $migration->up();
});

afterEach(function (): void {
    Schema::dropIfExists('resource_fields');
    Schema::dropIfExists('villages');
});

it('creates the resource fields table with the expected columns', function (): void {
    expect(Schema::hasTable('resource_fields'))->toBeTrue();

    expect(Schema::hasColumns('resource_fields', [
        'id',
        'village_id',
        'slot_number',
        'kind',
        'level',
        'production_per_hour_cached',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('enforces unique resource field entries per village kind and slot combination', function (): void {
    $villageId = DB::table('villages')->insertGetId([
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('resource_fields')->insert([
        'village_id' => $villageId,
        'slot_number' => 1,
        'kind' => 'wood',
        'level' => 1,
        'production_per_hour_cached' => 60,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn (): bool => DB::table('resource_fields')->insert([
        'village_id' => $villageId,
        'slot_number' => 1,
        'kind' => 'wood',
        'level' => 2,
        'production_per_hour_cached' => 40,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('allows the same resource kind across different slots for a village', function (): void {
    /** @var int $villageId */
    $villageId = DB::table('villages')->insertGetId([
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('resource_fields')->insert([
        'village_id' => $villageId,
        'slot_number' => 1,
        'kind' => 'clay',
        'level' => 1,
        'production_per_hour_cached' => 50,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $inserted = DB::table('resource_fields')->insert([
        'village_id' => $villageId,
        'slot_number' => 2,
        'kind' => 'clay',
        'level' => 1,
        'production_per_hour_cached' => 55,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($inserted)->toBeTrue();
});
