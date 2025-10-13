<?php

return [
    'spawn' => [
        'min_radius' => env('GAME_SPAWN_MIN_RADIUS', 25),
        'max_radius_padding' => env('GAME_SPAWN_MAX_RADIUS_PADDING', 40),
        'retry_limit' => env('GAME_SPAWN_RETRY_LIMIT', 16),
    ],

    'protection' => [
        'basic_hours' => env('GAME_PROTECTION_BASIC_HOURS', 72),
    ],

    'storage' => [
        'multiplier' => env('GAME_STORAGE_MULTIPLIER', 1.0),
    ],

    'hero' => [
        'default_gender' => env('GAME_HERO_DEFAULT_GENDER', 'male'),
    ],
];
