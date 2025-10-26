<?php

declare(strict_types=1);

use App\Models\Game\BuildingCatalog;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageResource;
use App\Services\Game\BuildingPrerequisiteResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves unmet prerequisites with summary text', function (): void {
    $resolver = app(BuildingPrerequisiteResolver::class);

    $catalog = BuildingCatalog::make([
        'prerequisites' => [
            ['type' => 'building', 'slug' => 'academy', 'name' => 'Academy', 'level' => 5],
        ],
    ]);

    $result = $resolver->resolve($catalog, [
        'effective_building_levels' => ['academy' => 3],
    ]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['met'])->toBeFalse()
        ->and($result[0]['summary'])->toBe('Requires Academy 5')
        ->and($result[0]['current_level'])->toBe(3)
        ->and($result[0]['required_level'])->toBe(5);
});

it('derives prerequisite state from the village when context is omitted', function (): void {
    $resolver = app(BuildingPrerequisiteResolver::class);

    $owner = \App\Models\User::factory()->create();

    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
    ]);

    $academy = BuildingType::factory()->create([
        'slug' => 'academy',
        'name' => 'Academy',
        'gid' => 22,
    ]);

    VillageBuilding::factory()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 25,
        'building_type' => $academy->gid,
        'buildable_type' => $academy->getMorphClass(),
        'buildable_id' => $academy->getKey(),
        'level' => 6,
    ]);

    VillageResource::factory()->create([
        'village_id' => $village->getKey(),
        'resource_type' => 'crop',
        'level' => 8,
    ]);

    $catalog = BuildingCatalog::make([
        'prerequisites' => [
            ['type' => 'building', 'slug' => 'academy', 'name' => 'Academy', 'level' => 5],
            ['type' => 'resource-field', 'slug' => 'cropland', 'name' => 'Cropland', 'level' => 6],
        ],
    ]);

    $result = $resolver->resolveForVillage($village, $catalog);

    expect($result)->toHaveCount(2)
        ->and($result[0]['met'])->toBeTrue()
        ->and($result[0]['summary'])->toBe('Requires Academy 5')
        ->and($result[0]['current_level'])->toBe(6)
        ->and($result[1]['met'])->toBeTrue()
        ->and($result[1]['summary'])->toBe('Requires Cropland 6')
        ->and($result[1]['current_level'])->toBe(8);
});
