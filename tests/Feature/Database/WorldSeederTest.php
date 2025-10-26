<?php

declare(strict_types=1);

use App\Models\Game\World;
use Database\Seeders\WorldSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\seed;

beforeEach(function (): void {
    Schema::dropIfExists('oases');
    Schema::dropIfExists('map_tiles');
    Schema::dropIfExists('worlds');

    Schema::create('worlds', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->unsignedDecimal('speed', 4, 2)->default(1.00);
        $table->json('features')->nullable();
        $table->timestamp('starts_at')->nullable();
        $table->string('status', 32)->default('pending');
        $table->timestamps();
    });

    Schema::create('map_tiles', function (Blueprint $table): void {
        $table->id();
        $table->string('world_id');
        $table->integer('x');
        $table->integer('y');
        $table->unsignedTinyInteger('tile_type');
        $table->string('resource_pattern', 32);
        $table->unsignedTinyInteger('oasis_type')->nullable();
        $table->unique(['world_id', 'x', 'y']);
        $table->index('world_id');
        $table->index('tile_type');
        $table->index('oasis_type');
    });

    Schema::create('oases', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('world_id');
        $table->integer('x');
        $table->integer('y');
        $table->unsignedTinyInteger('type');
        $table->json('nature_garrison')->nullable();
        $table->timestamp('respawn_at')->nullable();
        $table->timestamps();
        $table->unique(['world_id', 'x', 'y']);
        $table->index('world_id');
        $table->index('type');
    });

    Carbon::setTestNow(Carbon::parse('2025-01-01 12:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('seeds the default world with expected attributes and map data', function (): void {
    seed(WorldSeeder::class);

    $world = World::query()->where('name', 'World #1')->first();

    expect($world)->not->toBeNull();
    expect($world->speed)->toBe(1.0);
    expect($world->status)->toBe('active');
    expect($world->starts_at)->not->toBeNull();

    $features = $world->features;

    expect($features)->toBeArray()->toHaveKey('map');

    /** @var array<string, mixed> $map */
    $map = $features['map'];

    expect($map)->toHaveKeys(['identifier', 'bounds', 'total_tiles', 'tile_counts', 'oasis_counts']);

    expect($map['bounds'])->toMatchArray([
        'min_x' => -200,
        'max_x' => 200,
        'min_y' => -200,
        'max_y' => 200,
    ]);

    $worldIdentifier = $map['identifier'];

    $totalTiles = DB::table('map_tiles')
        ->where('world_id', $worldIdentifier)
        ->count();

    expect($totalTiles)->toBe($map['total_tiles']);

    $resourceTotals = DB::table('map_tiles')
        ->select('resource_pattern', DB::raw('count(*) as total'))
        ->where('world_id', $worldIdentifier)
        ->whereNull('oasis_type')
        ->groupBy('resource_pattern')
        ->pluck('total', 'resource_pattern')
        ->all();

    foreach (['4-4-4-6', '9c', '15c'] as $pattern) {
        expect((int) ($resourceTotals[$pattern] ?? 0))
            ->toBe($map['tile_counts'][$pattern]);
    }

    $oasisTiles = DB::table('map_tiles')
        ->where('world_id', $worldIdentifier)
        ->where('resource_pattern', 'oasis')
        ->count();

    expect($oasisTiles)->toBe($map['oasis_counts']['total']);

    $oasisCountsByType = DB::table('oases')
        ->select('type', DB::raw('count(*) as total'))
        ->where('world_id', $world->getKey())
        ->groupBy('type')
        ->pluck('total', 'type')
        ->all();

    $labelByType = [];

    foreach (WorldSeeder::OASIS_PRESETS as $type => $preset) {
        $labelByType[$type] = $preset['label'];
    }

    foreach ($labelByType as $type => $label) {
        expect((int) ($oasisCountsByType[$type] ?? 0))
            ->toBe($map['oasis_counts']['by_type'][$label]);
    }

    foreach (WorldSeeder::OASIS_PRESETS as $type => $preset) {
        $sampleOasis = DB::table('oases')
            ->where('world_id', $world->getKey())
            ->where('type', $type)
            ->first();

        expect($sampleOasis)->not->toBeNull();
        expect($sampleOasis->nature_garrison)->not->toBeNull();
        expect($sampleOasis->respawn_at)->not->toBeNull();

        /** @var array<string, int> $decodedGarrison */
        $decodedGarrison = json_decode($sampleOasis->nature_garrison, true, 512, JSON_THROW_ON_ERROR);

        expect($decodedGarrison)->toMatchArray($preset['garrison']);

        $expectedMinutes = (int) ceil($preset['respawn_minutes'] / max($world->speed, 0.1));
        $expectedRespawn = Carbon::now()->copy()->addMinutes($expectedMinutes)->toDateTimeString();

        expect(Carbon::parse($sampleOasis->respawn_at)->toDateTimeString())
            ->toBe($expectedRespawn);
    }

    $totalResourceTiles = array_sum($map['tile_counts']);

    expect($totalResourceTiles + $map['oasis_counts']['total'])
        ->toBe($map['total_tiles']);
});
