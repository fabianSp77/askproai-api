<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class FilamentColumnToggleServiceProvider extends ServiceProvider
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
        FilamentAsset::register([
            Css::make('filament-column-toggle-fix', __DIR__ . '/../../resources/css/filament-column-toggle-fix.css'),
            Js::make('filament-column-toggle-fix', __DIR__ . '/../../resources/js/filament-column-toggle-fix.js'),
        ], 'app');
    }
}