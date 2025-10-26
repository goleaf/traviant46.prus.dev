<?php

declare(strict_types=1);

return [
    'window' => [
        'minutes' => env('MULTIACCOUNT_WINDOW_MINUTES', 1440),
    ],
    'thresholds' => [
        'low' => [
            'unique_accounts' => env('MULTIACCOUNT_THRESHOLD_LOW_ACCOUNTS', 3),
            'occurrences' => env('MULTIACCOUNT_THRESHOLD_LOW_OCCURRENCES', 5),
        ],
        'medium' => [
            'unique_accounts' => env('MULTIACCOUNT_THRESHOLD_MEDIUM_ACCOUNTS', 4),
            'occurrences' => env('MULTIACCOUNT_THRESHOLD_MEDIUM_OCCURRENCES', 8),
        ],
        'high' => [
            'unique_accounts' => env('MULTIACCOUNT_THRESHOLD_HIGH_ACCOUNTS', 5),
            'occurrences' => env('MULTIACCOUNT_THRESHOLD_HIGH_OCCURRENCES', 12),
        ],
        'critical' => [
            'unique_accounts' => env('MULTIACCOUNT_THRESHOLD_CRITICAL_ACCOUNTS', 6),
            'occurrences' => env('MULTIACCOUNT_THRESHOLD_CRITICAL_OCCURRENCES', 18),
        ],
    ],
    'allowlist' => [
        'ip_addresses' => array_values(array_filter(array_map('trim', explode(',', (string) env('MULTIACCOUNT_ALLOWLIST_IPS', ''))))),
        'cidr_ranges' => array_values(array_filter(array_map('trim', explode(',', (string) env('MULTIACCOUNT_ALLOWLIST_CIDRS', ''))))),
        'device_hashes' => array_values(array_filter(array_map('trim', explode(',', (string) env('MULTIACCOUNT_ALLOWLIST_DEVICE_HASHES', ''))))),
    ],
    'vpn_indicators' => [
        'asn_threshold' => (int) env('MULTIACCOUNT_VPN_ASN_THRESHOLD', 20000),
        'user_agent_keywords' => array_values(array_filter(array_map('trim', explode(',', (string) env('MULTIACCOUNT_VPN_KEYWORDS', 'vpn,proxy,tor,privacy,secure'))))),
        'cidr_ranges' => array_values(array_filter(array_map('trim', explode(',', (string) env('MULTIACCOUNT_VPN_CIDRS', ''))))),
    ],
    'notifications' => [
        'webhook_url' => env('MULTIACCOUNT_WEBHOOK_URL'),
        'channels' => array_values(array_filter(array_map('trim', explode(',', (string) env('MULTIACCOUNT_NOTIFICATION_CHANNELS', 'mail'))))),
        'cooldown_minutes' => (int) env('MULTIACCOUNT_NOTIFICATION_COOLDOWN_MINUTES', 60),
    ],
    'pruning' => [
        'login_activity_days' => (int) env('MULTIACCOUNT_PRUNE_LOGIN_ACTIVITY_DAYS', 90),
        'alert_days' => (int) env('MULTIACCOUNT_PRUNE_ALERT_DAYS', 180),
    ],
    'ip_lookup' => [
        'rate_limit' => [
            'attempts' => (int) env('MULTIACCOUNT_IP_LOOKUP_ATTEMPTS', 30),
            'per_minutes' => (int) env('MULTIACCOUNT_IP_LOOKUP_WINDOW_MINUTES', 10),
        ],
        'cache_ttl' => (int) env('MULTIACCOUNT_IP_LOOKUP_CACHE_SECONDS', 86400),
        'provider' => env('MULTIACCOUNT_IP_LOOKUP_PROVIDER', 'ip-api'),
        'api_key' => env('MULTIACCOUNT_IP_LOOKUP_API_KEY'),
    ],
];
