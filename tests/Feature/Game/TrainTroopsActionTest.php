<?php

declare(strict_types=1);

use App\Actions\Game\TrainTroopsAction;
use App\Models\Game\TrainingQueue;
use App\Models\Game\TroopType;
use App\Models\Game\Village;
use App\Models\User;
use App\Support\Travian\TroopCatalog;
use Database\Seeders\TroopTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seed(TroopTypeSeeder::class);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('queues troop training and deducts resources', function (): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    $owner = User::factory()->create();

    $initialResources = [
        'wood' => 5_000,
        'clay' => 5_000,
        'iron' => 5_000,
        'crop' => 5_000,
    ];

    /** @var Village $village */
    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
        'resource_balances' => $initialResources,
    ]);

    DB::table('buildings')->insert([
        [
            'village_id' => $village->getKey(),
            'building_type' => 19,
            'position' => 1,
            'level' => 5,
        ],
        [
            'village_id' => $village->getKey(),
            'building_type' => 13,
            'position' => 2,
            'level' => 5,
        ],
        [
            'village_id' => $village->getKey(),
            'building_type' => 22,
            'position' => 3,
            'level' => 5,
        ],
    ]);

    /** @var TroopType $troopType */
    $troopType = TroopType::query()
        ->where('code', 'romans-imperian')
        ->firstOrFail();

    $action = app(TrainTroopsAction::class);

    /** @var TrainingQueue $queue */
    $queue = $action->execute($village, $troopType, 5);

    expect($queue->exists)->toBeTrue();
    expect($queue->count)->toBe(5);
    expect($queue->building_ref)->toBe('barracks');

    $catalog = app(TroopCatalog::class);
    $baseTime = $catalog->baseTime($troopType->code);
    $buildingLevel = 5;

    $gameSpeed = (float) config('travian.settings.game.speed', 1);
    $trainingModifier = (float) config('travian.settings.game.extra_training_time_multiplier', 1);
    $speedDivider = max(0.0001, $gameSpeed * $trainingModifier);
    $expectedPerUnit = (int) ceil(max(($baseTime / $speedDivider) * pow(0.9, $buildingLevel - 1), 1));

    $expectedFinish = Carbon::now()->addSeconds($expectedPerUnit * 5);

    expect($queue->finishes_at)->not->toBeNull();
    expect($queue->finishes_at->equalTo($expectedFinish))->toBeTrue();

    $updated = $village->fresh();
    expect($updated)->not->toBeNull();

    $cost = [
        'wood' => 150,
        'clay' => 160,
        'iron' => 210,
        'crop' => 80,
    ];

    foreach ($cost as $resource => $amount) {
        expect($updated->resource_balances[$resource] ?? null)->toBe($initialResources[$resource] - ($amount * 5));
    }
});

it('rejects training when requirements are missing', function (): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    $owner = User::factory()->create();

    /** @var Village $village */
    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
        'resource_balances' => [
            'wood' => 1_000,
            'clay' => 1_000,
            'iron' => 1_000,
            'crop' => 1_000,
        ],
    ]);

    // Only barracks present, academy requirement missing.
    DB::table('buildings')->insert([
        'village_id' => $village->getKey(),
        'building_type' => 19,
        'position' => 1,
        'level' => 1,
    ]);

    /** @var TroopType $troopType */
    $troopType = TroopType::query()
        ->where('code', 'romans-imperian')
        ->firstOrFail();

    $action = app(TrainTroopsAction::class);

    expect(fn () => $action->execute($village, $troopType, 1))
        ->toThrow(\InvalidArgumentException::class);

    expect(TrainingQueue::query()->count())->toBe(0);
});

it('rejects training when resources are insufficient', function (): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    $owner = User::factory()->create();

    /** @var Village $village */
    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
        'resource_balances' => [
            'wood' => 10,
            'clay' => 10,
            'iron' => 10,
            'crop' => 10,
        ],
    ]);

    DB::table('buildings')->insert([
        [
            'village_id' => $village->getKey(),
            'building_type' => 19,
            'position' => 1,
            'level' => 5,
        ],
        [
            'village_id' => $village->getKey(),
            'building_type' => 13,
            'position' => 2,
            'level' => 5,
        ],
        [
            'village_id' => $village->getKey(),
            'building_type' => 22,
            'position' => 3,
            'level' => 5,
        ],
    ]);

    /** @var TroopType $troopType */
    $troopType = TroopType::query()
        ->where('code', 'romans-imperian')
        ->firstOrFail();

    $action = app(TrainTroopsAction::class);

    expect(fn () => $action->execute($village, $troopType, 1))
        ->toThrow(\InvalidArgumentException::class);
});
