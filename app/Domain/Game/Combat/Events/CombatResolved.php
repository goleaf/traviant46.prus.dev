<?php

declare(strict_types=1);

namespace App\Domain\Game\Combat\Events;

use App\Domain\Game\Events\PrivateBroadcastDomainEvent;

/**
 * Publishes the outcome of a combat resolution to interested clients.
 */
class CombatResolved extends PrivateBroadcastDomainEvent
{
    public const EVENT = 'combat-resolved';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
