<?php

namespace App\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class FilamentSafeFixesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register only the safe fixes as raw inline script
        FilamentAsset::register([
            Js::make('filament-safe-fixes', __DIR__ . '/../../resources/js/filament-safe-fixes.js'),
            Js::make('wizard-dropdown-fix', __DIR__ . '/../../resources/js/wizard-dropdown-fix.js'),
        ], 'app');
    }
}