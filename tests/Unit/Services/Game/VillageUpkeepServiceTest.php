<?php

declare(strict_types=1);

use App\Models\Game\ReinforcementGarrison;
use App\Models\Game\TroopType;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use App\Services\Game\VillageUpkeepService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates troop upkeep including reinforcements', function (): void {
    config()->set('game.tick_interval_seconds', 120);

    $village = Village::factory()->create();

    $heavy = TroopType::factory()->create(['upkeep' => 3]);
    $light = TroopType::factory()->create(['upkeep' => 1]);

    VillageUnit::factory()->create([
        'village_id' => $village->getKey(),
        'unit_type_id' => $heavy->getKey(),
        'quantity' => 10,
    ]);

    VillageUnit::factory()->create([
        'village_id' => $village->getKey(),
        'unit_type_id' => $light->getKey(),
        'quantity' => 5,
    ]);

    ReinforcementGarrison::query()->create([
        'stationed_village_id' => $village->getKey(),
        'unit_composition' => ['heavy' => 12],
        'upkeep' => 12,
        'is_active' => true,
    ]);

    $service = app(VillageUpkeepService::class);

    $result = $service->calculate($village->fresh());

    expect($result['per_hour'])->toEqual(47.0)
        ->and($result['per_tick'])->toEqualWithDelta(47.0 / 30, 0.0001)
        ->and($result['sources']['own_units'])->toEqual(35.0)
        ->and($result['sources']['reinforcements'])->toEqual(12.0);
});
