<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;


return [
        "mail" => [
            "driver" => "single",
            "path"   => storage_path("logs/mail.log"),
            "level"  => "debug",
        ],
        "mail" => [

            "driver" => "single",

            "path"   => storage_path("logs/mail.log"),

            "level"  => "debug",

        ],

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace'   => false,
    ],

    'channels' => [

        /* -------------------------------------------------------------- *
         *  Standard-Stack (unverändert)                                  *
         * -------------------------------------------------------------- */
        'stack' => [
            'driver'   => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravel.log'),
            'level'  => env('LOG_LEVEL', 'debug'),
        ],

        /* -------------------------------------------------------------- *
         *  Neuer Channel „calcom“                                        *
         * -------------------------------------------------------------- */
        'calcom' => [
            'driver' => 'single',
            'path'   => storage_path('logs/calcom.log'),
            'level'  => 'info',
        ],

        /* … alle weiteren Standard-Channels kannst du lassen … */
        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],
    ],
];
