<?php

$includePath = base_path('_travian/main_script/include/');

return [
    'global_cache_key' => env('TRAVIAN_GLOBAL_CACHE_KEY', get_current_user()),
    'cache_store' => env('TRAVIAN_CACHE_STORE'),
    'world_config_cache_key' => env('TRAVIAN_WORLD_CONFIG_CACHE_KEY', 'travian.world_config'),
    'world_config_ttl' => (int) env('TRAVIAN_WORLD_CONFIG_TTL', 300),
    'paths' => [
        'root' => dirname($includePath) . DIRECTORY_SEPARATOR,
        'include' => $includePath,
        'public_internal' => base_path('_travian/main_script/copyable/public/'),
        'resources' => $includePath . 'resources' . DIRECTORY_SEPARATOR,
        'locale' => $includePath . 'resources' . DIRECTORY_SEPARATOR . 'Translation' . DIRECTORY_SEPARATOR,
        'templates' => $includePath . 'resources' . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR,
    ],
    'task_worker' => [
        'root' => base_path('_travian/TaskWorker/'),
        'include' => base_path('_travian/TaskWorker/include/'),
        'php_binary' => env('TRAVIAN_TASK_WORKER_PHP_BINARY', PHP_BINARY),
    ],
    'payment' => [
        'root' => base_path('_travian/sections/payment/'),
        'include' => base_path('_travian/sections/payment/include/'),
        'locale' => base_path('_travian/sections/payment/include/Locale/'),
    ],
];
