<?php

declare(strict_types=1);

use App\Domain\Game\Building\Events\BuildCompleted;
use App\Domain\Game\Troop\Events\TroopsTrained;
use App\Enums\Game\BuildQueueState;
use App\Jobs\Shard\QueueCompleterJob;
use App\Models\Game\BuildQueue;
use App\Models\Game\TrainingQueue;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use App\Support\ShardResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('completes due build queues for the assigned shard', function (): void {
    Event::fake([BuildCompleted::class]);

    config()->set('game.shards', 3);
    app()->forgetInstance(ShardResolver::class);

    $village = Village::factory()->create();
    $shard = app(ShardResolver::class)->resolveForVillage($village->getKey());

    DB::table('buildings')->insert([
        'village_id' => $village->getKey(),
        'building_type' => 19,
        'position' => 1,
        'level' => 2,
    ]);

    $queue = BuildQueue::query()->create([
        'village_id' => $village->getKey(),
        'building_type' => 19,
        'target_level' => 4,
        'finishes_at' => Carbon::now()->subMinute(),
        'state' => BuildQueueState::Pending,
    ]);

    $job = new QueueCompleterJob(chunkSize: 10, shard: $shard);
    $job->handle();

    $queue->refresh();

    expect($queue->state)->toBe(BuildQueueState::Done);

    $level = DB::table('buildings')
        ->where('village_id', $village->getKey())
        ->where('building_type', 19)
        ->value('level');

    expect($level)->toBe(4);

    Event::assertDispatched(BuildCompleted::class, function (BuildCompleted $event) use ($queue, $village): bool {
        return $event->channelName === sprintf('game.villages.%d', $village->getKey())
            && $event->payload['queue_id'] === $queue->getKey()
            && $event->payload['target_level'] === 4;
    });
});

it('awards trained troops when a training queue completes', function (): void {
    Event::fake([TroopsTrained::class]);

    config()->set('game.shards', 4);
    app()->forgetInstance(ShardResolver::class);

    $village = Village::factory()->create();
    $shard = app(ShardResolver::class)->resolveForVillage($village->getKey());

    $unit = VillageUnit::query()->create([
        'village_id' => $village->getKey(),
        'unit_type_id' => 1,
        'quantity' => 5,
    ]);

    $queue = TrainingQueue::query()->create([
        'village_id' => $village->getKey(),
        'troop_type_id' => 1,
        'count' => 12,
        'finishes_at' => Carbon::now()->subMinutes(2),
        'building_ref' => 'barracks',
    ]);

    $job = new QueueCompleterJob(chunkSize: 10, shard: $shard);
    $job->handle();

    $unit->refresh();

    expect($unit->quantity)->toBe(17)
        ->and(TrainingQueue::query()->whereKey($queue->getKey())->exists())->toBeFalse();

    Event::assertDispatched(TroopsTrained::class, function (TroopsTrained $event) use ($village): bool {
        return $event->channelName === sprintf('game.villages.%d', $village->getKey())
            && $event->payload['village_id'] === $village->getKey()
            && $event->payload['quantity'] === 12;
    });
});

it('skips queue items that belong to other shards', function (): void {
    Event::fake([BuildCompleted::class]);

    config()->set('game.shards', 5);
    app()->forgetInstance(ShardResolver::class);

    $resolver = app(ShardResolver::class);

    $primaryVillage = Village::factory()->create();
    $primaryShard = $resolver->resolveForVillage($primaryVillage->getKey());

    do {
        $secondaryVillage = Village::factory()->create();
        $secondaryShard = $resolver->resolveForVillage($secondaryVillage->getKey());
    } while ($secondaryShard === $primaryShard);

    DB::table('buildings')->insert([
        'village_id' => $primaryVillage->getKey(),
        'building_type' => 15,
        'position' => 2,
        'level' => 1,
    ]);

    DB::table('buildings')->insert([
        'village_id' => $secondaryVillage->getKey(),
        'building_type' => 15,
        'position' => 3,
        'level' => 1,
    ]);

    $primaryQueue = BuildQueue::query()->create([
        'village_id' => $primaryVillage->getKey(),
        'building_type' => 15,
        'target_level' => 3,
        'finishes_at' => Carbon::now()->subMinute(),
        'state' => BuildQueueState::Pending,
    ]);

    $secondaryQueue = BuildQueue::query()->create([
        'village_id' => $secondaryVillage->getKey(),
        'building_type' => 15,
        'target_level' => 3,
        'finishes_at' => Carbon::now()->subMinute(),
        'state' => BuildQueueState::Pending,
    ]);

    $job = new QueueCompleterJob(chunkSize: 10, shard: $primaryShard);
    $job->handle();

    $primaryQueue->refresh();
    $secondaryQueue->refresh();

    $primaryLevel = DB::table('buildings')
        ->where('village_id', $primaryVillage->getKey())
        ->where('building_type', 15)
        ->value('level');

    $secondaryLevel = DB::table('buildings')
        ->where('village_id', $secondaryVillage->getKey())
        ->where('building_type', 15)
        ->value('level');

    expect($primaryQueue->state)->toBe(BuildQueueState::Done)
        ->and($primaryLevel)->toBe(3)
        ->and($secondaryQueue->state)->toBe(BuildQueueState::Pending)
        ->and($secondaryLevel)->toBe(1);

    Event::assertDispatched(BuildCompleted::class, fn (BuildCompleted $event): bool => $event->payload['queue_id'] === $primaryQueue->getKey());
});
