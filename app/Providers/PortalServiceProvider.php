<?php

namespace App\Providers;

use App\Services\Portal\PortalAuthService;
use Illuminate\Support\ServiceProvider;

class PortalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // Register PortalAuthService as singleton
        $this->app->singleton(PortalAuthService::class, function ($app) {
            return new PortalAuthService;
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
        if (! config('auth.guards.portal')) {
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
            // Use the portal-specific session configuration
            $portalSessionConfig = config('session_portal');
            if ($portalSessionConfig) {
                config([
                    'session.driver' => $portalSessionConfig['driver'],
                    'session.cookie' => $portalSessionConfig['cookie'],
                    'session.table' => $portalSessionConfig['table'] ?? 'portal_sessions',
                    'session.domain' => $portalSessionConfig['domain'] ?? config('session.domain'),
                    'session.path' => $portalSessionConfig['path'],
                    'session.same_site' => $portalSessionConfig['same_site'],
                    'session.http_only' => $portalSessionConfig['http_only'],
                    'session.secure' => $portalSessionConfig['secure'],
                    'session.lifetime' => $portalSessionConfig['lifetime'],
                    'session.files' => $portalSessionConfig['files'],
                ]);
            }
        }
    }
}
