<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Portal\PortalAuthService;

class PortalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // Register PortalAuthService as singleton
        $this->app->singleton(PortalAuthService::class, function ($app) {
            return new PortalAuthService();
        });

        // Register alias for easier access
        $this->app->alias(PortalAuthService::class, 'portal.auth');
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Add custom auth guard if needed
        if (!config('auth.guards.portal')) {
            config([
                'auth.guards.portal' => [
                    'driver' => 'session',
                    'provider' => 'portal_users',
                ],
                'auth.providers.portal_users' => [
                    'driver' => 'eloquent',
                    'model' => \App\Models\PortalUser::class,
                ],
            ]);
        }

        // Register session configuration for portal
        if ($this->app->runningInConsole()) {
            return;
        }

        // Apply portal session config when in business routes
        if (request()->is('business/*') || request()->is('api/business/*')) {
            config([
                'session.cookie' => 'portal_session',
                'session.table' => 'portal_sessions',
                'session.domain' => config('session.domain'),
                'session.path' => '/',
                'session.same_site' => 'lax',
                'session.http_only' => true,
                'session.secure' => request()->secure(),
            ]);
        }
    }
}