<?php

return [
    'domain' => env('HORIZON_DOMAIN'),

    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => env('HORIZON_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'redis')),

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => ['web'],

    'waits' => [
        env('HORIZON_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'redis')).':provisioning' => 60,
        env('HORIZON_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'redis')).':default' => 60,
    ],

    'environments' => [
        'production' => [
            'provisioning-supervisor' => [
                'connection' => env('HORIZON_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'redis')),
                'queue' => ['provisioning'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 5,
                'tries' => 3,
                'timeout' => 900,
            ],
        ],

        'local' => [
            'local-provisioning' => [
                'connection' => env('HORIZON_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'redis')),
                'queue' => ['provisioning', 'default'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 900,
            ],
        ],
    ],
];
