<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => env('MAIL_TIMEOUT'),
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
            'encryption' => env('MAIL_ENCRYPTION'),
            'auth_mode' => env('MAIL_AUTH_MODE'),
            'stream' => [
                'ssl' => array_filter([
                    'allow_self_signed' => filter_var(env('MAIL_SSL_ALLOW_SELF_SIGNED', false), FILTER_VALIDATE_BOOL),
                    'verify_peer' => filter_var(env('MAIL_SSL_VERIFY_PEER', true), FILTER_VALIDATE_BOOL),
                    'verify_peer_name' => filter_var(env('MAIL_SSL_VERIFY_PEER_NAME', true), FILTER_VALIDATE_BOOL),
                    'cafile' => env('MAIL_SSL_CAFILE'),
                    'local_cert' => env('MAIL_SSL_LOCAL_CERT'),
                    'local_pk' => env('MAIL_SSL_LOCAL_PRIVATE_KEY'),
                    'passphrase' => env('MAIL_SSL_PASSPHRASE'),
                ], static fn ($value) => $value !== null),
            ],
            'queue' => [
                'connection' => env('MAIL_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
                'name' => env('MAIL_QUEUE', 'mail'),
                'retry_after' => (int) env('MAIL_QUEUE_RETRY_AFTER', 90),
            ],
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Queue Defaults
    |--------------------------------------------------------------------------
    |
    | Global queue configuration for outbound mail notifications. These values
    | are leveraged by queued notifications to ensure consistent dispatching.
    |
    */

    'queue' => [
        'connection' => env('MAIL_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'name' => env('MAIL_QUEUE', 'mail'),
        'retry_after' => (int) env('MAIL_QUEUE_RETRY_AFTER', 90),
    ],

];
