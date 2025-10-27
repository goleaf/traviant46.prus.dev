<?php

declare(strict_types=1);

use App\Jobs\OasisRespawnJob;
use App\Models\Game\WorldOasis;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => database_path('testing.sqlite'),
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    config()->set('cache.default', 'array');
    config()->set('oasis', [
        'default_respawn_minutes' => 360,
        'presets' => [
            1 => [
                'respawn_minutes' => 180,
                'garrison' => [
                    'rat' => 12,
                    'spider' => 6,
                ],
            ],
            2 => [
                'respawn_minutes' => 240,
                'garrison' => [
                    'rat' => 10,
                ],
            ],
        ],
    ]);

    $databasePath = database_path('testing.sqlite');
    if (! file_exists($databasePath)) {
        touch($databasePath);
    }

    DB::purge();
    DB::reconnect();
    DB::connection()->getPdo()->exec('PRAGMA foreign_keys = ON;');

    Schema::dropIfExists('oases');
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

    Schema::create('oases', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
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
});

it('repopulates due oases and schedules the next respawn window', function (): void {
    $now = Carbon::parse('2025-01-15 12:00:00');
    Carbon::setTestNow($now);

    $worldId = DB::table('worlds')->insertGetId([
        'name' => 'World #1',
        'speed' => 2.0,
        'features' => json_encode([]),
        'starts_at' => $now->copy()->subDay(),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $dueOasis = WorldOasis::query()->create([
        'world_id' => $worldId,
        'x' => 10,
        'y' => -5,
        'type' => 1,
        'nature_garrison' => null,
        'respawn_at' => $now->copy()->subMinutes(30),
    ]);

    $futureOasis = WorldOasis::query()->create([
        'world_id' => $worldId,
        'x' => -4,
        'y' => 7,
        'type' => 2,
        'nature_garrison' => ['existing' => 3],
        'respawn_at' => $now->copy()->addMinutes(90),
    ]);

    $unscheduledOasis = WorldOasis::query()->create([
        'world_id' => $worldId,
        'x' => 3,
        'y' => 11,
        'type' => 3,
        'nature_garrison' => null,
        'respawn_at' => null,
    ]);

    $respawnInterval = 180;
    $job = new OasisRespawnJob(chunkSize: 10, fallbackRespawnMinutes: $respawnInterval);
    $job->handle();

    $expectedGarrison = config('oasis.presets.1.garrison');

    $dueOasis->refresh();
    expect($dueOasis->nature_garrison)->toEqual($expectedGarrison)
        ->and($dueOasis->respawn_at?->equalTo($now->copy()->addMinutes((int) ceil(180 / 2))))->toBeTrue();

    $futureOasis->refresh();
    expect($futureOasis->nature_garrison)->toEqual(['existing' => 3])
        ->and($futureOasis->respawn_at?->equalTo($now->copy()->addMinutes(90)))->toBeTrue();

    $unscheduledOasis->refresh();
    expect($unscheduledOasis->nature_garrison)->toBeNull()
        ->and($unscheduledOasis->respawn_at)->toBeNull();

    Carbon::setTestNow();
});

