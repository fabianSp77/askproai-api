<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | Hier legen wir nur fest, welche Verbindung Laravel standardmäßig nutzt.
    | Der eigentliche Name / Host usw. kommen ab sofort **alle** aus der .env.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Datenbank-Verbindungen
    |--------------------------------------------------------------------------
    |
    | Der MySQL-Block holt sich jetzt jeden Wert aus der .env-Datei.
    | Falls dort etwas fehlt, greifen die angegebenen Defaults.
    |
    */

    'connections' => [

        'mysql' => [
            'driver'      => 'mysql',
            'host'        => env('DB_HOST', '127.0.0.1'),
            'port'        => env('DB_PORT', '3306'),
            'database'    => env('DB_DATABASE', 'askproai_staging_db'),
            'username'    => env('DB_USERNAME', 'root'),
            'password'    => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => '',
            'prefix_indexes' => true,
            'strict'      => true,
            'engine'      => null,
            'options'     => extension_loaded('pdo_mysql')
                                ? array_filter([
                                    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                                ])
                                : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migrations-Tabelle
    |--------------------------------------------------------------------------
    |
    | Laravel legt hier fest, in welcher Tabelle es durchgeführte Migrationen
    | speichert. Standard bleibt "migrations".
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis-Konfiguration
    |--------------------------------------------------------------------------
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],

        'default' => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

    ],

];

