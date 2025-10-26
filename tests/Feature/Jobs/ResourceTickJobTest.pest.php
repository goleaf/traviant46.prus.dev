<?php

declare(strict_types=1);

use App\Events\Game\ResourcesProduced;
use App\Jobs\ResourceTickJob;
use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use App\Models\Game\World;
use App\Models\Game\WorldOasis;
use App\Services\Game\VillageUpkeepService;
use App\Services\ResourceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    config()->set('game.tick_interval_seconds', 60);
    config()->set('game.shards', 1);

    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => database_path('testing.sqlite'),
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    $databasePath = database_path('testing.sqlite');
    if (! file_exists($databasePath)) {
        touch($databasePath);
    }

    DB::purge();
    DB::reconnect();
    $pdo = DB::connection()->getPdo();
    $pdo->exec('PRAGMA foreign_keys = OFF;');

    if (method_exists($pdo, 'sqliteCreateFunction')) {
        $pdo->sqliteCreateFunction('mod', static function ($dividend, $divisor): float {
            if ($divisor === 0 || $divisor === null) {
                return 0.0;
            }

            return fmod((float) $dividend, (float) $divisor);
        });
    }

    Schema::dropIfExists('oasis_ownerships');
    Schema::dropIfExists('oases');
    Schema::dropIfExists('worlds');
    Schema::dropIfExists('village_resources');
    Schema::dropIfExists('villages');

    Schema::create('worlds', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->float('speed')->default(1.0);
        $table->json('features')->nullable();
        $table->timestamp('starts_at')->nullable();
        $table->string('status', 32)->default('scheduled');
        $table->timestamps();
    });

    Schema::create('villages', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('legacy_kid')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('alliance_id')->nullable();
        $table->unsignedBigInteger('watcher_user_id')->nullable();
        $table->string('name')->nullable();
        $table->integer('x_coordinate')->default(0);
        $table->integer('y_coordinate')->default(0);
        $table->unsignedTinyInteger('terrain_type')->default(1);
        $table->string('village_category')->nullable();
        $table->boolean('is_capital')->default(false);
        $table->boolean('is_wonder_village')->default(false);
        $table->unsignedInteger('population')->default(0);
        $table->unsignedInteger('loyalty')->default(100);
        $table->unsignedInteger('culture_points')->default(0);
        $table->json('resource_balances')->nullable();
        $table->json('storage')->nullable();
        $table->json('production')->nullable();
        $table->json('defense_bonus')->nullable();
        $table->timestamp('founded_at')->nullable();
        $table->timestamp('abandoned_at')->nullable();
        $table->timestamp('last_loyalty_change_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('village_resources', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
        $table->string('resource_type', 16);
        $table->unsignedTinyInteger('level')->default(0);
        $table->unsignedInteger('production_per_hour')->default(0);
        $table->unsignedInteger('storage_capacity')->default(0);
        $table->json('bonuses')->nullable();
        $table->timestamp('last_collected_at')->nullable();
        $table->timestamps();
        $table->unique(['village_id', 'resource_type']);
        $table->index(['resource_type', 'level']);
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
    });

    Schema::create('oasis_ownerships', function (Blueprint $table): void {
        $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
        $table->foreignId('oasis_id')->constrained('oases')->cascadeOnDelete();
        $table->primary(['village_id', 'oasis_id']);
    });

    $pdo->exec('PRAGMA foreign_keys = ON;');
});

