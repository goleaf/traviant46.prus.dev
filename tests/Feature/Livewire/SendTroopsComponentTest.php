<?php

declare(strict_types=1);

use App\Actions\Game\CreateMovementAction;
use App\Livewire\Game\Send as SendComponent;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use App\Models\User;
use App\ValueObjects\Travian\MapSize;
use Database\Seeders\TroopTypeSeeder;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('travian.connection.speed', 1);
    config()->set('travian.settings', [
        'game' => [
            'movement_speed_increase' => 1,
        ],
    ]);
    config()->set('quests.definitions', []);
    config()->set('hashing.driver', 'bcrypt');

    app()->singleton(MapSize::class, fn () => new MapSize(400));

    $this->seed(TroopTypeSeeder::class);
});

it('validates coordinates and requires units before queuing movements', function (): void {
    $user = User::factory()->create();
    $origin = Village::factory()->starter()->create(['user_id' => $user->getKey()]);
    VillageUnit::factory()->for($origin)->create([
        'unit_type_id' => 1,
        'quantity' => 25,
    ]);

    $target = Village::factory()->starter()->create();

    $this->actingAs($user);

    Livewire::test(SendComponent::class, ['village' => $origin])
        ->set('targetX', '9999')
        ->set('targetY', '0')
        ->set('formUnits.1', 5)
        ->call('submit')
        ->assertHasErrors(['targetX' => 'between']);

    Livewire::test(SendComponent::class, ['village' => $origin])
        ->set('targetX', (string) $target->x_coordinate)
        ->set('targetY', (string) $target->y_coordinate)
        ->call('submit')
        ->assertHasErrors('formUnits');
});

it('queues a movement through the action and surfaces confirmation text', function (): void {
    $user = User::factory()->create();
    $origin = Village::factory()->starter()->create(['user_id' => $user->getKey(), 'x_coordinate' => 10, 'y_coordinate' => 10]);
    VillageUnit::factory()->for($origin)->create([
        'unit_type_id' => 1,
        'quantity' => 40,
    ]);

    $target = Village::factory()->starter()->create(['x_coordinate' => 15, 'y_coordinate' => 12]);

    $movement = MovementOrder::query()->create([
        'origin_village_id' => $origin->getKey(),
        'target_village_id' => $target->getKey(),
        'movement_type' => 'troops',
        'mission' => 'attack',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    $actionMock = \Mockery::mock(CreateMovementAction::class);
    $actionMock->shouldReceive('execute')->once()->andReturn($movement);
    $this->app->instance(CreateMovementAction::class, $actionMock);

    $component = Livewire::test(SendComponent::class, ['village' => $origin])
        ->set('targetX', (string) $target->x_coordinate)
        ->set('targetY', (string) $target->y_coordinate)
        ->set('mission', 'attack')
        ->set('formUnits.1', 15);

    $component->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Movement queued')
        ->assertSet('formUnits.1', 0);
});
