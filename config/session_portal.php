<?php

use Illuminate\Support\Str;

return [
    /*
    |--------------------------------------------------------------------------
    | Business Portal Session Configuration
    |--------------------------------------------------------------------------
    |
    | Separate session configuration for the Business Portal to prevent
    | conflicts with Admin Portal sessions.
    |
    */

    'driver' => env('SESSION_DRIVER', 'file'),

    'lifetime' => env('PORTAL_SESSION_LIFETIME', 480), // 8 hours for portal users

    'expire_on_close' => false,

    'encrypt' => env('SESSION_ENCRYPT', true),

    'files' => storage_path('framework/sessions/portal'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => 'portal_sessions',

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Portal-specific Session Cookie
    |--------------------------------------------------------------------------
    |
    | This cookie name is specifically for the Business Portal to avoid
    | conflicts with the Admin Portal session cookie.
    |
    */
    'cookie' => env(
        'PORTAL_SESSION_COOKIE',
        'askproai_portal_session'
    ),

    'path' => '/',

    'domain' => env('SESSION_DOMAIN'),

    'secure' => env('SESSION_SECURE_COOKIE', true),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => false,
];