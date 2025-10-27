<?php

declare(strict_types=1);

use App\Jobs\OasisRespawnJob;
use App\Models\Game\World;
use App\Models\Game\WorldOasis;
use App\Support\Game\OasisPresetRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
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
        $table->foreignIdFor(World::class, 'world_id');
        $table->integer('x');
        $table->integer('y');
        $table->unsignedTinyInteger('type');
        $table->json('nature_garrison')->nullable();
        $table->timestamp('respawn_at')->nullable();
        $table->timestamps();
    });

    Carbon::setTestNow(Carbon::parse('2025-01-02 06:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('respawns oases using the configured garrison and proportional timers', function (): void {
    $world = World::query()->create([
        'name' => 'Speed World',
        'speed' => 1.5,
        'features' => [],
        'starts_at' => now(),
        'status' => 'active',
    ]);

    $type = 4;
    $expectedGarrison = OasisPresetRepository::garrisonForType($type);

    $oasis = WorldOasis::query()->create([
        'world_id' => $world->getKey(),
        'x' => 10,
        'y' => -5,
        'type' => $type,
        'nature_garrison' => [],
        'respawn_at' => now()->subMinutes(5),
    ]);

    $job = new OasisRespawnJob(chunkSize: 5);
    $job->handle();

    $freshOasis = $oasis->fresh();

    expect($freshOasis)->not->toBeNull();
    expect($freshOasis->nature_garrison)->toMatchArray($expectedGarrison);

    $respawnMinutes = OasisPresetRepository::respawnMinutesForType($type) ?? 0;
    $expectedDelay = (int) ceil($respawnMinutes / max($world->speed, 0.1));

    expect($freshOasis->respawn_at?->toDateTimeString())
        ->toBe(now()->copy()->addMinutes($expectedDelay)->toDateTimeString());
});

