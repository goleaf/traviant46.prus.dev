<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum AllianceDiplomacyStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Accepted => __('Accepted'),
            self::Rejected => __('Rejected'),
            self::Cancelled => __('Cancelled'),
        };
    }
}
