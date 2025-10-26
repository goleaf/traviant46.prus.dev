<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Correlation Header
    |--------------------------------------------------------------------------
    |
    | The header that will be used to propagate request correlation identifiers
    | between incoming and outgoing traffic. Override this if an upstream
    | gateway uses a different header name that should be honoured.
    |
    */

    'request_id_header' => env('MONITORING_REQUEST_ID_HEADER', 'X-Request-Id'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how application metrics should be emitted. The default driver
    | writes structured JSON logs that can be scraped by log forwarders.
    | Optionally, enable the StatsD driver to push metrics via UDP.
    |
    */

    'metrics' => [
        'driver' => env('METRICS_DRIVER', 'log'),

        'log' => [
            'channel' => env('METRICS_LOG_CHANNEL', 'metrics'),
        ],

        'statsd' => [
            'host' => env('STATSD_HOST', '127.0.0.1'),
            'port' => (int) env('STATSD_PORT', 8125),
            'namespace' => env('STATSD_NAMESPACE', 'travian'),
            'failover_log_channel' => env('STATSD_FAILOVER_LOG_CHANNEL', null),
        ],
    ],

];
