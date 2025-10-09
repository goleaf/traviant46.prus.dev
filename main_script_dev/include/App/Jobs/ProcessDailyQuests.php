<?php

namespace App\Jobs;

use Model\DailyQuestModel;

class ProcessDailyQuests
{
    public function runAction()
    {
        (new DailyQuestModel())->resetDailyQuest();
    }
}
