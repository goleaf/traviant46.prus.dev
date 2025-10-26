<?php

declare(strict_types=1);

use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use App\Services\Game\MapDistanceService;
use App\ValueObjects\Travian\MapSize;
use Database\Seeders\TroopTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seed(TroopTypeSeeder::class);
    app()->singleton(MapSize::class, fn (): MapSize => new MapSize(400));
    config()->set('travian.settings.game.speed', 2.0);
});

it('profiles distances with unit speeds and world speed factor', function (): void {
    /** @var MapDistanceService $service */
    $service = app(MapDistanceService::class);

    $world = World::factory()->create([
        'speed' => 3.5,
        'features' => ['map' => ['wrap_around' => true]],
    ]);

    $user = User::factory()->create([
        'race' => 1,
    ]);

    $origin = Village::factory()->create([
        'user_id' => $user->getKey(),
        'x_coordinate' => 400,
        'y_coordinate' => 0,
    ]);

    $target = Village::factory()->create([
        'x_coordinate' => -399,
        'y_coordinate' => 0,
    ]);

    $origin->setRelation('world', $world);
    $target->setRelation('world', $world);

    $profile = $service->profile($origin, $target, ['u1' => 100, 'u2' => 50]);

    expect($profile)
        ->toHaveKeys(['distance', 'wrap_around', 'unit_speeds', 'slowest_unit_speed', 'world_speed_factor'])
        ->and($profile['distance'])->toBe(2.0)
        ->and($profile['wrap_around'])->toBeTrue()
        ->and($profile['world_speed_factor'])->toBe(3.5)
        ->and($profile['unit_speeds']['u1'] ?? null)->toBe(6.0)
        ->and($profile['unit_speeds']['u2'] ?? null)->toBe(5.0)
        ->and($profile['slowest_unit_speed'])->toBe(5.0);
});

it('supports disabling wrap-around', function (): void {
    /** @var MapDistanceService $service */
    $service = app(MapDistanceService::class);

    $wrapped = $service->distanceBetweenCoordinates(400, 0, -399, 0, wrapAround: true);
    $flat = $service->distanceBetweenCoordinates(400, 0, -399, 0, wrapAround: false);

    expect($wrapped)->toBe(2.0)
        ->and($flat)->toBe(799.0);
});
