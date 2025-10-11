<?php

use App\Jobs\ProcessAttackArrival;
use App\Jobs\ProcessEvasion;
use App\Jobs\ProcessReinforcementArrival;
use App\Jobs\ProcessReturnMovement;
use App\Jobs\ProcessSettlerArrival;
use App\Models\Game\UnitMovement;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('marks attack movements as completed with resolution metadata', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-03-01 12:00:00'));

    $movement = UnitMovement::factory()
        ->mission(UnitMovement::MISSION_ATTACK)
        ->create();

    (new ProcessAttackArrival())->handle();

    $movement->refresh();

    expect($movement->status)->toBe(UnitMovement::STATUS_COMPLETED);
    expect(Arr::get($movement->metadata, 'resolution.type'))->toBe(UnitMovement::MISSION_ATTACK);
    expect(Arr::get($movement->metadata, 'resolution.completed_at'))
        ->toBe(Carbon::now()->toIso8601String());
});

it('applies reinforcement payloads to the target village', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-03-01 13:00:00'));

    $origin = Village::factory()->create();
    $target = Village::factory()->create();

    $movement = UnitMovement::factory()
        ->mission(UnitMovement::MISSION_REINFORCEMENT)
        ->fromVillage($origin)
        ->toVillage($target)
        ->withUnits([
            ['unit_type_id' => 1, 'quantity' => 30],
            ['unit_type_id' => 2, 'quantity' => 15],
        ])
        ->create();

    (new ProcessReinforcementArrival())->handle();

    $movement->refresh();

    expect($movement->status)->toBe(UnitMovement::STATUS_COMPLETED);
    expect(VillageUnit::query()->where('village_id', $target->getKey())->sum('quantity'))->toBe(45);
    expect(VillageUnit::query()
        ->where('village_id', $target->getKey())
        ->where('unit_type_id', 1)
        ->value('quantity'))
        ->toBe(30);
});

it('returns troops to the origin village', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-03-01 14:00:00'));

    $origin = Village::factory()->create();
    $target = Village::factory()->create();

    $movement = UnitMovement::factory()
        ->mission(UnitMovement::MISSION_RETURN)
        ->fromVillage($target)
        ->toVillage($origin)
        ->withUnits([
            ['unit_type_id' => 3, 'quantity' => 20],
        ])
        ->create();

    (new ProcessReturnMovement())->handle();

    $movement->refresh();

    expect($movement->status)->toBe(UnitMovement::STATUS_COMPLETED);
    expect(VillageUnit::query()
        ->where('village_id', $origin->getKey())
        ->where('unit_type_id', 3)
        ->value('quantity'))
        ->toBe(20);
});

it('creates a new village when settlers arrive', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-03-01 15:00:00'));

    $origin = Village::factory()->create();

    $movement = UnitMovement::factory()
        ->mission(UnitMovement::MISSION_SETTLERS)
        ->fromVillage($origin)
        ->withUnits([
            ['unit_type_id' => 10, 'quantity' => 3],
        ])
        ->state(fn () => [
            'payload' => [
                'units' => [
                    ['unit_type_id' => 10, 'quantity' => 3],
                ],
                'settlement' => [
                    'owner_id' => $origin->owner_id,
                    'name' => 'Outpost',
                    'coordinates' => ['x' => 123, 'y' => -45],
                ],
            ],
        ])
        ->create();

    (new ProcessSettlerArrival())->handle();

    $movement->refresh();

    expect($movement->status)->toBe(UnitMovement::STATUS_COMPLETED);
    $settledVillageId = Arr::get($movement->metadata, 'settlement.village_id');
    expect($settledVillageId)->not->toBeNull();

    $settledVillage = Village::query()->find($settledVillageId);
    expect($settledVillage)->not->toBeNull();
    expect($settledVillage->owner_id)->toBe($origin->owner_id);
    expect($settledVillage->name)->toBe('Outpost');
});

it('schedules a return movement after evasion', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-03-01 16:00:00'));
    config(['gameplay.evasion_return_seconds' => 1800]);

    $origin = Village::factory()->create();
    $hideout = Village::factory()->create();

    $movement = UnitMovement::factory()
        ->mission(UnitMovement::MISSION_EVASION)
        ->fromVillage($origin)
        ->toVillage($hideout)
        ->withUnits([
            ['unit_type_id' => 4, 'quantity' => 25],
        ])
        ->create();

    (new ProcessEvasion())->handle();

    $movement->refresh();

    expect($movement->status)->toBe(UnitMovement::STATUS_COMPLETED);

    $returnId = Arr::get($movement->metadata, 'evasion.scheduled_return_id');
    expect($returnId)->not->toBeNull();

    $returnMovement = UnitMovement::query()->find($returnId);
    expect($returnMovement)->not->toBeNull();
    expect($returnMovement->mission)->toBe(UnitMovement::MISSION_RETURN);
    expect($returnMovement->origin_village_id)->toBe($hideout->getKey());
    expect($returnMovement->target_village_id)->toBe($origin->getKey());
    expect($returnMovement->arrives_at)
        ->toEqual(Carbon::now()->addSeconds(1800));
});

