<?php

declare(strict_types=1);

namespace App\Enums\Game;

enum MovementOrderStatus: string
{
    case Pending = 'pending';
    case InTransit = 'in_transit';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
