<?php

declare(strict_types=1);

namespace App\Domain\Game\Troop\Events;

use App\Domain\Game\Events\PrivateBroadcastDomainEvent;

/**
 * Notifies subscribers that a new troop movement has been created.
 */
class MovementCreated extends PrivateBroadcastDomainEvent
{
    public const EVENT = 'movement-created';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
