<?php

declare(strict_types=1);

use App\Domain\Game\Building\Events\BuildCompleted;
use App\Domain\Game\Building\Events\BuildQueued;
use App\Domain\Game\Combat\Events\CombatResolved;
use App\Domain\Game\Economy\Events\ResourcesProduced;
use App\Domain\Game\Report\Events\ReportCreated;
use App\Domain\Game\Troop\Events\MovementCreated;
use App\Domain\Game\Troop\Events\TroopsArrived;
use App\Domain\Game\Troop\Events\TroopsTrained;
use App\Events\Game\ResourceStorageWarning;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

dataset('game events', [
    ResourcesProduced::class => [ResourcesProduced::class, ResourcesProduced::EVENT],
    ResourceStorageWarning::class => [ResourceStorageWarning::class, ResourceStorageWarning::EVENT],
    BuildQueued::class => [BuildQueued::class, BuildQueued::EVENT],
    BuildCompleted::class => [BuildCompleted::class, BuildCompleted::EVENT],
    TroopsTrained::class => [TroopsTrained::class, TroopsTrained::EVENT],
    MovementCreated::class => [MovementCreated::class, MovementCreated::EVENT],
    TroopsArrived::class => [TroopsArrived::class, TroopsArrived::EVENT],
    CombatResolved::class => [CombatResolved::class, CombatResolved::EVENT],
    ReportCreated::class => [ReportCreated::class, ReportCreated::EVENT],
]);

it('broadcasts game events with minimal payload over private channels', function (string $eventClass, string $eventName): void {
    $channelName = 'game.village.42';
    $payload = ['resource' => 'wood', 'amount' => 120];

    /** @var ShouldBroadcast $event */
    $event = new $eventClass($channelName, $payload);

    expect($event)
        ->toBeInstanceOf(ShouldBroadcast::class)
        ->and($event->broadcastAs())->toBe($eventName)
        ->and($event->broadcastWith())->toBe($payload);

    $channel = $event->broadcastOn();

    expect($channel)
        ->toBeInstanceOf(PrivateChannel::class)
        ->and($channel->name)->toBe("private-{$channelName}");
})->with('game events');
