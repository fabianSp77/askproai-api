<?php

use Illuminate\Support\Str;

return [

    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver'         => 'mysql',
            'url'            => env('DATABASE_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_DATABASE', 'database'),
            'username'       => env('DB_USERNAME', 'user'),
            'password'       => env('DB_PASSWORD', ''),
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => 'InnoDB',
            'options'        => extension_loaded('pdo_mysql')
                                ? array_filter([
                                      PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                                      // Connection Pooling Optimizations
                                      PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', true),
                                      PDO::ATTR_EMULATE_PREPARES => true,
                                      PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
                                      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                                      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                      PDO::ATTR_TIMEOUT => env('DB_TIMEOUT', 30),
                                      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci', SESSION sql_mode='TRADITIONAL,NO_AUTO_VALUE_ON_ZERO', SESSION wait_timeout=28800",
                                  ])
                                : [],
            
            // Slow Query Logging
            'log_queries' => env('DB_LOG_QUERIES', false),
            'log_slow_queries' => env('DB_LOG_SLOW_QUERIES', true),
            'slow_query_time' => env('DB_SLOW_QUERY_TIME', 2), // Log queries slower than 2 seconds
            
            // Connection Pool Configuration
            'pool' => [
                'min_connections' => env('DB_POOL_MIN', 5),
                'max_connections' => env('DB_POOL_MAX', 50),
                'connection_timeout' => env('DB_POOL_TIMEOUT', 10),
                'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 60),
                'health_check_interval' => env('DB_POOL_HEALTH_CHECK', 30),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection Pool Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the behavior of database connection pooling.
    | Adjust based on your server capacity and expected load.
    |
    */
    
    'pool' => [
        'enabled' => env('DB_POOL_ENABLED', true),
        'min_connections' => env('DB_POOL_MIN', 5),
        'max_connections' => env('DB_POOL_MAX', 50),
        'max_idle_time' => env('DB_POOL_IDLE_TIME', 60),
        'validation_interval' => env('DB_POOL_VALIDATION', 30),
    ],

    'migrations' => 'migrations',

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD') === 'null' ? null : env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD') === 'null' ? null : env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
