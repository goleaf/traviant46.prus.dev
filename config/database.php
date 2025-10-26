<?php

use Illuminate\Support\Str;
use PDO;

$filterConfig = static fn (array $config): array => array_filter(
    $config,
    static fn ($value) => $value !== null && $value !== ''
);

$mysqlReadHosts = array_values(array_filter(
    array_map('trim', explode(',', (string) env('DB_READ_HOSTS', (string) env('DB_READ_HOST', '')))),
    static fn ($host) => $host !== ''
));

$mysqlWriteHosts = array_values(array_filter(
    array_map('trim', explode(',', (string) env('DB_WRITE_HOSTS', (string) env('DB_WRITE_HOST', '')))),
    static fn ($host) => $host !== ''
));

$mysqlDatabase = env('DB_DATABASE', 'laravel');

$mysqlReadConfig = $mysqlReadHosts !== [] ? $filterConfig([
    'host' => $mysqlReadHosts,
    'port' => env('DB_READ_PORT', env('DB_PORT', '3306')),
    'username' => env('DB_READ_USERNAME', env('DB_USERNAME', 'root')),
    'password' => env('DB_READ_PASSWORD', env('DB_PASSWORD', '')),
    'database' => env('DB_READ_DATABASE', $mysqlDatabase),
]) : [];

$mysqlWriteConfig = $mysqlWriteHosts !== [] ? $filterConfig([
    'host' => $mysqlWriteHosts,
    'port' => env('DB_WRITE_PORT', env('DB_PORT', '3306')),
    'username' => env('DB_WRITE_USERNAME', env('DB_USERNAME', 'root')),
    'password' => env('DB_WRITE_PASSWORD', env('DB_PASSWORD', '')),
    'database' => env('DB_WRITE_DATABASE', $mysqlDatabase),
]) : [];

$mysqlSslVerifyServerCert = env('DB_SSL_VERIFY_SERVER_CERT');

$mysqlSslOptions = extension_loaded('pdo_mysql') ? array_filter([
    PDO::MYSQL_ATTR_SSL_CA => env('DB_SSL_CA'),
    PDO::MYSQL_ATTR_SSL_CERT => env('DB_SSL_CERT'),
    PDO::MYSQL_ATTR_SSL_KEY => env('DB_SSL_KEY'),
    PDO::MYSQL_ATTR_SSL_CIPHER => env('DB_SSL_CIPHER'),
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => $mysqlSslVerifyServerCert !== null
        ? filter_var($mysqlSslVerifyServerCert, FILTER_VALIDATE_BOOL)
        : null,
], static fn ($value) => $value !== null) : [];

$legacyReadHosts = array_values(array_filter(
    array_map('trim', explode(',', (string) env('LEGACY_DB_READ_HOSTS', (string) env('LEGACY_DB_READ_HOST', '')))),
    static fn ($host) => $host !== ''
));

$legacyWriteHosts = array_values(array_filter(
    array_map('trim', explode(',', (string) env('LEGACY_DB_WRITE_HOSTS', (string) env('LEGACY_DB_WRITE_HOST', '')))),
    static fn ($host) => $host !== ''
));

$legacyDatabase = env('LEGACY_DB_DATABASE', env('DB_DATABASE', 'laravel'));

$legacyReadConfig = $legacyReadHosts !== [] ? $filterConfig([
    'host' => $legacyReadHosts,
    'port' => env('LEGACY_DB_READ_PORT', env('LEGACY_DB_PORT', env('DB_PORT', '3306'))),
    'username' => env('LEGACY_DB_READ_USERNAME', env('LEGACY_DB_USERNAME', env('DB_USERNAME', 'root'))),
    'password' => env('LEGACY_DB_READ_PASSWORD', env('LEGACY_DB_PASSWORD', env('DB_PASSWORD', ''))),
    'database' => env('LEGACY_DB_READ_DATABASE', $legacyDatabase),
]) : [];

$legacyWriteConfig = $legacyWriteHosts !== [] ? $filterConfig([
    'host' => $legacyWriteHosts,
    'port' => env('LEGACY_DB_WRITE_PORT', env('LEGACY_DB_PORT', env('DB_PORT', '3306'))),
    'username' => env('LEGACY_DB_WRITE_USERNAME', env('LEGACY_DB_USERNAME', env('DB_USERNAME', 'root'))),
    'password' => env('LEGACY_DB_WRITE_PASSWORD', env('LEGACY_DB_PASSWORD', env('DB_PASSWORD', ''))),
    'database' => env('LEGACY_DB_WRITE_DATABASE', $legacyDatabase),
]) : [];

$legacyMysqlSslVerifyServerCert = env('LEGACY_DB_SSL_VERIFY_SERVER_CERT', env('DB_SSL_VERIFY_SERVER_CERT'));

