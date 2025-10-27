<?php

declare(strict_types=1);

namespace App\Domain\Game\Report\Events;

use App\Domain\Game\Events\PrivateBroadcastDomainEvent;

/**
 * Signals that a new report is available for a village or player.
 */
class ReportCreated extends PrivateBroadcastDomainEvent
{
    public const EVENT = 'report-created';

    public static function eventName(): string
    {
        return self::EVENT;
    }
}
