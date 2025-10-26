<?php

declare(strict_types=1);

use App\Enums\Game\VillageBuildingUpgradeStatus;
use App\Livewire\Village\BuildingQueue;
use App\Livewire\Village\Overview;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\Game\VillageBuildingUpgrade;
use App\Models\Game\VillageResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays combined production totals on the resource overview', function (): void {
    $user = User::factory()->create();

    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
        'production' => ['wood' => 100, 'clay' => 0, 'iron' => 0, 'crop' => 0],
        'storage' => ['wood' => 1200, 'clay' => 0, 'iron' => 0, 'crop' => 0],
    ]);

    VillageResource::factory()->create([
        'village_id' => $village->getKey(),
        'resource_type' => 'wood',
        'production_per_hour' => 30,
        'storage_capacity' => 800,
        'bonuses' => [
            'percent' => 10,
            'flat' => 5,
        ],
    ]);

    $component = Livewire::actingAs($user)->test(Overview::class, [
        'village' => $village,
    ]);

    $component
        ->assertSet('resources.production.wood', 148.5)
        ->assertSee((string) number_format(148.5, 1));
});

it('refreshes the building queue widget after new upgrades are created', function (): void {
    $user = User::factory()->create();
    $village = Village::factory()->create([
        'user_id' => $user->getKey(),
    ]);

    $buildingType = BuildingType::factory()->create([
        'name' => 'Main Building',
        'gid' => 15,
    ]);

    $component = Livewire::actingAs($user)->test(BuildingQueue::class, [
        'village' => $village,
    ]);

    $component->assertSet('queue.entries', []);

    Carbon::setTestNow('2025-03-01 12:00:00');

    VillageBuildingUpgrade::create([
        'village_id' => $village->getKey(),
        'slot_number' => 25,
        'building_type' => $buildingType->gid,
        'current_level' => 4,
        'target_level' => 5,
        'queue_position' => 1,
        'status' => VillageBuildingUpgradeStatus::Pending,
        'metadata' => [
            'segments' => [
                ['level' => 5, 'duration' => 900],
            ],
        ],
        'queued_at' => Carbon::now(),
        'starts_at' => Carbon::now(),
        'completes_at' => Carbon::now()->addMinutes(15),
    ]);

    $component
        ->call('refreshQueue')
        ->assertSet('queue.entries.0.building_name', 'Main Building')
        ->assertSet('queue.entries.0.queue_position', 1)
        ->assertSet('queue.entries.0.slot', 25);

    Carbon::setTestNow(null);
});

it('prevents invalid sitter sessions from loading the village overview', function (): void {
    $owner = User::factory()->create([
        'sit1_uid' => null,
        'sit2_uid' => null,
    ]);

    $sitter = User::factory()->create();

    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
    ]);

    $guardStub = new class($owner)
    {
        public function __construct(private readonly User $user) {}

        public function user(): User
        {
            return $this->user;
        }

        public function logout(): void
        {
            //
        }
    };

    Auth::shouldReceive('guard')->andReturn($guardStub);

    $this->actingAs($owner)
        ->withSession([
            'auth.acting_as_sitter' => true,
            'auth.sitter_id' => $sitter->getKey(),
        ])
        ->get(route('game.villages.overview', $village))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
