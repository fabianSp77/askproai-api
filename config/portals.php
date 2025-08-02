<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Portal-specific Session Configuration
    |--------------------------------------------------------------------------
    |
    | Each portal gets its own session configuration to prevent conflicts
    |
    */

    'admin' => [
        'session' => [
            'cookie' => env('ADMIN_SESSION_COOKIE', 'askproai_admin_session'),
            'table' => 'admin_sessions',
            'lifetime' => env('ADMIN_SESSION_LIFETIME', 120),
            'path' => '/admin',
        ],
        'guard' => 'web',
    ],

    'business' => [
        'session' => [
            'cookie' => env('BUSINESS_SESSION_COOKIE', 'askproai_business_session'),
            'table' => 'business_sessions',
            'lifetime' => env('BUSINESS_SESSION_LIFETIME', 120),
            'path' => '/business',
        ],
        'guard' => 'portal',
    ],

    'customer' => [
        'session' => [
            'cookie' => env('CUSTOMER_SESSION_COOKIE', 'askproai_customer_session'),
            'table' => 'customer_sessions',
            'lifetime' => env('CUSTOMER_SESSION_LIFETIME', 120),
            'path' => '/customer',
        ],
        'guard' => 'customer',
    ],
];
