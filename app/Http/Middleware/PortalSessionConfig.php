<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Business Portal Session Configuration
 * 
 * Configures session settings specifically for the business portal
 * to prevent conflicts with the admin portal.
 */
class PortalSessionConfig
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only configure if session hasn't started yet
        if (!session()->isStarted()) {
            // Portal-specific session configuration
            $sessionConfig = [
                'driver' => env('SESSION_DRIVER', 'redis'),
                'lifetime' => 480, // 8 hours for portal
                'expire_on_close' => false,
                'encrypt' => env('SESSION_ENCRYPT', true),
                'cookie' => env('PORTAL_SESSION_COOKIE', 'askproai_portal_session'),
                'path' => '/',
                'domain' => env('SESSION_DOMAIN', '.askproai.de'),
                'secure' => $request->secure(), // Auto-detect HTTPS
                'http_only' => true,
                'same_site' => 'lax',
                'files' => storage_path('framework/sessions'),
            ];
            
            // Apply configuration BEFORE session starts
            foreach ($sessionConfig as $key => $value) {
                config(["session.$key" => $value]);
            }
        }
        
        return $next($request);
    }
}