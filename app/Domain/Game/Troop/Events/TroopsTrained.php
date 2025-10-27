<?php

declare(strict_types=1);

namespace App\Domain\Game\Troop\Events;

use App\Domain\Game\Events\PrivateBroadcastDomainEvent;

/**
 * Signals that a troop training queue has produced new units.
 */
class TroopsTrained extends PrivateBroadcastDomainEvent
{
    public const EVENT = 'troops-trained';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
