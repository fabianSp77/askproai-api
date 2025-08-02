<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class CompanyContextServiceProvider extends ServiceProvider
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
        // Set company context after authentication
        $this->app['events']->listen('Illuminate\Auth\Events\Authenticated', function ($event) {
            if ($event->user && $event->user->company_id) {
                app()->instance('current_company_id', $event->user->company_id);
                app()->instance('company_context_source', 'auth_event');
                
                \Log::info('CompanyContextServiceProvider: Set context on auth', [
                    'user_id' => $event->user->id,
                    'company_id' => $event->user->company_id,
                ]);
            }
        });
        
        // Also check on every request
        $this->app['events']->listen('Illuminate\Foundation\Http\Events\RequestHandled', function ($event) {
            if (Auth::check() && Auth::user()->company_id && !app()->has('current_company_id')) {
                app()->instance('current_company_id', Auth::user()->company_id);
                app()->instance('company_context_source', 'request_handled_event');
                
                \Log::warning('CompanyContextServiceProvider: Had to set context in RequestHandled event', [
                    'user_id' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                ]);
            }
        });
        
        // Hook into route matched event
        $this->app['events']->listen('Illuminate\Routing\Events\RouteMatched', function ($event) {
            if (Auth::check() && Auth::user()->company_id && !app()->has('current_company_id')) {
                app()->instance('current_company_id', Auth::user()->company_id);
                app()->instance('company_context_source', 'route_matched_event');
                
                \Log::info('CompanyContextServiceProvider: Set context on route match', [
                    'user_id' => Auth::user()->id,
                    'company_id' => Auth::user()->company_id,
                    'route' => $event->route->getName() ?? $event->route->uri(),
                ]);
            }
        });
    }
}