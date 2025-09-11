<?php

return [

    /* ------------------------------------------------ Basics ------------ */
    'name'            => env('APP_NAME', 'AskProAI'),
    'env'             => env('APP_ENV', 'production'),
    'debug'           => (bool) env('APP_DEBUG', false),
    'url'             => env('APP_URL', 'https://api.askproai.de'),
    'asset_url'       => env('ASSET_URL'),

    'timezone'        => 'Europe/Berlin',
    'locale'          => 'de',
    'fallback_locale' => 'en',
    'faker_locale'    => 'de_DE',

    'key'    => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    /* ------------- Service-Provider – jeder Eintrag genau EINMAL -------- */
    'providers' => [

        /* Laravel Core */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /* Packages */
        Laravel\Horizon\HorizonServiceProvider::class,
        Filament\FilamentServiceProvider::class,
//         App\Providers\Filament\TestPanelProvider::class,
        BezhanSalleh\FilamentShield\FilamentShieldServiceProvider::class,

        /* App */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\FlowbiteServiceProvider::class,
        App\Providers\ViewServiceProvider::class,

        /* Filament Panel */
        App\Providers\Filament\AdminPanelProvider::class,
    ],
    /* --------------------------- Facades -------------------------------- */
    'aliases' => [
        'App'    => Illuminate\Support\Facades\App::class,
        'Arr'    => Illuminate\Support\Arr::class,
        'Auth'   => Illuminate\Support\Facades\Auth::class,
        'Cache'  => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'DB'     => Illuminate\Support\Facades\DB::class,
        'Event'  => Illuminate\Support\Facades\Event::class,
        'File'   => Illuminate\Support\Facades\File::class,
        'Gate'   => Illuminate\Support\Facades\Gate::class,
        'Log'    => Illuminate\Support\Facades\Log::class,
        'Queue'  => Illuminate\Support\Facades\Queue::class,
        'Route'  => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Str'    => Illuminate\Support\Str::class,
    ],
];
