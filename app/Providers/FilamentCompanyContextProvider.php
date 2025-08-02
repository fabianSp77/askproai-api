<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class FilamentCompanyContextProvider extends ServiceProvider
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
        // Hook into Filament's boot process
        $this->app->booted(function () {
            // Set company context for every Filament request
            if (request()->is('admin/*') || request()->is('livewire/*')) {
                if (Auth::check() && Auth::user()->company_id) {
                    app()->instance('current_company_id', Auth::user()->company_id);
                    app()->instance('company_context_source', 'web_auth');
                }
            }
        });
        
        // Add a render hook to ensure context is set
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            function (): string {
                if (Auth::check() && Auth::user()->company_id && !app()->has('current_company_id')) {
                    app()->instance('current_company_id', Auth::user()->company_id);
                    app()->instance('company_context_source', 'web_auth');
                }
                return '';
            }
        );
        
        // Hook into Livewire component boot
        \Livewire\Livewire::listen('component.boot', function ($component) {
            if (Auth::check() && Auth::user()->company_id && !app()->has('current_company_id')) {
                app()->instance('current_company_id', Auth::user()->company_id);
                app()->instance('company_context_source', 'web_auth');
            }
        });
        
        // Hook into Livewire component mount
        \Livewire\Livewire::listen('component.mount', function ($component) {
            if (Auth::check() && Auth::user()->company_id && !app()->has('current_company_id')) {
                app()->instance('current_company_id', Auth::user()->company_id);
                app()->instance('company_context_source', 'web_auth');
            }
        });
    }
}