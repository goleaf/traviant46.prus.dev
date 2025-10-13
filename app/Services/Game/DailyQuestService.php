<?php

namespace App\Services\Game;

use App\Models\Game\DailyQuestProgress;

class DailyQuestService
{
    public function createForUser(int $userId): DailyQuestProgress
    {
        return DailyQuestProgress::firstOrCreate(
            ['uid' => $userId],
            ['lastDailyQuestReset' => now()->timestamp]
        );
    }
}
