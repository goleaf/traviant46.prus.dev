<?php

declare(strict_types=1);

namespace App\Domain\Game\Building\Events;

use App\Domain\Game\Events\PrivateBroadcastDomainEvent;

/**
 * Announces that a building upgrade has been queued for a village.
 */
class BuildQueued extends PrivateBroadcastDomainEvent
{
    public const EVENT = 'build-queued';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
