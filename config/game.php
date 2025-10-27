<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Game runtime configuration values that drive core mechanics and feature toggles.
 *
 * @return array{
 *     world_id_default: string|null,
 *     speed: array<int, int>,
 *     features: array<int, string>,
 *     tick_interval_seconds: int,
 *     shards: int,
 *     start_time: string|null,
 *     maintenance: array<string, mixed>,
 *     communication: array<string, array<string, int|array<string, int>>>,
 *     movements: array<string, int>
 * }
 */
return [
    'world_id_default' => env('GAME_WORLD_ID_DEFAULT'),

    'speed' => [1, 3, 5, 10],

    'features' => [
        'hospital',
        'artifacts',
        'hero',
    ],

    'tick_interval_seconds' => 60,

    'shards' => (int) env('GAME_SHARD_COUNT', 0),

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

    'movements' => [
        'cancel_window_seconds' => (int) env('GAME_MOVEMENT_CANCEL_WINDOW_SECONDS', 60),
    ],
];
