<?php

declare(strict_types=1);

use App\Models\User;

return [
    'start_time' => env('GAME_START_TIME'),

    'maintenance' => [
        'enabled' => (bool) env('GAME_MAINTENANCE_ENABLED', false),
        'allowed_legacy_uids' => User::reservedLegacyUids(),
    ],

    'communication' => [
        'pagination' => [
            'messages' => 20,
            'reports' => 20,
        ],
        'spam' => [
            'rate_limit_window_minutes' => 10,
            'rate_limit_threshold' => 12,
            'duplicate_window_minutes' => 15,
            'recipient_unread_threshold' => 3,
            'global_unread_threshold' => 10,
        ],
    ],
];
