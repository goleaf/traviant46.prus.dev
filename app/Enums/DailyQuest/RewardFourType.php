<?php

declare(strict_types=1);

namespace App\Enums\DailyQuest;

enum RewardFourType: int
{
    case None = 0;
    case CulturePoints = 1;
    case CapitalResourceCache = 2;
    case HeroExperience = 3;
    case CapitalResourceWindfall = 4;
}
