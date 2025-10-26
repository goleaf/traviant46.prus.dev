<?php

declare(strict_types=1);

use App\Actions\Game\ResolveCombatAction;
use App\Enums\Game\MovementOrderStatus;
use App\Events\Game\CombatResolved;
use App\Events\Game\TroopsArrived;
use App\Jobs\MovementResolverJob;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('resolves due troop movements and dispatches arrival events', function (): void {
    Carbon::setTestNow($now = Carbon::parse('2025-01-01 00:00:00'));

    $targetVillage = Village::factory()->create();
    $originVillage = Village::factory()->create();

    /** @var MovementOrder $attackMovement */
    $attackMovement = MovementOrder::query()->create([
        'origin_village_id' => $originVillage->getKey(),
        'target_village_id' => $targetVillage->getKey(),
        'movement_type' => 'attack',
        'status' => MovementOrderStatus::InTransit,
        'depart_at' => $now->copy()->subHour(),
        'arrive_at' => $now->copy()->subMinute(),
        'payload' => [
            'units' => ['u1' => 100, 'u2' => 50],
        ],
    ]);

    /** @var MovementOrder $reinforcementMovement */
    $reinforcementMovement = MovementOrder::query()->create([
        'origin_village_id' => $originVillage->getKey(),
        'target_village_id' => $targetVillage->getKey(),
        'movement_type' => 'reinforcement',
        'status' => MovementOrderStatus::InTransit,
        'depart_at' => $now->copy()->subHour(),
        'arrive_at' => $now->copy()->subMinutes(2),
        'payload' => [
            'units' => ['u3' => 75],
        ],
    ]);

    MovementOrder::query()->create([
        'origin_village_id' => $originVillage->getKey(),
        'target_village_id' => $targetVillage->getKey(),
        'movement_type' => 'reinforcement',
        'status' => MovementOrderStatus::InTransit,
        'depart_at' => $now,
        'arrive_at' => $now->copy()->addHour(),
    ]);

    Event::fake([TroopsArrived::class, CombatResolved::class]);

    $combatAction = \Mockery::mock(ResolveCombatAction::class);
    $combatAction
        ->expects('execute')
        ->once()
        ->withArgs(function (Collection $movements) use ($attackMovement): bool {
            expect($movements)->toHaveCount(1);
            expect($movements->first()?->getKey())->toBe($attackMovement->getKey());

            return true;
        })
        ->andReturn([
            'status' => 'resolved',
            'report_ids' => [123],
        ]);

    $job = new MovementResolverJob(chunkSize: 25);
    $job->handle($combatAction);

    $attackMovement->refresh();
    $reinforcementMovement->refresh();

    expect($attackMovement->status)->toBe(MovementOrderStatus::Completed);
    expect($reinforcementMovement->status)->toBe(MovementOrderStatus::Completed);
    expect($reinforcementMovement->metadata['movement_resolution']['type'] ?? null)->toBe('reinforcement');
    expect($reinforcementMovement->metadata['movement_resolution']['units'] ?? [])->toBe(['u3' => 75]);

    Event::assertDispatched(TroopsArrived::class, function (TroopsArrived $event) use ($attackMovement): bool {
        return $event->payload['movement_id'] === $attackMovement->getKey()
            && $event->payload['resolution'] === 'combat';
    });

    Event::assertDispatched(TroopsArrived::class, function (TroopsArrived $event) use ($reinforcementMovement): bool {
        return $event->payload['movement_id'] === $reinforcementMovement->getKey()
            && $event->payload['resolution'] === 'reinforcement';
    });

    Event::assertDispatched(CombatResolved::class, function (CombatResolved $event) use ($targetVillage, $attackMovement): bool {
        return $event->channelName === sprintf('game.village.%d', $targetVillage->getKey())
            && in_array($attackMovement->getKey(), $event->payload['movement_ids'] ?? [], true);
    });

    Carbon::setTestNow();
});
