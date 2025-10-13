<?php

namespace App\Services\Game;

use App\Models\Game\InfoBoxEntry;

class InfoBoxService
{
    public const TYPE_PROTECTION = 6;

    public function addProtectionNotice(int $userId, int $endsAt): void
    {
        InfoBoxEntry::create([
            'uid' => $userId,
            'forAll' => false,
            'type' => self::TYPE_PROTECTION,
            'params' => '',
            'showFrom' => now()->timestamp,
            'showTo' => $endsAt,
            'readStatus' => false,
            'del' => false,
        ]);
    }
}
