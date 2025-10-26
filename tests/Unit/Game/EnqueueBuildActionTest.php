<?php

declare(strict_types=1);

use App\Actions\Game\EnqueueBuildAction;
use App\Enums\Game\BuildQueueState;
use App\Models\Game\BuildQueue;
use App\Models\Game\Village;
use App\Models\User;
use App\Repositories\Game\BuildingQueueRepository;
use App\Repositories\Game\VillageRepository;
use App\Services\Game\BuildingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! class_exists(VillageRepository::class, false)) {
        eval(<<<'PHP'
            namespace App\Repositories\Game;

            class VillageRepository {}
        PHP);
    }

    if (! class_exists(BuildingQueueRepository::class, false)) {
        eval(<<<'PHP'
            namespace App\Repositories\Game;

            class BuildingQueueRepository {}
        PHP);
    }
});

it('enqueues a building upgrade when requirements are satisfied', function (): void {
    Carbon::setTestNow('2025-05-01 12:00:00');

    $user = User::factory()->create();

    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
        'resource_balances' => [
            'wood' => 5_000,
            'clay' => 5_000,
            'iron' => 5_000,
            'crop' => 5_000,
        ],
        'is_capital' => true,
    ]);

    config()->set('travian.settings.game.speed', 2.0);

    DB::table('buildings')->insert([
        'village_id' => $village->getKey(),
        'building_type' => 15,
        'position' => 1,
        'level' => 5,
    ]);

    DB::table('resource_fields')->insert([
        'village_id' => $village->getKey(),
        'slot_number' => 1,
        'kind' => 'wood',
        'level' => 10,
        'production_per_hour_cached' => 0,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $action = new EnqueueBuildAction(
        new VillageRepository,
        new BuildingQueueRepository,
    );

    $queued = $action->execute($village->fresh(), 5);

    $service = new BuildingService(gameSpeed: 2.0);
    $expectedDuration = $service->calculateBuildTime(5, 1, 5);

    expect($queued->state)->toBe(BuildQueueState::Pending);
    expect($queued->target_level)->toBe(1);
    expect($queued->finishes_at->equalTo(Carbon::now()->addSeconds($expectedDuration)))->toBeTrue();

    $village->refresh();
    $expectedCost = calculateExpectedCost(5, 1, 2.0);

    foreach ($expectedCost as $resource => $amount) {
        expect($village->resource_balances[$resource])->toBe(5_000 - $amount);
    }

    Carbon::setTestNow();
});

it('rejects enqueue when all worker slots are busy', function (): void {
    $user = User::factory()->create();
    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
    ]);

    BuildQueue::create([
        'village_id' => $village->getKey(),
        'building_type' => 15,
        'target_level' => 1,
        'state' => BuildQueueState::Pending,
        'finishes_at' => Carbon::now()->addHour(),
    ]);

    $action = new EnqueueBuildAction(
        new VillageRepository,
        new BuildingQueueRepository,
    );

    expect(fn () => $action->execute($village, 15))
        ->toThrow(RuntimeException::class, 'All worker slots are currently occupied.');
});

/**
 * @return array<string, int>
 */
function calculateExpectedCost(int $buildingType, int $targetLevel, float $worldSpeed): array
{
    /** @var array<int, array<string, mixed>> $definitions */
    $definitions = require base_path('app/Data/buildings.php');
    $definition = $definitions[$buildingType - 1];

    $baseCosts = (array) ($definition['cost'] ?? []);
    $multiplier = (float) ($definition['k'] ?? 1.0);
    $keys = ['wood', 'clay', 'iron', 'crop'];

    $costs = [];
    foreach ($keys as $index => $resource) {
        $base = (float) ($baseCosts[$index] ?? 0);
        $value = round($base * pow($multiplier, $targetLevel - 1) / 5) * 5;

        if ($buildingType === 40) {
            if (($targetLevel === 100 && $index < 3) || $value > 1_000_000) {
                $value = 1_000_000;
            }
        }

        $costs[$resource] = (int) round($value);
    }

    if ($buildingType <= 4 && $targetLevel > 20 && $worldSpeed > 10) {
        foreach ($keys as $resource) {
            $costs[$resource] = (int) round($costs[$resource] * 10);
        }
    }

    return $costs;
}