it('produces resources using base, building, and oasis bonuses', function (): void {
    Event::fake([ResourcesProduced::class]);

    $village = Village::factory()->create([
        'resource_balances' => [
            'wood' => 100,
            'clay' => 100,
            'iron' => 100,
            'crop' => 100,
        ],
        'production' => [
            'wood' => 40,
            'clay' => 30,
            'iron' => 20,
            'crop' => 10,
        ],
        'storage' => [
            'warehouse' => 250,
            'granary' => 250,
        ],
    ]);

    VillageResource::factory()->for($village)->create([
        'resource_type' => 'wood',
        'production_per_hour' => 60,
        'storage_capacity' => 0,
        'bonuses' => ['oasis' => 25],
    ]);

    VillageResource::factory()->for($village)->create([
        'resource_type' => 'clay',
        'production_per_hour' => 40,
        'storage_capacity' => 0,
        'bonuses' => [],
    ]);

    VillageResource::factory()->for($village)->create([
        'resource_type' => 'iron',
        'production_per_hour' => 15,
        'storage_capacity' => 0,
        'bonuses' => [],
    ]);

    VillageResource::factory()->for($village)->create([
        'resource_type' => 'crop',
        'production_per_hour' => 30,
        'storage_capacity' => 0,
        'bonuses' => ['oasis' => 50],
    ]);

    $job = new ResourceTickJob;
    $job->handle(app(ResourceService::class), app(VillageUpkeepService::class));

    $freshVillage = $village->fresh();

    expect($freshVillage->resource_balances['wood'])->toEqualWithDelta(102.0833, 0.0001)
        ->and($freshVillage->resource_balances['clay'])->toEqualWithDelta(101.1667, 0.0001)
        ->and($freshVillage->resource_balances['iron'])->toEqualWithDelta(100.5833, 0.0001)
        ->and($freshVillage->resource_balances['crop'])->toEqualWithDelta(101.0000, 0.0001);

    $woodResource = $freshVillage->resources->firstWhere('resource_type', 'wood');

    expect($woodResource?->last_collected_at)->not->toBeNull();

    Event::assertDispatched(ResourcesProduced::class, function (ResourcesProduced $event) use ($freshVillage): bool {
        $payload = $event->payload;

        return $event->channelName === sprintf('game.village.%d', $freshVillage->getKey())
            && $payload['village_id'] === $freshVillage->getKey()
            && $payload['produced']['wood'] > 2.0
            && $payload['per_hour']['wood'] === 125.0
            && ($payload['upkeep']['per_hour'] ?? 0) === 0.0;
    });
});

it('applies owned oasis bonuses to resource production', function (): void {
    Event::fake([ResourcesProduced::class]);

    $world = World::factory()->create();

    $village = Village::factory()->create([
        'resource_balances' => [
            'wood' => 100,
            'clay' => 100,
            'iron' => 100,
            'crop' => 100,
        ],
        'production' => [
            'wood' => 40,
            'clay' => 30,
            'iron' => 20,
            'crop' => 10,
        ],
        'storage' => [
            'warehouse' => 250,
            'granary' => 250,
        ],
    ]);

    $woodOasis = WorldOasis::query()->create([
        'world_id' => $world->getKey(),
        'x' => 10,
        'y' => 10,
        'type' => 1,
        'nature_garrison' => [],
        'respawn_at' => null,
    ]);

    $cropOasis = WorldOasis::query()->create([
        'world_id' => $world->getKey(),
        'x' => 11,
        'y' => 11,
        'type' => 4,
        'nature_garrison' => [],
        'respawn_at' => null,
    ]);

    $village->ownedOases()->attach([
        $woodOasis->getKey(),
        $cropOasis->getKey(),
    ]);

    VillageResource::factory()->for($village)->create([
        'resource_type' => 'wood',
        'production_per_hour' => 60,
        'storage_capacity' => 0,
        'bonuses' => [],
    ]);

    VillageResource::factory()->for($village)->create([
        'resource_type' => 'clay',
        'production_per_hour' => 40,
        'storage_capacity' => 0,
        'bonuses' => [],
    ]);

    VillageResource::factory()->for($village)->create([
        'resource_type' => 'iron',
        'production_per_hour' => 15,
        'storage_capacity' => 0,
        'bonuses' => [],
    ]);

    VillageResource::factory()->for($village)->create([
        'resource_type' => 'crop',
        'production_per_hour' => 30,
        'storage_capacity' => 0,
        'bonuses' => [],
    ]);

    $job = new ResourceTickJob;
    $job->handle(app(ResourceService::class), app(VillageUpkeepService::class));

    $freshVillage = $village->fresh();

    expect($freshVillage->resource_balances['wood'])->toEqualWithDelta(102.0833, 0.0001)
        ->and($freshVillage->resource_balances['clay'])->toEqualWithDelta(101.1667, 0.0001)
        ->and($freshVillage->resource_balances['iron'])->toEqualWithDelta(100.5833, 0.0001)
        ->and($freshVillage->resource_balances['crop'])->toEqualWithDelta(100.8333, 0.0001);

    Event::assertDispatched(ResourcesProduced::class, function (ResourcesProduced $event) use ($freshVillage): bool {
        $payload = $event->payload;

        return $payload['village_id'] === $freshVillage->getKey()
            && $payload['per_hour']['wood'] === 125.0
            && $payload['per_hour']['crop'] === 50.0
            && $payload['oasis_bonus']['wood'] === 25.0
            && $payload['oasis_bonus']['crop'] === 10.0;
    });
});

