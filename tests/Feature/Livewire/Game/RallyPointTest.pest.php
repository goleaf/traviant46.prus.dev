<?php

declare(strict_types=1);

use App\Enums\Game\MovementOrderStatus;
use App\Livewire\Game\RallyPoint;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['game.movements.cancel_window_seconds' => 900]);

    Route::middleware('web')->group(function (): void {
        if (! Route::has('game.villages.overview')) {
            Route::get('/test-villages-overview', static fn () => '')->name('game.villages.overview');
        }

        if (! Route::has('game.villages.infrastructure')) {
            Route::get('/test-villages-infrastructure', static fn () => '')->name('game.villages.infrastructure');
        }
    });
});

it('exposes outgoing and incoming movements with countdown metadata', function (): void {
    Carbon::setTestNow('2025-03-01 12:00:00');

    $player = User::factory()->create();
    $originVillage = Village::factory()->create(['user_id' => $player->getKey()]);

    $targetOwner = User::factory()->create();
    $targetVillage = Village::factory()->create(['user_id' => $targetOwner->getKey()]);
    $foreignOriginVillage = Village::factory()->create(['user_id' => $targetOwner->getKey()]);

    $outgoing = MovementOrder::query()->create([
        'user_id' => $player->getKey(),
        'origin_village_id' => $originVillage->getKey(),
        'target_village_id' => $targetVillage->getKey(),
        'movement_type' => 'attack',
        'mission' => 'raid',
        'status' => 'in_transit',
        'depart_at' => Carbon::now()->subMinutes(5),
        'arrive_at' => Carbon::now()->addMinutes(10),
        'payload' => ['units' => ['u1' => 50]],
        'metadata' => ['speed' => 'normal'],
    ]);

    $incoming = MovementOrder::query()->create([
        'user_id' => $targetOwner->getKey(),
        'origin_village_id' => $foreignOriginVillage->getKey(),
        'target_village_id' => $originVillage->getKey(),
        'movement_type' => 'reinforcement',
        'mission' => 'support',
        'status' => 'in_transit',
        'depart_at' => Carbon::now()->subMinutes(2),
        'arrive_at' => Carbon::now()->addMinutes(5),
        'payload' => ['units' => ['u2' => 25]],
        'metadata' => ['speed' => 'fast'],
    ]);

    Livewire::actingAs($player)
        ->test(RallyPoint::class, ['village' => $originVillage])
        ->assertSet('outgoing.0.id', $outgoing->getKey())
        ->assertSet('incoming.0.id', $incoming->getKey())
        ->assertSet('outgoing.0.remaining_seconds', 600)
        ->assertSet('outgoing.0.remaining_label', '10:00')
        ->assertSet('incoming.0.remaining_seconds', 300)
        ->assertSet('incoming.0.remaining_label', '5:00')
        ->assertSee($targetVillage->name)
        ->assertSee($foreignOriginVillage->name);

    Carbon::setTestNow();
});

it('cancels eligible outgoing movements within the configured window', function (): void {
    Carbon::setTestNow($now = Carbon::parse('2025-03-01 12:00:00'));

    $player = User::factory()->create();
    $originVillage = Village::factory()->create([
        'user_id' => $player->getKey(),
        'resource_balances' => [
            'wood' => 600,
            'clay' => 350,
            'iron' => 200,
            'crop' => 500,
        ],
    ]);
    $targetVillage = Village::factory()->create();

    $movement = MovementOrder::query()->create([
        'user_id' => $player->getKey(),
        'origin_village_id' => $originVillage->getKey(),
        'target_village_id' => $targetVillage->getKey(),
        'movement_type' => 'attack',
        'mission' => 'raid',
        'status' => 'in_transit',
        'depart_at' => $now->copy()->subSeconds(20),
        'arrive_at' => $now->copy()->addMinutes(15),
        'payload' => [
            'units' => ['u3' => 10],
            'resources' => ['wood' => 45, 'clay' => 30],
        ],
    ]);

    $component = Livewire::actingAs($player)->test(RallyPoint::class, [
        'village' => $originVillage,
    ]);

    $component
        ->call('cancelMovement', $movement->getKey())
        ->assertSet('outgoing.0.status', 'cancelled')
        ->assertSet('outgoing.0.remaining_seconds', 20)
        ->assertSet('outgoing.0.remaining_label', '0:20');

    $movement->refresh();
    $originVillage->refresh();

    expect($movement->status)->toBe(MovementOrderStatus::Cancelled)
        ->and($movement->return_at)->not->toBeNull()
        ->and($movement->return_at?->equalTo($now->copy()->addSeconds(20)))->toBeTrue()
        ->and(data_get($movement->metadata, 'cancellation.elapsed_seconds'))->toBe(20)
        ->and(data_get($movement->metadata, 'cancellation.refunded_resources.wood'))->toBe(45);

    expect($originVillage->resource_balances['wood'] ?? 0)->toBe(645)
        ->and($originVillage->resource_balances['clay'] ?? 0)->toBe(380);

    Carbon::setTestNow();
});

it('prevents cancelling movements outside the configured window', function (): void {
    Carbon::setTestNow('2025-03-01 12:00:00');
    config(['game.movements.cancel_window_seconds' => 30]);

    $player = User::factory()->create();
    $originVillage = Village::factory()->create(['user_id' => $player->getKey()]);
    $targetVillage = Village::factory()->create();

    $movement = MovementOrder::query()->create([
        'user_id' => $player->getKey(),
        'origin_village_id' => $originVillage->getKey(),
        'target_village_id' => $targetVillage->getKey(),
        'movement_type' => 'attack',
        'mission' => 'raid',
        'status' => 'in_transit',
        'depart_at' => Carbon::now()->subMinutes(5),
        'arrive_at' => Carbon::now()->addMinutes(10),
        'payload' => ['units' => ['u3' => 10]],
    ]);

    Livewire::actingAs($player)
        ->test(RallyPoint::class, ['village' => $originVillage])
        ->call('cancelMovement', $movement->getKey())
        ->assertHasErrors('movements');

    $movement->refresh();

    expect($movement->status)->toBe(MovementOrderStatus::InTransit);

    Carbon::setTestNow();
});
