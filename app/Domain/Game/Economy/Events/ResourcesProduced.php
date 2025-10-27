<?php

declare(strict_types=1);

namespace App\Domain\Game\Economy\Events;

use App\Domain\Game\Events\PrivateBroadcastDomainEvent;

/**
 * Broadcasts resource production updates for a specific village channel.
 */
class ResourcesProduced extends PrivateBroadcastDomainEvent
{
    public const EVENT = 'resources-produced';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
