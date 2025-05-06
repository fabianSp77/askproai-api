<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Welches Panel ist das „Standard-Panel“?
    |--------------------------------------------------------------------------
    | Bei einer einzigen Admin-Oberfläche genügt eine ID – hier: „admin“.
    */

    'default_panel_id' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | Panel-Definitionen
    |--------------------------------------------------------------------------
    | Bei Bedarf kannst du mehrere Panels definieren.
    */

    'panels' => [

        'admin' => [

            /*
            |------------------------------------------------------------------
            | Basis-Pfad & ID des Panels
            |------------------------------------------------------------------
            */

            'id'   => 'admin',
            'path' => 'admin',

            /*
            |------------------------------------------------------------------
            | Auth-Seiten einzeln aktivieren
            |------------------------------------------------------------------
            | Da der Name  auth()  in deinem Projekt kollidiert,
            | registrieren wir die Login-Features separat.
            */

            'login'             => true,
            'password_reset'    => true,
            'email_verification'=> true,
            'profile'           => true,

            /*
            |------------------------------------------------------------------
            | Ressourcen / Seiten / Widgets automatisch einsammeln
            |------------------------------------------------------------------
            */

            'discover' => [
                'resources' => [
                    'in'  => app_path('Filament/Admin/Resources'),
                    'for' => 'App\\Filament\\Admin\\Resources',
                ],
                'pages' => [
                    'in'  => app_path('Filament/Admin/Pages'),
                    'for' => 'App\\Filament\\Admin\\Pages',
                ],
                'widgets' => [
                    'in'  => app_path('Filament/Admin/Widgets'),
                    'for' => 'App\\Filament\\Admin\\Widgets',
                ],
            ],

            /*
            |------------------------------------------------------------------
            | Plugins
            |------------------------------------------------------------------
            | !!!  Filament-Shield hier bewusst NICHT eingetragen  !!!
            */

            'plugins' => [
                // Beispiel: \Filament\Notifications\NotificationsPlugin::make(),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting / Echo (optional)
    |--------------------------------------------------------------------------
    | Standard-Setup – lass es so, wenn du Echo nicht benutzt.
    */

    'broadcasting' => [

        'enabled' => false,

        // Beispiel-Konfiguration:
        /*
        'pusher' => [
            'key'          => env('PUSHER_APP_KEY'),
            'host'         => env('PUSHER_HOST', 'api.pusherapp.com'),
            'port'         => env('PUSHER_PORT', 443),
            'scheme'       => 'https',
            'encrypted'    => true,
            'authEndpoint' => '/broadcasting/auth',
            'disableStats' => true,
        ],
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Standard-Speicherdisk
    |--------------------------------------------------------------------------
    */

    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Pfad für veröffentlichte Assets
    |--------------------------------------------------------------------------
    */

    'assets_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Cache-Pfad für Filament-Komponenten
    |--------------------------------------------------------------------------
    */

    'cache_path' => base_path('bootstrap/cache/filament'),

    /*
    |--------------------------------------------------------------------------
    | Livewire-Loading-Delay
    |--------------------------------------------------------------------------
    */

    'livewire_loading_delay' => 'default',
];
