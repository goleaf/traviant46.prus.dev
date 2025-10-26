<?php

declare(strict_types=1);

return [
    'ip' => [
        'retention' => [
            'login_activities' => [
                'retain_plaintext_for_days' => (int) env('PRIVACY_LOGIN_ACTIVITY_RETAIN_DAYS', 30),
                'delete_after_days' => (int) env('PRIVACY_LOGIN_ACTIVITY_DELETE_DAYS', 365),
            ],
            'login_ip_logs' => [
                'retain_plaintext_for_days' => (int) env('PRIVACY_LOGIN_IP_LOG_RETAIN_DAYS', 90),
                'delete_after_days' => (int) env('PRIVACY_LOGIN_IP_LOG_DELETE_DAYS', 730),
            ],
            'multi_account_alerts' => [
                'retain_plaintext_for_days' => (int) env('PRIVACY_MULTI_ACCOUNT_RETAIN_DAYS', 60),
                'delete_after_days' => (int) env('PRIVACY_MULTI_ACCOUNT_DELETE_DAYS', 365),
            ],
        ],
        'anonymization' => [
            'strategy' => env('PRIVACY_IP_STRATEGY', 'hash'), // Supported: hash, truncate
            'hash' => [
                'key' => env('PRIVACY_IP_HASH_KEY', env('APP_KEY')),
                'algo' => env('PRIVACY_IP_HASH_ALGO', 'sha256'),
            ],
            'truncate' => [
                'ipv4_prefix_octets' => (int) env('PRIVACY_IP_TRUNCATE_IPV4_PREFIX', 3),
                'ipv6_prefix_hextets' => (int) env('PRIVACY_IP_TRUNCATE_IPV6_PREFIX', 4),
            ],
        ],
        'batch_size' => (int) env('PRIVACY_IP_BATCH_SIZE', 500),
    ],
    'export' => [
        'storage_disk' => env('PRIVACY_EXPORT_DISK', 'local'),
        'storage_path' => env('PRIVACY_EXPORT_PATH', 'privacy/exports'),
        'expires_after_days' => (int) env('PRIVACY_EXPORT_EXPIRES_DAYS', 14),
        'max_active_requests' => (int) env('PRIVACY_EXPORT_MAX_ACTIVE', 1),
    ],
    'deletion' => [
        'cooldown_days' => (int) env('PRIVACY_DELETION_COOLDOWN_DAYS', 7),
        'grace_minutes' => (int) env('PRIVACY_DELETION_GRACE_MINUTES', 5),
    ],
    'audit' => [
        'retention_days' => (int) env('PRIVACY_AUDIT_RETENTION_DAYS', 365),
    ],
];
