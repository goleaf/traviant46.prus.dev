<?php

declare(strict_types=1);

return [
    'speed' => (int) env('TRAVIAN_GAME_SPEED', 1),
    'round_length' => (int) env('TRAVIAN_ROUND_LENGTH', 0),
    'world_id' => env('TRAVIAN_WORLD_ID', 's1'),
    'secure_hash_code' => env('TRAVIAN_SECURE_HASH', ''),
    'title' => env('TRAVIAN_WORLD_TITLE', 'Travian'),
    'game_world_url' => env('TRAVIAN_GAME_WORLD_URL', ''),
    'server_name' => env('TRAVIAN_SERVER_NAME', ''),
    'auto_reinstall' => (bool) env('TRAVIAN_AUTO_REINSTALL', false),
    'auto_reinstall_start_after' => (int) env('TRAVIAN_AUTO_REINSTALL_START_AFTER', 0),
    'engine_filename' => env('TRAVIAN_ENGINE_FILENAME', 'engine.php'),
    'database' => [
        'hostname' => env('TRAVIAN_DB_HOST', env('DB_HOST')),
        'username' => env('TRAVIAN_DB_USERNAME', env('DB_USERNAME')),
        'password' => env('TRAVIAN_DB_PASSWORD', env('DB_PASSWORD')),
        'database' => env('TRAVIAN_DB_DATABASE', env('DB_DATABASE')),
        'charset' => env('TRAVIAN_DB_CHARSET', 'utf8mb4'),
    ],
];
