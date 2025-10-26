<?php

declare(strict_types=1);

namespace App\Enums;

enum MultiAccountAlertStatus: string
{
    case Open = 'open';
    case Suppressed = 'suppressed';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Suppressed => 'Suppressed',
            self::Resolved => 'Resolved',
            self::Dismissed => 'Dismissed',
        };
    }
}
