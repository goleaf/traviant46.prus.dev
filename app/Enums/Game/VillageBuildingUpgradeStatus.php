<?php

declare(strict_types=1);

namespace App\Enums\Game;

enum VillageBuildingUpgradeStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
