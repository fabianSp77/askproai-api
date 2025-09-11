<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class FlowbiteServiceProvider extends ServiceProvider
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
        // Register Flowbite component namespace
        Blade::componentNamespace('App\\View\\Components\\Flowbite', 'flowbite');
        
        // Share Flowbite initialization scripts globally
        View::composer('*', function ($view) {
            $view->with('flowbiteEnabled', true);
        });
        
        // Register Flowbite assets for Filament - check if assets exist first
        if (class_exists(\Filament\Support\Facades\FilamentAsset::class)) {
            $assets = [];
            
            if (file_exists(public_path('css/flowbite-pro.css'))) {
                $assets[] = \Filament\Support\Assets\Css::make('flowbite-pro', asset('css/flowbite-pro.css'));
            }
            
            if (file_exists(public_path('js/flowbite-alpine.js'))) {
                $assets[] = \Filament\Support\Assets\Js::make('flowbite-alpine', asset('js/flowbite-alpine.js'));
            }
            
            if (file_exists(public_path('js/flowbite-init.js'))) {
                $assets[] = \Filament\Support\Assets\Js::make('flowbite-init', asset('js/flowbite-init.js'));
            }
            
            if (!empty($assets)) {
                \Filament\Support\Facades\FilamentAsset::register($assets, package: 'app');
            }
        }
        
        // Publish Flowbite views
        $this->publishes([
            resource_path('views/components/flowbite') => resource_path('views/components/flowbite'),
            resource_path('views/components/flowbite-pro') => resource_path('views/components/flowbite-pro'),
        ], 'flowbite-views');
        
        // Load Flowbite component views if directories exist
        if (is_dir(resource_path('views/components/flowbite'))) {
            $this->loadViewsFrom(resource_path('views/components/flowbite'), 'flowbite');
        }
        
        if (is_dir(resource_path('views/components/flowbite-pro'))) {
            $this->loadViewsFrom(resource_path('views/components/flowbite-pro'), 'flowbite-pro');
        }
    }
}