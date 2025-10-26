<?php

declare(strict_types=1);

use Database\Seeders\TroopTypeSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\seed;

beforeEach(function (): void {
    Schema::dropIfExists('troop_types');

    $migration = require database_path('migrations/2025_10_26_223855_create_troop_types_table.php');
    $migration->up();
});

afterEach(function (): void {
    Schema::dropIfExists('troop_types');
});

it('seeds all troop types for each playable tribe', function (): void {
    seed(TroopTypeSeeder::class);

    expect(DB::table('troop_types')->count())->toBe(50);

    foreach ([1, 2, 3, 6, 7] as $tribe) {
        expect(DB::table('troop_types')->where('tribe', $tribe)->count())->toBe(10);
    }

    $legionnaire = DB::table('troop_types')->where('code', 'romans-legionnaire')->first();
    expect($legionnaire)->not->toBeNull();
    expect($legionnaire->tribe)->toBe(1);
    expect($legionnaire->attack)->toBe(40);
    expect(json_decode($legionnaire->train_cost, true))->toMatchArray([
        'wood' => 120,
        'clay' => 100,
        'iron' => 150,
        'crop' => 30,
    ]);

    $nomarch = DB::table('troop_types')->where('code', 'egyptians-nomarch')->first();
    expect($nomarch)->not->toBeNull();
    expect($nomarch->tribe)->toBe(6);
    expect($nomarch->upkeep)->toBe(4);
});
