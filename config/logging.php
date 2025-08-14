<?php

use Monolog\Handler\NullHandler;

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'mail' => [
            'driver' => 'single',
            'path' => storage_path('logs/mail.log'),
            'level' => 'debug',
        ],

        // Calcom-Log
        'calcom' => [
            'driver' => 'single',
            'path' => storage_path('logs/calcom.log'),
            'level' => 'debug',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],
        // ... weitere Channels falls benötigt
    ],

];
