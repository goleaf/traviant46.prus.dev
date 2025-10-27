<?php

declare(strict_types=1);

use App\Actions\Game\CreateMovementAction;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use App\Models\User;
use Database\Seeders\TroopTypeSeeder;
use Illuminate\Support\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(TroopTypeSeeder::class);
    config()->set('hashing.driver', 'bcrypt');
});

it('creates a movement order and reduces the dispatched units', function (): void {
    // Arrange a pair of villages with troops ready to march.
    $user = User::factory()->create();
    $origin = Village::factory()->starter()->create([
        'user_id' => $user->getKey(),
        'x_coordinate' => 10,
        'y_coordinate' => -4,
    ]);
    $target = Village::factory()->starter()->create([
        'x_coordinate' => 14,
        'y_coordinate' => 1,
    ]);

    $swordsmen = VillageUnit::factory()->for($origin)->create([
        'unit_type_id' => 1,
        'quantity' => 60,
    ]);
    $scouts = VillageUnit::factory()->for($origin)->create([
        'unit_type_id' => 2,
        'quantity' => 10,
    ]);

    $departAt = Carbon::now();
    $arriveAt = $departAt->copy()->addMinutes(30);

    $payload = [
        'units' => [1 => 25, 2 => 5],
        'mission' => 'attack',
        'calculation' => [
            'unit_speed' => 6,
            'slowest_unit_speed' => 6,
            'speed_factor' => 1.0,
            'world_speed_factor' => 1.0,
            'unit_speeds' => ['1' => 6.0, '2' => 9.0],
            'wrap_around' => true,
        ],
        'depart_at' => $departAt,
        'arrive_at' => $arriveAt,
        'metadata' => [
            'preview' => [
                'distance' => 12.5,
                'duration_seconds' => 1800,
                'arrive_at' => $arriveAt->toIso8601String(),
                'upkeep' => 35,
            ],
        ],
        'user_id' => $user->getKey(),
    ];

    $movement = app(CreateMovementAction::class)->execute($origin, $target, 'troops', $payload);

    // Assert that the queued movement references the request and the garrison shrinks accordingly.
    expect($movement)->toBeInstanceOf(MovementOrder::class)
        ->and($movement->mission)->toBe('attack')
        ->and($movement->user_id)->toBe($user->getKey())
        ->and($movement->arrive_at?->toDateTimeString())->toBe($arriveAt->toDateTimeString())
        ->and($movement->payload['units'])->toBe([1 => 25, 2 => 5])
        ->and($movement->metadata['preview']['upkeep'])->toBe(35);

    expect($swordsmen->refresh()->quantity)->toBe(35)
        ->and($scouts->refresh()->quantity)->toBe(5);
});
