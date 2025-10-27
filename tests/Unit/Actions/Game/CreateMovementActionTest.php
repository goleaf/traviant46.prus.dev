<?php

declare(strict_types=1);

use App\Actions\Game\CreateMovementAction;
use App\Domain\Game\Troop\Events\MovementCreated;
use App\Enums\Game\MovementOrderStatus;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\User;
use App\ValueObjects\Travian\MapSize;
use Database\Seeders\TroopTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->singleton(MapSize::class, fn (): MapSize => new MapSize(400));
    config(['travian.settings.game.speed' => 2]);
    seed(TroopTypeSeeder::class);
});

it('creates movement orders with wrapped distance and broadcasts events', function (): void {
    Carbon::setTestNow($now = Carbon::parse('2025-01-01 00:00:00'));

    $user = User::factory()->create(['race' => 1]);

    $origin = Village::factory()->create([
        'user_id' => $user->getKey(),
        'x_coordinate' => 400,
        'y_coordinate' => 0,
    ]);

    $target = Village::factory()->create([
        'x_coordinate' => -399,
        'y_coordinate' => 0,
    ]);

    Event::fake([MovementCreated::class]);

    /** @var CreateMovementAction $action */
    $action = app(CreateMovementAction::class);

    $payload = [
        'units' => ['u1' => 100, 'u2' => 50],
        'unit_speed' => 5,
        'metadata' => ['existing' => 'value'],
    ];

    $movement = $action->execute($origin, $target, 'raid', $payload);

    expect($movement)
        ->toBeInstanceOf(MovementOrder::class)
        ->and($movement->movement_type)->toBe('raid')
        ->and($movement->status)->toBe(MovementOrderStatus::InTransit)
        ->and($movement->payload['units'] ?? [])->toBe($payload['units'])
        ->and($movement->metadata['existing'] ?? null)->toBe('value')
        ->and((float) ($movement->metadata['calculation']['distance'] ?? 0))->toBe(2.0)
        ->and($movement->metadata['calculation']['travel_seconds'] ?? null)->toBe(1)
        ->and((float) ($movement->metadata['calculation']['world_speed_factor'] ?? 0))->toBe(2.0)
        ->and($movement->metadata['calculation']['unit_speeds'] ?? [])->toHaveKeys(['u1', 'u2'])
        ->and((float) ($movement->metadata['calculation']['unit_speeds']['u1'] ?? 0))->toBe(6.0)
        ->and((float) ($movement->metadata['calculation']['unit_speeds']['u2'] ?? 0))->toBe(5.0)
        ->and($movement->metadata['calculation']['wrap_around'] ?? null)->toBeTrue()
        ->and($movement->depart_at?->equalTo($now))->toBeTrue()
        ->and($movement->arrive_at?->equalTo($now->copy()->addSecond()))->toBeTrue();

    Event::assertDispatchedTimes(MovementCreated::class, 2);

    Event::assertDispatched(MovementCreated::class, function (MovementCreated $event) use ($movement, $origin): bool {
        if ($event->channelName !== sprintf('game.village.%d', $origin->getKey())) {
            return false;
        }

        return ($event->payload['movement_id'] ?? null) === $movement->getKey()
            && abs(($event->payload['distance'] ?? 0) - 2.0) < 0.0001
            && ($event->payload['travel_seconds'] ?? null) === 1
            && ($event->payload['status'] ?? null) === MovementOrderStatus::InTransit->value;
    });

    Event::assertDispatched(MovementCreated::class, function (MovementCreated $event) use ($movement): bool {
        if ($event->channelName !== sprintf('game.village.%d', $movement->target_village_id)) {
            return false;
        }

        return ($event->payload['movement_id'] ?? null) === $movement->getKey()
            && ($event->payload['status'] ?? null) === MovementOrderStatus::InTransit->value;
    });

    Carbon::setTestNow();
});
