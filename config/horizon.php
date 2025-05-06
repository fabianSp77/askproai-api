<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard URL
    |--------------------------------------------------------------------------
    */
    'domain' => env('HORIZON_DOMAIN', null),
    'path'   => 'admin/horizon',

    /*
    |--------------------------------------------------------------------------
    | Welche Queue-Connection Horizon überwacht
    |--------------------------------------------------------------------------
    */
    'use' => env('HORIZON_USE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Supervisor-Definitionen
    |--------------------------------------------------------------------------
    */
    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => env('QUEUE_CONNECTION', 'database'),
                'queue'      => ['default'],
                'balance'    => 'simple',
                'processes'  => 3,
                'tries'      => 3,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'database',
                'queue'      => ['default'],
                'balance'    => 'auto',
                'processes'  => 1,
                'tries'      => 1,
            ],
        ],
    ],
];
