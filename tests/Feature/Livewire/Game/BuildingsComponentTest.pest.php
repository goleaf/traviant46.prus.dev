<?php

declare(strict_types=1);

use App\Livewire\Game\Buildings;
use App\Models\Game\BuildingCatalog;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('surfaces unmet prerequisites as descriptive text', function (): void {
    $user = User::factory()->create();

    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
    ]);

    $mainBuilding = BuildingType::factory()->create([
        'slug' => 'main-building',
        'name' => 'Main Building',
        'gid' => 15,
    ]);

    $grainMill = BuildingType::factory()->create([
        'slug' => 'grain-mill',
        'name' => 'Grain Mill',
        'gid' => 8,
    ]);

    $bakery = BuildingType::factory()->create([
        'slug' => 'bakery',
        'name' => 'Bakery',
        'gid' => 9,
    ]);

    VillageBuilding::factory()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 20,
        'building_type' => $mainBuilding->gid,
        'buildable_type' => $mainBuilding->getMorphClass(),
        'buildable_id' => $mainBuilding->getKey(),
        'level' => 5,
    ]);

    VillageBuilding::factory()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 21,
        'building_type' => $grainMill->gid,
        'buildable_type' => $grainMill->getMorphClass(),
        'buildable_id' => $grainMill->getKey(),
        'level' => 4,
    ]);

    VillageBuilding::factory()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 22,
        'building_type' => $bakery->gid,
        'buildable_type' => $bakery->getMorphClass(),
        'buildable_id' => $bakery->getKey(),
        'level' => 2,
    ]);

    VillageResource::factory()->create([
        'village_id' => $village->getKey(),
        'resource_type' => 'crop',
        'level' => 7,
    ]);

    BuildingCatalog::query()->create([
        'building_type_id' => $bakery->getKey(),
        'building_slug' => 'bakery',
        'name' => 'Bakery',
        'prerequisites' => [
            ['type' => 'resource-field', 'slug' => 'cropland', 'name' => 'Cropland', 'level' => 10],
            ['type' => 'building', 'slug' => 'grain-mill', 'name' => 'Grain Mill', 'level' => 5],
            ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
        ],
    ]);

    $component = Livewire::actingAs($user)->test(Buildings::class, [
        'village' => $village,
    ]);

    $buildings = collect($component->get('buildings'));

    $bakerySnapshot = $buildings->firstWhere('slug', 'bakery');

    expect($bakerySnapshot)->not->toBeNull();
    expect($bakerySnapshot['disabled_reason'] ?? null)->toBe('Requires Cropland 10, Requires Grain Mill 5');
    expect(collect($bakerySnapshot['prerequisites'] ?? [])->pluck('summary'))
        ->toContain('Requires Grain Mill 5')
        ->toContain('Requires Cropland 10');

    $component->assertSee('Requires Cropland 10');
    $component->assertSee('Requires Grain Mill 5');
});
