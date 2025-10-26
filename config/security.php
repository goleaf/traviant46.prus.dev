<?php

declare(strict_types=1);

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
            'per_user' => [
                'max_attempts' => (int) env('SECURITY_RATE_LIMIT_LOGIN_PER_USER_ATTEMPTS', 20),
                'decay_minutes' => (int) env('SECURITY_RATE_LIMIT_LOGIN_PER_USER_DECAY_MINUTES', 5),
            ],
            'per_ip' => [
                'max_attempts' => (int) env('SECURITY_RATE_LIMIT_LOGIN_PER_IP_ATTEMPTS', 50),
                'decay_minutes' => (int) env('SECURITY_RATE_LIMIT_LOGIN_PER_IP_DECAY_MINUTES', 10),
            ],
            'per_device' => [
                'max_attempts' => (int) env('SECURITY_RATE_LIMIT_LOGIN_PER_DEVICE_ATTEMPTS', 15),
                'decay_minutes' => (int) env('SECURITY_RATE_LIMIT_LOGIN_PER_DEVICE_DECAY_MINUTES', 5),
            ],
        ],
        'market' => [
            'per_minute' => (int) env('SECURITY_RATE_LIMIT_MARKET_PER_MINUTE', 30),
            'per_hour' => (int) env('SECURITY_RATE_LIMIT_MARKET_PER_HOUR', 150),
        ],
        'send' => [
            'per_minute' => (int) env('SECURITY_RATE_LIMIT_SEND_PER_MINUTE', 20),
            'per_hour' => (int) env('SECURITY_RATE_LIMIT_SEND_PER_HOUR', 90),
        ],
        'messages' => [
            'per_minute' => (int) env('SECURITY_RATE_LIMIT_MESSAGES_PER_MINUTE', 15),
            'per_hour' => (int) env('SECURITY_RATE_LIMIT_MESSAGES_PER_HOUR', 60),
        ],
        'two_factor' => [
            'max_attempts' => (int) env('SECURITY_RATE_LIMIT_TWO_FACTOR_ATTEMPTS', 5),
            'decay_minutes' => (int) env('SECURITY_RATE_LIMIT_TWO_FACTOR_DECAY_MINUTES', 1),
        ],
        'password_reset' => [
            'max_attempts' => (int) env('SECURITY_RATE_LIMIT_PASSWORD_RESET_ATTEMPTS', 3),
            'decay_minutes' => (int) env('SECURITY_RATE_LIMIT_PASSWORD_RESET_DECAY_MINUTES', 10),
            'per_ip' => [
                'max_attempts' => (int) env('SECURITY_RATE_LIMIT_PASSWORD_RESET_PER_IP_ATTEMPTS', 10),
                'decay_minutes' => (int) env('SECURITY_RATE_LIMIT_PASSWORD_RESET_PER_IP_DECAY_MINUTES', 60),
            ],
        ],
        'sitter_mutations' => [
            'per_minute' => (int) env('SECURITY_RATE_LIMIT_SITTER_MINUTE', 6),
            'per_hour' => (int) env('SECURITY_RATE_LIMIT_SITTER_HOUR', 30),
        ],
        'admin_actions' => [
            'per_minute' => (int) env('SECURITY_RATE_LIMIT_ADMIN_MINUTE', 30),
            'per_hour' => (int) env('SECURITY_RATE_LIMIT_ADMIN_HOUR', 120),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Security Headers
    |--------------------------------------------------------------------------
    |
    | Define the default HTTP response headers that enforce browser security
    | policies such as the Content Security Policy and referrer behaviour.
    | Directives can be customised per-environment using the overrides array.
    |
    */

    'headers' => [
        'csp' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'],
            'font-src' => ["'self'", 'https://fonts.bunny.net', 'data:'],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'connect-src' => ["'self'"],
        ],
        'csp_environment_overrides' => [
            'local' => [
                'script-src' => ['http://localhost:5173', 'http://127.0.0.1:5173'],
                'style-src' => ['http://localhost:5173', 'http://127.0.0.1:5173'],
                'connect-src' => [
                    'ws://localhost:5173',
                    'ws://127.0.0.1:5173',
                    'http://localhost:5173',
                    'http://127.0.0.1:5173',
                ],
            ],
        ],
        'referrer-policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted Device Management
    |--------------------------------------------------------------------------
    |
    | Configure how remembered devices are handled, including whether the
    | feature is enabled, cookie characteristics, expiration windows, and
    | per-user limits. These values are consumed by the TrustedDeviceManager.
    |
    */

    'trusted_devices' => [
        'enabled' => (bool) env('SECURITY_TRUSTED_DEVICES_ENABLED', true),
        'max_per_user' => (int) env('SECURITY_TRUSTED_DEVICES_MAX_PER_USER', 10),
        'default_expiration_days' => (int) env('SECURITY_TRUSTED_DEVICES_EXPIRATION_DAYS', 180),
        'cookie' => [
            'name' => env('SECURITY_TRUSTED_DEVICE_COOKIE_NAME', 'travian_trusted_device'),
            'lifetime_days' => (int) env('SECURITY_TRUSTED_DEVICE_COOKIE_DAYS', 180),
            'same_site' => env('SECURITY_TRUSTED_DEVICE_COOKIE_SAME_SITE', env('SESSION_SAME_SITE', 'lax')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Reputation Service
    |--------------------------------------------------------------------------
    |
    | Configure the IP reputation checks that run during authentication.
    | Scores greater than or equal to the configured threshold are considered
    | hostile and the login attempt is blocked before credentials are checked.
    |
    */

    'ip_reputation' => [
        'enabled' => (bool) env('SECURITY_IP_REPUTATION_ENABLED', true),
        'block_score' => (int) env('SECURITY_IP_REPUTATION_BLOCK_SCORE', 70),
        'cache_ttl_seconds' => (int) env('SECURITY_IP_REPUTATION_CACHE_TTL', 900),
        'allow' => [
            'ips' => array_filter(array_map('trim', explode(',', (string) env('SECURITY_IP_REPUTATION_ALLOW_IPS', '')))),
            'cidrs' => array_filter(array_map('trim', explode(',', (string) env('SECURITY_IP_REPUTATION_ALLOW_CIDRS', '')))),
        ],
        'block' => [
            'ips' => array_filter(array_map('trim', explode(',', (string) env('SECURITY_IP_REPUTATION_BLOCK_IPS', '')))),
            'cidrs' => array_filter(array_map('trim', explode(',', (string) env('SECURITY_IP_REPUTATION_BLOCK_CIDRS', '')))),
        ],
        'mock_scores' => [],
        'providers' => [
            'http' => [
                'endpoint' => env('SECURITY_IP_REPUTATION_HTTP_ENDPOINT'),
                'token' => env('SECURITY_IP_REPUTATION_HTTP_TOKEN'),
                'timeout' => (int) env('SECURITY_IP_REPUTATION_HTTP_TIMEOUT', 3),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Event Broadcasting
    |--------------------------------------------------------------------------
    |
    | Configure how authentication lifecycle events are streamed to external
    | systems. Drivers include HTTPS webhooks and AWS EventBridge. Payloads
    | are dispatched asynchronously via the configured queue.
    |
    */

    'auth_events' => [
        'enabled' => (bool) env('SECURITY_AUTH_EVENTS_ENABLED', true),
        'driver' => env('SECURITY_AUTH_EVENTS_DRIVER', 'webhook'),
        'queue' => env('SECURITY_AUTH_EVENTS_QUEUE', 'notifications'),
        'webhook' => [
            'url' => env('SECURITY_AUTH_EVENTS_WEBHOOK_URL'),
            'secret' => env('SECURITY_AUTH_EVENTS_WEBHOOK_SECRET'),
            'timeout' => (int) env('SECURITY_AUTH_EVENTS_WEBHOOK_TIMEOUT', 3),
            'verify_ssl' => (bool) env('SECURITY_AUTH_EVENTS_WEBHOOK_VERIFY', true),
        ],
        'eventbridge' => [
            'bus_name' => env('SECURITY_AUTH_EVENTS_EVENTBRIDGE_BUS'),
            'region' => env('SECURITY_AUTH_EVENTS_EVENTBRIDGE_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
            'source' => env('SECURITY_AUTH_EVENTS_EVENTBRIDGE_SOURCE', 'traviant.auth'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Verification
    |--------------------------------------------------------------------------
    |
    | Controls notifications for logins originating from unrecognised device
    | fingerprints or network locations. Cache TTL defines how long a device
    | fingerprint remains trusted before re-notification occurs.
    |
    */

    'device_verification' => [
        'enabled' => (bool) env('SECURITY_DEVICE_VERIFICATION', true),
        'cache_ttl_minutes' => (int) env('SECURITY_DEVICE_VERIFICATION_CACHE_TTL', 720),
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

    /*
    |--------------------------------------------------------------------------
    | Signed Cookie Key Rotation
    |--------------------------------------------------------------------------
    |
    | Configure how many previous keys should be retained when rotating the
    | application's signing key set. Keeping previous keys allows active
    | sessions to remain valid while new cookies are minted with the latest
    | secret. Adjust the retention window to meet operational requirements.
    |
    */

    'cookie_keys' => [
        'max_previous' => (int) env('SECURITY_COOKIE_KEYS_MAX_PREVIOUS', 5),
    ],

];
