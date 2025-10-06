<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;

class FilamentColumnOrderingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register admin-specific JavaScript with Alpine.js and column manager
        FilamentAsset::register([
            Js::make('app-admin', Vite::asset('resources/js/app-admin.js'))
                ->loadedOnRequest()
                ->module(),
        ]);

        // Register Blade component
        Blade::component('filament.tables.column-manager', 'column-manager');

        // Add custom CSS if needed
        $this->publishes([
            __DIR__ . '/../../resources/views/filament' => resource_path('views/vendor/filament'),
        ], 'filament-column-ordering');
    }
}