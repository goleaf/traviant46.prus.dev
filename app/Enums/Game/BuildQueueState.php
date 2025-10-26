<?php

declare(strict_types=1);

namespace App\Enums\Game;

enum BuildQueueState: string
{
    case Pending = 'pending';
    case Working = 'working';
    case Done = 'done';
}

