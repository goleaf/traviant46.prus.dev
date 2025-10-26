<?php

$csv = static function (string $value): array {
    $parts = array_map('trim', explode(',', $value));

    return array_values(array_filter($parts, static fn ($part) => $part !== ''));
};

return [

    /*
    |--------------------------------------------------------------------------
    | Application Key Locking
    |--------------------------------------------------------------------------
    |
    | These options control whether the application key can be regenerated via
    | the artisan command without explicitly forcing the operation. Once the
    | key has been provisioned, locking prevents accidental rotations.
    |
    */

    'app_key' => [
        'locked' => (bool) env('SECURITY_LOCK_APP_KEY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | Configure the password requirements that are enforced for registration,
    | password resets, and other auth flows. These values feed directly into
    | Fortify's validation layer so adjust them per environment as needed.
    |
    */

    'passwords' => [
        'min_length' => (int) env('SECURITY_PASSWORD_MIN_LENGTH', 12),
        'require_letters' => (bool) env('SECURITY_PASSWORD_REQUIRE_LETTERS', true),
        'require_mixed_case' => (bool) env('SECURITY_PASSWORD_REQUIRE_MIXED_CASE', true),
        'require_numbers' => (bool) env('SECURITY_PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => (bool) env('SECURITY_PASSWORD_REQUIRE_SYMBOLS', true),
        'uncompromised' => [
            'enabled' => (bool) env('SECURITY_PASSWORD_UNCOMPROMISED', true),
            'threshold' => (int) env('SECURITY_PASSWORD_UNCOMPROMISED_THRESHOLD', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Centralized rate limit configuration for authentication and recovery
    | workflows. Values are expressed in attempts per decay window with the
    | window defined in minutes unless otherwise noted.
    |
    */

    'rate_limits' => [
        'login' => [
            'max_attempts' => (int) env('SECURITY_RATE_LIMIT_LOGIN_ATTEMPTS', 5),
            'decay_minutes' => (int) env('SECURITY_RATE_LIMIT_LOGIN_DECAY_MINUTES', 1),
        ],
        'two_factor' => [
            'max_attempts' => (int) env('SECURITY_RATE_LIMIT_TWO_FACTOR_ATTEMPTS', 5),
            'decay_minutes' => (int) env('SECURITY_RATE_LIMIT_TWO_FACTOR_DECAY_MINUTES', 1),
        ],
        'password_reset' => [
            'max_attempts' => (int) env('SECURITY_RATE_LIMIT_PASSWORD_RESET_ATTEMPTS', 3),
            'decay_minutes' => (int) env('SECURITY_RATE_LIMIT_PASSWORD_RESET_DECAY_MINUTES', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Retention Windows
    |--------------------------------------------------------------------------
    |
    | Define how long IP related metadata should be retained for compliance
    | and auditing purposes. Application services can rely on these values
    | to align storage retention policies across different environments.
    |
    */

    'ip_retention' => [
        'login_days' => (int) env('SECURITY_IP_RETENTION_LOGIN_DAYS', 90),
        'account_activity_days' => (int) env('SECURITY_IP_RETENTION_ACTIVITY_DAYS', 180),
        'failed_login_days' => (int) env('SECURITY_IP_RETENTION_FAILED_LOGIN_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Configure proxy ranges that should be trusted when resolving client IP
    | addresses. Presets provide quick access to common provider ranges such
    | as Cloudflare. Additional addresses may be specified per environment.
    |
    */

    'trusted_proxies' => [
        'presets' => $csv((string) env('SECURITY_TRUSTED_PROXY_PRESETS', '')),
        'ips' => $csv((string) env('SECURITY_TRUSTED_PROXIES', '')),
        'headers' => env('SECURITY_TRUSTED_PROXY_HEADERS', 'forwarded'),
        'ranges' => [
            'cloudflare' => [
                '173.245.48.0/20',
                '103.21.244.0/22',
                '103.22.200.0/22',
                '103.31.4.0/22',
                '141.101.64.0/18',
                '108.162.192.0/18',
                '190.93.240.0/20',
                '188.114.96.0/20',
                '197.234.240.0/22',
                '198.41.128.0/17',
                '162.158.0.0/15',
                '104.16.0.0/13',
                '104.24.0.0/14',
                '172.64.0.0/13',
                '131.0.72.0/22',
                '2400:cb00::/32',
                '2606:4700::/32',
                '2803:f800::/32',
                '2405:b500::/32',
                '2405:8100::/32',
                '2a06:98c0::/29',
                '2c0f:f248::/32',
            ],
            'aws_elb' => [
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Cache
    |--------------------------------------------------------------------------
    |
    | Tune the caching strategy for authentication related lookups. These
    | values control how aggressively user and activation queries are cached,
    | along with sitter permission sets that power delegation experiences.
    |
    */

    'cache' => [
        'store' => env('SECURITY_CACHE_STORE'),
        'auth_lookup_ttl' => (int) env('SECURITY_CACHE_AUTH_LOOKUP_TTL', 300),
        'activation_lookup_ttl' => (int) env('SECURITY_CACHE_ACTIVATION_LOOKUP_TTL', 300),
        'permission_set_ttl' => (int) env('SECURITY_CACHE_PERMISSION_SET_TTL', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Queue Defaults
    |--------------------------------------------------------------------------
    |
    | Centralized defaults for queued outbound mail. Notifications responsible
    | for verification and password reset emails reference these settings to
    | ensure they are dispatched on the preferred transport every time.
    |
    */

    'mail' => [
        'queue' => [
            'connection' => env('MAIL_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
            'name' => env('MAIL_QUEUE', 'mail'),
            'retry_after' => (int) env('MAIL_QUEUE_RETRY_AFTER', 90),
        ],
    ],

];