it('caps resources at storage limits and only processes matched shard', function (): void {
    Event::fake([ResourcesProduced::class]);

    config()->set('game.shards', 2);

    $ignoredVillage = Village::factory()->create([
        'resource_balances' => [
            'wood' => 75,
            'clay' => 75,
            'iron' => 75,
            'crop' => 75,
        ],
        'production' => [
            'wood' => 10,
            'clay' => 10,
            'iron' => 10,
            'crop' => 10,
        ],
        'storage' => [
            'warehouse' => 300,
            'granary' => 300,
        ],
    ]);

    $processedVillage = Village::factory()->create([
        'resource_balances' => [
            'wood' => 104.5,
            'clay' => 104.0,
            'iron' => 104.0,
            'crop' => 104.0,
        ],
        'production' => [
            'wood' => 50,
            'clay' => 50,
            'iron' => 50,
            'crop' => 40,
        ],
        'storage' => [
            'warehouse' => 105,
            'granary' => 105,
        ],
    ]);

    foreach (['wood', 'clay', 'iron'] as $resourceType) {
        VillageResource::factory()->for($processedVillage)->create([
            'resource_type' => $resourceType,
            'production_per_hour' => 200,
            'storage_capacity' => 0,
            'bonuses' => [],
        ]);
    }

    VillageResource::factory()->for($processedVillage)->create([
        'resource_type' => 'crop',
        'production_per_hour' => 120,
        'storage_capacity' => 0,
        'bonuses' => [],
    ]);

    $job = new ResourceTickJob(shard: 0);
    $job->handle(app(ResourceService::class), app(VillageUpkeepService::class));

    expect($ignoredVillage->fresh()->resource_balances['wood'])->toEqual(75.0);

    $updatedVillage = $processedVillage->fresh();

    expect($updatedVillage->resource_balances['wood'])->toEqual(105.0)
        ->and($updatedVillage->resource_balances['clay'])->toEqual(105.0)
        ->and($updatedVillage->resource_balances['iron'])->toEqual(105.0)
        ->and($updatedVillage->resource_balances['crop'])->toEqual(105.0);

    Event::assertDispatched(ResourcesProduced::class, function (ResourcesProduced $event) use ($processedVillage): bool {
        return $event->payload['village_id'] === $processedVillage->getKey()
            && $event->payload['had_overflow'] === true
            && $event->payload['overflow']['wood'] > 0;
    });

    Event::assertDispatchedTimes(ResourcesProduced::class, 1);
});

it('emits storage warnings and keeps reservations within capacity', function (): void {
    Event::fake([
        ResourcesProduced::class,
        \App\Events\Game\ResourceStorageWarning::class,
    ]);

    $village = Village::factory()->create([
        'resource_balances' => [
            'wood' => 95.0,
            'clay' => 10.0,
            'iron' => 5.0,
            'crop' => 5.0,
        ],
        'production' => [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ],
        'storage' => [
            'warehouse' => 100,
            'granary' => 100,
            'reservations' => [
                'wood' => 150,
                'clay' => -10,
            ],
        ],
    ]);

    VillageResource::factory()->for($village)->create([
        'resource_type' => 'wood',
        'production_per_hour' => 300,
        'storage_capacity' => 0,
    ]);

    $job = new ResourceTickJob;
    $job->handle(app(ResourceService::class), app(VillageUpkeepService::class));

    $freshVillage = $village->fresh();
    $storage = $freshVillage->storage;

    expect(data_get($storage, 'reservations.wood'))->toEqual(100.0)
        ->and(data_get($storage, 'reservations.clay'))->toBeNull();

    Event::assertDispatched(\App\Events\Game\ResourceStorageWarning::class, function ($event) use ($freshVillage): bool {
        return $event->channelName === sprintf('game.village.%d', $freshVillage->getKey())
            && $event->payload['resource'] === 'wood'
            && $event->payload['percent'] >= 90.0;
    });

    Event::assertDispatched(ResourcesProduced::class, function (ResourcesProduced $event) use ($freshVillage): bool {
        $payload = $event->payload;

        return $payload['village_id'] === $freshVillage->getKey()
            && ($payload['warnings'][0]['resource'] ?? null) === 'wood'
            && data_get($payload, 'reservations.wood') === 100.0;
    });
});
