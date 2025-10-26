<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Context;
use Sentry\Event;

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    */

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
    |--------------------------------------------------------------------------
    | Release & Environment
    |--------------------------------------------------------------------------
    */

    'release' => env('SENTRY_RELEASE'),
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),

    /*
    |--------------------------------------------------------------------------
    | Sampling
    |--------------------------------------------------------------------------
    */

    'sample_rate' => env('SENTRY_SAMPLE_RATE') === null ? 1.0 : (float) env('SENTRY_SAMPLE_RATE'),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_TRACES_SAMPLE_RATE'),
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_PROFILES_SAMPLE_RATE'),

    /*
    |--------------------------------------------------------------------------
    | Logging Integration
    |--------------------------------------------------------------------------
    */

    'enable_logs' => env('SENTRY_ENABLE_LOGS', false),
    'logs_channel_level' => env('SENTRY_LOG_LEVEL', env('LOG_LEVEL', 'debug')),

    /*
    |--------------------------------------------------------------------------
    | Personally Identifiable Information
    |--------------------------------------------------------------------------
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', true),

    /*
    |--------------------------------------------------------------------------
    | Filtering & Tagging
    |--------------------------------------------------------------------------
    */

    'ignore_transactions' => [
        '/up',
    ],

    'before_send' => static function (Event $event): Event {
        if (Context::has('request_id')) {
            $event->setTag('request_id', (string) Context::get('request_id'));
        }

        return $event;
    },

    'breadcrumbs' => [
        'logs' => env('SENTRY_BREADCRUMBS_LOGS_ENABLED', true),
        'cache' => env('SENTRY_BREADCRUMBS_CACHE_ENABLED', true),
        'livewire' => env('SENTRY_BREADCRUMBS_LIVEWIRE_ENABLED', true),
        'sql_queries' => env('SENTRY_BREADCRUMBS_SQL_QUERIES_ENABLED', true),
        'sql_bindings' => env('SENTRY_BREADCRUMBS_SQL_BINDINGS_ENABLED', false),
        'queue_info' => env('SENTRY_BREADCRUMBS_QUEUE_INFO_ENABLED', true),
        'command_info' => env('SENTRY_BREADCRUMBS_COMMAND_JOBS_ENABLED', true),
        'http_client_requests' => env('SENTRY_BREADCRUMBS_HTTP_CLIENT_REQUESTS_ENABLED', true),
        'notifications' => env('SENTRY_BREADCRUMBS_NOTIFICATIONS_ENABLED', true),
    ],

    'tracing' => [
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_ENABLED', true),
        'queue_jobs' => env('SENTRY_TRACE_QUEUE_JOBS_ENABLED', true),
        'sql_queries' => env('SENTRY_TRACE_SQL_QUERIES_ENABLED', true),
        'sql_bindings' => env('SENTRY_TRACE_SQL_BINDINGS_ENABLED', false),
        'sql_origin' => env('SENTRY_TRACE_SQL_ORIGIN_ENABLED', true),
        'sql_origin_threshold_ms' => env('SENTRY_TRACE_SQL_ORIGIN_THRESHOLD_MS', 100),
        'views' => env('SENTRY_TRACE_VIEWS_ENABLED', true),
        'livewire' => env('SENTRY_TRACE_LIVEWIRE_ENABLED', true),
        'http_client_requests' => env('SENTRY_TRACE_HTTP_CLIENT_REQUESTS_ENABLED', true),
        'cache' => env('SENTRY_TRACE_CACHE_ENABLED', true),
        'redis_commands' => env('SENTRY_TRACE_REDIS_COMMANDS', false),
        'redis_origin' => env('SENTRY_TRACE_REDIS_ORIGIN_ENABLED', true),
        'notifications' => env('SENTRY_TRACE_NOTIFICATIONS_ENABLED', true),
        'missing_routes' => env('SENTRY_TRACE_MISSING_ROUTES_ENABLED', false),
        'continue_after_response' => env('SENTRY_TRACE_CONTINUE_AFTER_RESPONSE', true),
        'default_integrations' => env('SENTRY_TRACE_DEFAULT_INTEGRATIONS_ENABLED', true),
    ],

];