$legacySslOptions = extension_loaded('pdo_mysql') ? array_filter([
    PDO::MYSQL_ATTR_SSL_CA => env('LEGACY_DB_SSL_CA', env('DB_SSL_CA')),
    PDO::MYSQL_ATTR_SSL_CERT => env('LEGACY_DB_SSL_CERT', env('DB_SSL_CERT')),
    PDO::MYSQL_ATTR_SSL_KEY => env('LEGACY_DB_SSL_KEY', env('DB_SSL_KEY')),
    PDO::MYSQL_ATTR_SSL_CIPHER => env('LEGACY_DB_SSL_CIPHER', env('DB_SSL_CIPHER')),
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => $legacyMysqlSslVerifyServerCert !== null
        ? filter_var($legacyMysqlSslVerifyServerCert, FILTER_VALIDATE_BOOL)
        : null,
], static fn ($value) => $value !== null) : [];

$pgsqlSsl = $filterConfig([
    'sslmode' => env('PGSQL_SSL_MODE', env('DB_SSL_MODE', 'prefer')),
    'sslrootcert' => env('PGSQL_SSL_ROOT_CERT'),
    'sslcert' => env('PGSQL_SSL_CERT'),
    'sslkey' => env('PGSQL_SSL_KEY'),
    'sslcrl' => env('PGSQL_SSL_CRL'),
]);

$redisSentinels = array_values(array_filter(
    array_map('trim', explode(',', (string) env('REDIS_SENTINELS', ''))),
    static fn ($entry) => $entry !== ''
));

$redisClusters = array_values(array_filter(
    array_map('trim', explode(',', (string) env('REDIS_CLUSTER_NODES', ''))),
    static fn ($entry) => $entry !== ''
));

$redisScheme = env('REDIS_SCHEME', 'tcp');

$buildRedisConnection = static function (string $databaseEnv, string $defaultDatabase) use ($filterConfig, $redisScheme) {
    return $filterConfig([
        'scheme' => $redisScheme,
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env($databaseEnv, $defaultDatabase),
        'read_timeout' => env('REDIS_READ_TIMEOUT'),
    ]);
};

$redisConnections = [
    'default' => $buildRedisConnection('REDIS_DB', '0'),
    'cache' => $buildRedisConnection('REDIS_CACHE_DB', '1'),
    'session' => $buildRedisConnection('REDIS_SESSION_DB', '2'),
];

$redisOptions = $filterConfig([
    'cluster' => $redisClusters !== [] ? 'redis' : env('REDIS_CLUSTER', 'redis'),
    'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
    'persistent' => filter_var(env('REDIS_PERSISTENT', false), FILTER_VALIDATE_BOOL),
]);

if (env('REDIS_BACKOFF_ALGORITHM')) {
    $redisOptions['backoff'] = $filterConfig([
        'algorithm' => env('REDIS_BACKOFF_ALGORITHM'),
        'base' => env('REDIS_BACKOFF_BASE'),
        'cap' => env('REDIS_BACKOFF_CAP'),
    ]);
}

if ($redisClusters !== []) {
    $redisConnections['clusters'] = [
        'default' => array_map(static function ($entry) {
            $parts = parse_url(str_contains($entry, '://') ? $entry : "tcp://{$entry}");

            return [
                'host' => $parts['host'] ?? '127.0.0.1',
                'port' => (string) ($parts['port'] ?? '6379'),
            ];
        }, $redisClusters),
    ];
}

if ($redisSentinels !== []) {
    $redisOptions = array_merge($redisOptions, [
        'replication' => 'sentinel',
        'service' => env('REDIS_SENTINEL_SERVICE', 'mymaster'),
        'parameters' => $filterConfig([
            'password' => env('REDIS_PASSWORD'),
            'username' => env('REDIS_USERNAME'),
            'database' => env('REDIS_DB', '0'),
            'scheme' => $redisScheme,
        ]),
    ]);

    $redisConnections['sentinels'] = array_map(static function ($entry) {
        $parts = parse_url(str_contains($entry, '://') ? $entry : "tcp://{$entry}");

        return [
            'host' => $parts['host'] ?? $entry,
            'port' => (string) ($parts['port'] ?? '26379'),
        ];
    }, $redisSentinels);
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => array_merge([
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'sticky' => filter_var(env('DB_STICKY', $mysqlReadConfig !== []), FILTER_VALIDATE_BOOL),
            'options' => $mysqlSslOptions,
        ], $mysqlReadConfig !== [] ? ['read' => $mysqlReadConfig] : [], $mysqlWriteConfig !== [] ? ['write' => $mysqlWriteConfig] : []),

        'legacy' => array_merge([
            'driver' => env('LEGACY_DB_DRIVER', 'mysql'),
            'url' => env('LEGACY_DB_URL'),
            'host' => env('LEGACY_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('LEGACY_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('LEGACY_DB_DATABASE', env('DB_DATABASE', 'laravel')),
            'username' => env('LEGACY_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('LEGACY_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('LEGACY_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('LEGACY_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('LEGACY_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'sticky' => filter_var(env('LEGACY_DB_STICKY', $legacyReadConfig !== []), FILTER_VALIDATE_BOOL),
            'options' => $legacySslOptions,
        ], $legacyReadConfig !== [] ? ['read' => $legacyReadConfig] : [], $legacyWriteConfig !== [] ? ['write' => $legacyWriteConfig] : []),

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'session' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
