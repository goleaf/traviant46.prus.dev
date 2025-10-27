<?php

declare(strict_types=1);

use App\Enums\Game\VillageBuildingUpgradeStatus;
use App\Livewire\Game\VillageDashboard;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageBuildingUpgrade;
use App\Models\Game\VillageResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('hydrates resource state from broadcast payloads', function (): void {
    Carbon::setTestNow('2025-03-05 10:00:00');

    /** @var User $user */
    $user = User::factory()->create(['timezone' => 'UTC']);

    /** @var Village $village */
    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
        'resource_balances' => [
            'wood' => 900,
            'clay' => 850,
            'iron' => 800,
            'crop' => 750,
        ],
        'production' => [
            'wood' => 120,
            'clay' => 115,
            'iron' => 110,
            'crop' => 95,
        ],
        'storage' => [
            'warehouse' => 6_000,
            'granary' => 5_000,
            'extra' => ['warehouse' => 0, 'granary' => 0],
        ],
    ]);

    // Ensure each resource has a collection timestamp so the component can derive the last tick time.
    foreach (['wood', 'clay', 'iron', 'crop'] as $resource) {
        VillageResource::factory()->create([
            'village_id' => $village->getKey(),
            'resource_type' => $resource,
            'production_per_hour' => 100,
            'storage_capacity' => 2_000,
            'last_collected_at' => Carbon::now()->subMinutes(5),
        ]);
    }

    $component = Livewire::actingAs($user)->test(VillageDashboard::class, [
        'village' => $village,
    ]);

    $payload = [
        'balances' => ['wood' => 1_250, 'clay' => 980, 'iron' => 840, 'crop' => 710],
        'production' => ['wood' => 140, 'clay' => 120, 'iron' => 111, 'crop' => 101],
        'storage' => ['wood' => 2_400, 'clay' => 2_300, 'iron' => 2_200, 'crop' => 2_100],
        'last_tick_at' => Carbon::now()->toIso8601String(),
        'generated_at' => Carbon::now()->addSecond()->toIso8601String(),
    ];

    $component->call('handleResourcesProduced', $payload)
        ->assertSet('balances.wood', 1_250.0)
        ->assertSet('production.crop', 101.0)
        ->assertSet('storage.iron', 2_200.0)
        ->assertSet('lastTickAt', $payload['last_tick_at'])
        ->assertSet('snapshotGeneratedAt', $payload['generated_at']);

    Carbon::setTestNow();
});

it('replaces queue summary when a build completes', function (): void {
    Carbon::setTestNow('2025-03-05 11:30:00');

    /** @var User $user */
    $user = User::factory()->create(['timezone' => 'UTC']);

    /** @var BuildingType $buildingType */
    $buildingType = BuildingType::factory()->create(['gid' => 15, 'name' => 'Main Building']);

    /** @var Village $village */
    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
        'resource_balances' => ['wood' => 900, 'clay' => 900, 'iron' => 900, 'crop' => 900],
    ]);

    /** @var VillageBuilding $building */
    $building = VillageBuilding::factory()->create([
        'village_id' => $village->getKey(),
        'slot_number' => 1,
        'building_type' => $buildingType->gid,
        'buildable_type' => $buildingType->getMorphClass(),
        'buildable_id' => $buildingType->getKey(),
        'level' => 5,
    ]);

    /** @var VillageBuildingUpgrade $upgrade */
    $upgrade = VillageBuildingUpgrade::query()->create([
        'village_id' => $village->getKey(),
        'village_building_id' => $building->getKey(),
        'slot_number' => 1,
        'building_type' => $buildingType->gid,
        'current_level' => 5,
        'target_level' => 6,
        'queue_position' => 1,
        'status' => VillageBuildingUpgradeStatus::Processing,
        'metadata' => ['segments' => [['level' => 6, 'duration' => 600]]],
        'queued_at' => Carbon::now()->subMinutes(10),
        'starts_at' => Carbon::now()->subMinutes(9),
        'completes_at' => Carbon::now()->addMinutes(1),
        'created_at' => Carbon::now()->subMinutes(10),
        'updated_at' => Carbon::now()->subMinutes(1),
    ]);

    $component = Livewire::actingAs($user)->test(VillageDashboard::class, [
        'village' => $village,
    ]);

    $replacementQueue = [
        'entries' => [
            [
                'id' => $upgrade->getKey(),
                'building_name' => 'Main Building',
                'slot' => 1,
                'current_level' => 5,
                'target_level' => 6,
                'queue_position' => 1,
                'is_active' => true,
                'starts_at' => Carbon::now()->subMinutes(1)->toIso8601String(),
                'completes_at' => Carbon::now()->addMinutes(4)->toIso8601String(),
                'remaining_seconds' => 240,
                'segments' => [['level' => 6, 'duration' => 240]],
            ],
        ],
        'active_entry' => null,
        'next_completion_at' => Carbon::now()->addMinutes(4)->toIso8601String(),
    ];

    $component->call('handleBuildCompleted', ['queue' => $replacementQueue])
        ->assertSet('queueSummary.entries.0.remaining_seconds', 240.0)
        ->assertSet('queueSummary.entries.0.target_level', 6)
        ->assertSet('queueSummary.next_completion_at', $replacementQueue['next_completion_at']);

    Carbon::setTestNow();
});
