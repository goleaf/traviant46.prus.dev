<?php

declare(strict_types=1);

return [
    'hero' => [
        'source' => env('FEATURE_HERO_SOURCE', 'legacy'),
    ],
    'metrics' => [
        'enabled' => env('FEATURE_METRICS_ENABLED', true),
    ],
];
