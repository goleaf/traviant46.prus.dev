<?php

namespace App\Enums\DailyQuest;

enum RewardTwoType: int
{
    case None = 0;
    case PlusAccountDay = 1;
    case LumberProductionBoost = 2;
    case ClayProductionBoost = 3;
    case IronProductionBoost = 4;
    case CropProductionBoost = 5;
}
