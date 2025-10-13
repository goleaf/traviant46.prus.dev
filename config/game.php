<?php

return [
    'start_time' => env('GAME_START_TIME'),

    'maintenance' => [
        'enabled' => (bool) env('GAME_MAINTENANCE_ENABLED', false),
        'allowed_legacy_uids' => [
            0,
            2,
        ],
    ],
];
