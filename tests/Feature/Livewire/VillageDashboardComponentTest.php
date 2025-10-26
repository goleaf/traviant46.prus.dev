<?php

declare(strict_types=1);

use App\Enums\Game\VillageBuildingUpgradeStatus;
use App\Livewire\Game\VillageDashboard;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\Game\VillageBuildingUpgrade;
use App\Models\Game\VillageResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createVillageWithEconomy(User $user): array
{
    $village = Village::factory()->for($user, 'owner')->create([
        'resource_balances' => [
            'wood' => 1_200,
            'clay' => 1_100,
            'iron' => 980,
            'crop' => 650,
        ],
        'production' => [
            'wood' => 120,
            'clay' => 105,
            'iron' => 96,
            'crop' => 60,
        ],
    ]);

    $lastTick = Carbon::now()->subMinutes(3);

    foreach (['wood', 'clay', 'iron', 'crop'] as $resource) {
        VillageResource::factory()
            ->for($village, 'village')
            ->create([
                'resource_type' => $resource,
                'production_per_hour' => match ($resource) {
                    'wood' => 120,
                    'clay' => 105,
                    'iron' => 96,
                    default => 60,
                },
                'storage_capacity' => $resource === 'crop' ? 3_200 : 6_400,
                'last_collected_at' => $lastTick,
            ]);
    }

    $buildingType = BuildingType::factory()->create([
        'name' => 'Main Building',
    ]);

    $upgrade = VillageBuildingUpgrade::query()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 1,
        'building_type' => $buildingType->gid,
        'current_level' => 5,
        'target_level' => 6,
        'queue_position' => 1,
        'status' => VillageBuildingUpgradeStatus::Processing,
        'metadata' => [
            'segments' => [
                ['level' => 6, 'duration' => 900],
            ],
        ],
        'starts_at' => Carbon::now()->subMinutes(5),
        'completes_at' => Carbon::now()->addMinutes(10),
    ]);

    return [$village->fresh(), $upgrade];
}

it('renders live resource snapshot and queue summary', function (): void {
    Carbon::setTestNow(now());

    $user = User::factory()->create();
    [$village] = createVillageWithEconomy($user);

    $this->actingAs($user);

    Livewire::test(VillageDashboard::class, ['village' => $village->getKey()])
        ->assertSet('balances.wood', 1200.0)
        ->assertSet('production.crop', 60.0)
        ->assertSet('storage.wood', 6400.0)
        ->assertSet('queueSummary.entries.0.target_level', 6)
        ->assertSet('queueSummary.entries.0.building_name', 'Main Building')
        ->assertNotNull('lastTickAt')
        ->assertNotNull('snapshotGeneratedAt');
});

it('updates queue from database when build completion payload is empty', function (): void {
    Carbon::setTestNow(now());

    $user = User::factory()->create();
    [$village, $upgrade] = createVillageWithEconomy($user);

    $this->actingAs($user);

    $component = Livewire::test(VillageDashboard::class, ['village' => $village->getKey()]);

    $upgrade->update([
        'target_level' => 7,
    ]);

    $component
        ->call('handleBuildCompleted', [])
        ->assertSet('queueSummary.entries.0.target_level', 7);
});

it('applies broadcast payload when supplied for resources', function (): void {
    $user = User::factory()->create();
    [$village] = createVillageWithEconomy($user);

    $this->actingAs($user);

    Livewire::test(VillageDashboard::class, ['village' => $village->getKey()])
        ->call('handleResourcesProduced', [
            'balances' => ['wood' => 1800, 'clay' => 1600, 'iron' => 1200, 'crop' => 900],
            'production' => ['wood' => 180, 'clay' => 140, 'iron' => 110, 'crop' => 90],
            'storage' => ['wood' => 10_000, 'clay' => 10_000, 'iron' => 10_000, 'crop' => 6_000],
            'last_tick_at' => now()->toIso8601String(),
            'generated_at' => now()->addSecond()->toIso8601String(),
        ])
        ->assertSet('balances.wood', 1800.0)
        ->assertSet('production.clay', 140.0)
        ->assertSet('storage.crop', 6000.0);
});
