<?php

declare(strict_types=1);

namespace App\Enums\Game;

enum AdventureStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
    case Failed = 'failed';
}
