<?php

declare(strict_types=1);

namespace App\Enums\DailyQuest;

enum RewardThreeType: int
{
    case None = 0;
    case HorseWhip = 1;
    case TrainingSwords = 2;
    case InfantryHelmet = 3;
    case CavalryHelmet = 4;
    case AdventureScroll = 5;
}
