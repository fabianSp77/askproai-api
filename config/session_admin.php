<?php

use Illuminate\Support\Str;

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Portal Session Configuration
    |--------------------------------------------------------------------------
    |
    | Separate session configuration for the Admin Portal (Filament) to prevent
    | conflicts with Business Portal sessions.
    |
    */

    'driver' => env('SESSION_DRIVER', 'file'),

    'lifetime' => env('ADMIN_SESSION_LIFETIME', 120), // 2 hours for admin users

    'expire_on_close' => false,

    'encrypt' => env('SESSION_ENCRYPT', true),

    'files' => storage_path('framework/sessions/admin'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => 'admin_sessions',

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Admin-specific Session Cookie
    |--------------------------------------------------------------------------
    |
    | This cookie name is specifically for the Admin Portal to avoid
    | conflicts with the Business Portal session cookie.
    |
    */
    'cookie' => env(
        'ADMIN_SESSION_COOKIE',
        'askproai_admin_session'
    ),

    'path' => '/',

    'domain' => env('SESSION_DOMAIN'),

    'secure' => env('SESSION_SECURE_COOKIE', true),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => false,
];