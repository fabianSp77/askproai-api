<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Auth Guard & Password Broker
    |--------------------------------------------------------------------------
    |
    | Hier legst du fest, welcher Guard standard­mä­ßig für dein
    | Frontend benutzt wird und welcher Password-Broker für
    | Pass­wort-Resets zuständig ist.
    |
    */

    'defaults' => [
        'guard'     => 'web',   // <- Browser-Sessions
        'passwords' => 'users', // <- Tabelle password_reset_tokens
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    |
    | Jeder Guard repräsentiert eine „Log-in-Schicht“ (Session,
    | Token …​) mit einem User-Provider.  Für Filament benötigen
    | wir einen eigenen Guard, damit Shield & Panel richtig
    | zusammen­spielen.
    |
    */

    'guards' => [
        /*  ────────── Standard-Web-Guard ────────── */
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        /*  ────────── API (Token / Passport) ─────── */
        'api' => [
            'driver'   => 'passport',
            'provider' => 'users',
            'hash'     => false,
        ],
        
        /*  ────────── Customer Portal Guard ─────── */
        'customer' => [
            'driver'   => 'session',
            'provider' => 'customers',
        ],
        
        /*  ────────── Business Portal Guard (DEPRECATED - Use 'web' guard) ─────── */
        // Kept for backward compatibility during migration
        // 'portal' => [
        //     'driver'   => 'session',
        //     'provider' => 'portal_users',
        // ],
        
        /*  ────────── Customer API Guard ─────── */
        'customer-api' => [
            'driver'   => 'sanctum',
            'provider' => 'customers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Definiert, wie Benutzer geladen werden.  Meist genügt
    | der Eloquent-Provider mit deinem User-Model.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],
        
        'customers' => [
            'driver' => 'eloquent',
            'model'  => App\Models\CustomerAuth::class,
        ],
        
        // DEPRECATED - portal_users migrated to main users table
        // 'portal_users' => [
        //     'driver' => 'portal_eloquent',
        //     'model'  => App\Models\PortalUser::class,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset / Confirmation
    |--------------------------------------------------------------------------
    |
    | Tabelle, Lauf­zeit & Throttle der Reset-Tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,   // Minuten
            'throttle' => 60,
        ],
        
        'customers' => [
            'provider' => 'customers',
            'table'    => 'customer_password_resets',
            'expire'   => 60,   // Minuten
            'throttle' => 60,
        ],
        
        'portal' => [
            'provider' => 'portal_users',
            'table'    => 'portal_password_resets',
            'expire'   => 60,   // Minuten
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session-Timeout für Passwort­bestätigungen
    |--------------------------------------------------------------------------
    */

    'password_timeout' => 10_800, // = 3 h
];
