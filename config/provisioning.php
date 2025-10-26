<?php

declare(strict_types=1);

return [
    'queue' => env('PROVISIONING_QUEUE', 'provisioning'),

    'infrastructure' => [
        'base_url' => env('PROVISIONING_API_BASE_URL'),
        'token' => env('PROVISIONING_API_TOKEN'),
        'timeout' => (int) env('PROVISIONING_API_TIMEOUT', 30),
    ],
];
