<?php

declare(strict_types=1);

namespace App\Domain\Game\Troop\Events;

use App\Domain\Game\Events\PrivateBroadcastDomainEvent;

/**
 * Announces that a troop movement has arrived at its destination.
 */
class TroopsArrived extends PrivateBroadcastDomainEvent
{
    public const EVENT = 'troops-arrived';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
