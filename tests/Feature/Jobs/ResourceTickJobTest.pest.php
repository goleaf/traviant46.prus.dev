<?php

declare(strict_types=1);

use App\Events\Game\ResourcesProduced;
use App\Jobs\ResourceTickJob;
use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use App\Services\ResourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.tick_interval_seconds', 60);
    config()->set('game.shards', 1);
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

    $job = new ResourceTickJob();
    $job->handle(app(ResourceService::class));

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
            && $payload['per_hour']['wood'] === 125.0;
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

    $job = new ResourceTickJob(shardIndex: 0, shardCount: 2);
    $job->handle(app(ResourceService::class));

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
