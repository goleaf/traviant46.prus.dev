<?php

return [
    'connections' => [
        'legacy' => env('MAINTENANCE_LEGACY_CONNECTION', 'legacy'),
        'primary' => env('MAINTENANCE_PRIMARY_CONNECTION', config('database.default')),
    ],

    'inactive_players' => [
        'batch' => env('MAINTENANCE_INACTIVE_BATCH', 25),
        'deletion_grace_seconds' => env('MAINTENANCE_INACTIVE_GRACE', 12 * 3600),
        'thresholds' => [
            ['max_population' => 10, 'inactive_seconds' => 7 * 86400],
            ['max_population' => 100, 'inactive_seconds' => 10 * 86400],
            ['max_population' => 250, 'inactive_seconds' => 14 * 86400],
            ['max_population' => 500, 'inactive_seconds' => 15 * 86400],
            ['max_population' => 1000, 'inactive_seconds' => 21 * 86400],
            ['max_population' => null, 'inactive_seconds' => 35 * 86400],
        ],
    ],

    'reports' => [
        'batch' => env('MAINTENANCE_REPORT_BATCH', 20000),
        'deleted_retention_seconds' => env('MAINTENANCE_REPORT_DELETED_RETENTION', 12 * 3600),
        'retention_seconds' => env('MAINTENANCE_REPORT_RETENTION', 21600),
        'remove_low_losses' => env('MAINTENANCE_REPORT_REMOVE_LOW_LOSSES', true),
        'low_loss_types' => env('MAINTENANCE_REPORT_LOW_LOSS_TYPES', [1, 4]),
        'low_loss_grace_seconds' => env('MAINTENANCE_REPORT_LOW_LOSS_GRACE', 600),
        'loss_threshold_percent' => env('MAINTENANCE_REPORT_LOSS_THRESHOLD_PERCENT', 30),
    ],

    'messages' => [
        'batch' => env('MAINTENANCE_MESSAGE_BATCH', 5000),
        'retention_seconds' => env('MAINTENANCE_MESSAGE_RETENTION', 2 * 86400),
    ],

    'backup' => [
        'connection' => env('MAINTENANCE_BACKUP_CONNECTION', config('database.default')),
        'directory' => env('MAINTENANCE_BACKUP_DIRECTORY', storage_path('app/backups')),
    ],
];
