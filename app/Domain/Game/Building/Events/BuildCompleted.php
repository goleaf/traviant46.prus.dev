<?php

declare(strict_types=1);

namespace App\Domain\Game\Building\Events;

use App\Domain\Game\Events\PrivateBroadcastDomainEvent;

/**
 * Broadcasts the completion of a queued building upgrade.
 */
class BuildCompleted extends PrivateBroadcastDomainEvent
{
    public const EVENT = 'build-completed';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
