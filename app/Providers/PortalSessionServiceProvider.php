<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PortalSessionServiceProvider extends ServiceProvider
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
        // Configure portal session for business routes
        $this->app->booted(function () {
            $request = $this->app['request'];
            
            // Apply portal session config for business routes
            if ($request->is('business/*') || $request->is('business-api/*')) {
                config([
                    'session.cookie' => 'askproai_portal_session',
                    'session.files' => storage_path('framework/sessions/portal'),
                    'session.path' => '/',
                    'session.domain' => '.askproai.de',
                    'session.secure' => $request->secure(),
                    'session.http_only' => true,
                    'session.same_site' => 'lax',
                    'session.lifetime' => 480, // 8 hours for portal
                ]);
                
                \Log::debug('PortalSessionServiceProvider: Applied portal session config', [
                    'url' => $request->url(),
                    'cookie' => config('session.cookie'),
                    'files' => config('session.files'),
                    'domain' => config('session.domain'),
                ]);
            }
        });
    }
}