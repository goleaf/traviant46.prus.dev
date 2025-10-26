<?php

declare(strict_types=1);

use App\Livewire\Game\Buildings as GameBuildings;
use App\Models\Game\BuildingCatalog;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageBuildingUpgrade;
use App\Models\Game\VillageResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedBuildingCatalogFor(string $slug, BuildingType $buildingType): void
{
    BuildingCatalog::query()->updateOrCreate(
        ['building_slug' => $slug],
        [
            'building_type_id' => $buildingType->getKey(),
            'name' => $buildingType->name,
            'prerequisites' => [
                ['type' => 'resource-field', 'slug' => 'woodcutter', 'name' => 'Woodcutter', 'level' => 10],
                ['type' => 'building', 'slug' => 'main-building', 'name' => 'Main Building', 'level' => 5],
            ],
            'bonuses_per_level' => [
                1 => 5,
                2 => 10,
                3 => 15,
            ],
        ],
    );
}

it('queues an upgrade when catalog prerequisites are satisfied', function (): void {
    $user = User::factory()->create();
    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
        'is_capital' => true,
    ]);

    $mainBuildingType = BuildingType::factory()->create([
        'gid' => 15,
        'slug' => 'main-building',
        'name' => 'Main Building',
        'max_level' => 20,
    ]);

    $sawmillType = BuildingType::factory()->create([
        'gid' => 5,
        'slug' => 'sawmill',
        'name' => 'Sawmill',
        'max_level' => 5,
    ]);

    seedBuildingCatalogFor('sawmill', $sawmillType);

    VillageResource::factory()->create([
        'village_id' => $village->getKey(),
        'resource_type' => 'wood',
        'level' => 12,
    ]);

    VillageBuilding::query()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 1,
        'building_type' => $mainBuildingType->gid,
        'buildable_type' => $mainBuildingType->getMorphClass(),
        'buildable_id' => $mainBuildingType->getKey(),
        'level' => 8,
    ]);

    $sawmill = VillageBuilding::query()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 5,
        'building_type' => $sawmillType->gid,
        'buildable_type' => $sawmillType->getMorphClass(),
        'buildable_id' => $sawmillType->getKey(),
        'level' => 2,
    ]);

    $component = Livewire::actingAs($user)->test(GameBuildings::class, [
        'village' => $village,
    ]);

    $component->assertSee('Next level bonus');

    $rows = collect($component->get('buildings'));
    $sawmillRow = $rows->firstWhere('id', $sawmill->getKey());

    expect($sawmillRow)->not->toBeNull();
    expect($sawmillRow['can_upgrade'])->toBeTrue();

    $component
        ->call('upgrade', $sawmill->getKey())
        ->assertSet('flashMessage.type', 'success');

    $this->assertDatabaseHas('village_building_upgrades', [
        'village_id' => $village->getKey(),
        'village_building_id' => $sawmill->getKey(),
        'target_level' => 3,
    ]);
});

it('disables upgrade when prerequisites are missing', function (): void {
    $user = User::factory()->create();
    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
        'is_capital' => false,
    ]);

    $mainBuildingType = BuildingType::factory()->create([
        'gid' => 15,
        'slug' => 'main-building',
        'name' => 'Main Building',
        'max_level' => 20,
    ]);

    $sawmillType = BuildingType::factory()->create([
        'gid' => 5,
        'slug' => 'sawmill',
        'name' => 'Sawmill',
        'max_level' => 5,
    ]);

    seedBuildingCatalogFor('sawmill', $sawmillType);

    VillageResource::factory()->create([
        'village_id' => $village->getKey(),
        'resource_type' => 'wood',
        'level' => 4,
    ]);

    VillageBuilding::query()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 2,
        'building_type' => $mainBuildingType->gid,
        'buildable_type' => $mainBuildingType->getMorphClass(),
        'buildable_id' => $mainBuildingType->getKey(),
        'level' => 3,
    ]);

    $sawmill = VillageBuilding::query()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 6,
        'building_type' => $sawmillType->gid,
        'buildable_type' => $sawmillType->getMorphClass(),
        'buildable_id' => $sawmillType->getKey(),
        'level' => 1,
    ]);

    $component = Livewire::actingAs($user)->test(GameBuildings::class, [
        'village' => $village,
    ]);

    $rows = collect($component->get('buildings'));
    $sawmillRow = $rows->firstWhere('id', $sawmill->getKey());

    expect($sawmillRow)->not->toBeNull();
    expect($sawmillRow['can_upgrade'])->toBeFalse();

    $component
        ->call('upgrade', $sawmill->getKey())
        ->assertSet('flashMessage.type', 'info');

    expect(VillageBuildingUpgrade::query()->count())->toBe(0);
});
