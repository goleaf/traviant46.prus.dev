<?php

declare(strict_types=1);

$csv = static function (string $value, array $default = []): array {
    $value = trim($value);

    if ($value === '') {
        return $default;
    }

    return array_values(array_filter(array_map('trim', explode(',', $value)), static fn ($entry) => $entry !== ''));
};

$paths = $csv((string) env('CORS_ALLOWED_PATHS', 'api/*,broadcasting/auth,sanctum/csrf-cookie'), ['api/*']);

$allowedOrigins = $csv((string) env('CORS_ALLOWED_ORIGINS', (string) env('APP_URL', 'http://localhost')) ?: '', [
    (string) env('APP_URL', 'http://localhost'),
]);

if (in_array('*', $allowedOrigins, true)) {
    $allowedOrigins = ['*'];
}

$allowedMethods = $csv((string) env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']);

if (in_array('*', $allowedMethods, true)) {
    $allowedMethods = ['*'];
}

$allowedHeaders = $csv((string) env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With,Accept,Origin'), ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin']);

if (in_array('*', $allowedHeaders, true)) {
    $allowedHeaders = ['*'];
}

$exposedHeaders = $csv((string) env('CORS_EXPOSED_HEADERS', ''), []);

return [
    'paths' => $paths,

    'allowed_methods' => $allowedMethods,

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => $allowedHeaders,

    'exposed_headers' => $exposedHeaders,

    'max_age' => (int) env('CORS_MAX_AGE', 300),

    'supports_credentials' => filter_var(env('CORS_SUPPORTS_CREDENTIALS', true), FILTER_VALIDATE_BOOL),
];
