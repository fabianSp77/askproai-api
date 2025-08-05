<?php

/**
 * Emergency App Configuration - Minimal Laravel Setup
 * 
 * This is a stripped-down configuration to prevent memory exhaustion
 * Only essential providers and services are loaded.
 */

return [
    /* ------------------------------------------------ Basics ------------ */
    'name' => env('APP_NAME', 'AskProAI'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://api.askproai.de'),
    'asset_url' => env('ASSET_URL'),

    'timezone' => 'Europe/Berlin',
    'locale' => 'de',
    'fallback_locale' => 'en',
    'faker_locale' => 'de_DE',

    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    /* ------------- Service-Provider – EMERGENCY MODE - MINIMAL ONLY -------- */
    'providers' => [
        /* Laravel Core - Essential Only */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        Illuminate\Routing\RoutingServiceProvider::class,

        /* Packages - Essential Only */
        Filament\FilamentServiceProvider::class,

        /* App - Minimal */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,

        /* Filament Panel - Emergency Mode */
        App\Providers\Filament\AdminPanelProviderEmergency::class,
    ],
    
    /* --------------------------- Facades -------------------------------- */
    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'URL' => Illuminate\Support\Facades\URL::class,
    ],
];