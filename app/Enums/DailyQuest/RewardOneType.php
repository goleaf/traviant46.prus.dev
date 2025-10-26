<?php

declare(strict_types=1);

namespace App\Enums\DailyQuest;

enum RewardOneType: int
{
    case None = 0;
    case VillageResourceBundle = 1;
    case HeroExperience = 2;
    case CulturePoints = 3;
    case RandomResourceBundle = 4;
}
