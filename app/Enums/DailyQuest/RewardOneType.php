<?php

namespace App\Enums\DailyQuest;

enum RewardOneType: int
{
    case None = 0;
    case VillageResourceBundle = 1;
    case HeroExperience = 2;
    case CulturePoints = 3;
    case RandomResourceBundle = 4;
}
