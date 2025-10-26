<?php

declare(strict_types=1);

return [
    'default_language' => env('TRAVIAN_DEFAULT_LANGUAGE', 'us'),
    'default_timezone' => env('TRAVIAN_DEFAULT_TIMEZONE', 'Australia/Sydney'),
    'default_direction' => env('TRAVIAN_DEFAULT_DIRECTION', 'LTR'),
    'default_date_format' => env('TRAVIAN_DEFAULT_DATE_FORMAT', 'y.m.d'),
    'default_time_format' => env('TRAVIAN_DEFAULT_TIME_FORMAT', 'H:i'),
    'index_url' => env('TRAVIAN_INDEX_URL', 'https://www.YOUR_DOMAIN.com/'),
    'forum_url' => env('TRAVIAN_FORUM_URL', 'https://forum.YOUR_DOMAIN.com/'),
    'answers_url' => env('TRAVIAN_ANSWERS_URL', 'https://answers.travian.com/index.php'),
    'help_url' => env('TRAVIAN_HELP_URL', 'https://help.YOUR_DOMAIN.com/'),
    'admin_email' => env('TRAVIAN_ADMIN_EMAIL', ''),
    'session_timeout' => (int) env('TRAVIAN_SESSION_TIMEOUT', 6 * 3600),
    'default_payment_location' => (int) env('TRAVIAN_DEFAULT_PAYMENT_LOCATION', 2),
    'global_css_class' => env('TRAVIAN_GLOBAL_CSS_CLASS', 'USERNAME_HERE'),
    'recaptcha_public_key' => env('TRAVIAN_RECAPTCHA_PUBLIC_KEY', ''),
    'recaptcha_private_key' => env('TRAVIAN_RECAPTCHA_PRIVATE_KEY', ''),
    'gpack_path' => env('TRAVIAN_GPACK_PATH', '/travian/sections/gpack/gpack.php'),
    'caching_servers' => [
        'memcached' => [
            [env('TRAVIAN_CACHE_HOST', '127.0.0.1'), (int) env('TRAVIAN_CACHE_PORT', 11211)],
        ],
    ],
    'global_database' => [
        'hostname' => env('TRAVIAN_GLOBAL_DB_HOST', 'localhost'),
        'username' => env('TRAVIAN_GLOBAL_DB_USERNAME', 'root'),
        'password' => env('TRAVIAN_GLOBAL_DB_PASSWORD', ''),
        'database' => env('TRAVIAN_GLOBAL_DB_DATABASE', 'main'),
        'charset' => env('TRAVIAN_GLOBAL_DB_CHARSET', 'utf8mb4'),
    ],
];
