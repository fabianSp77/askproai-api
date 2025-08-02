<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Admin Portal Session Configuration
 * 
 * Configures session settings specifically for the admin portal
 * to prevent conflicts with the business portal.
 */
class AdminSessionConfig
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only configure if session hasn't started yet
        if (!session()->isStarted()) {
            // Admin-specific session configuration
            $sessionConfig = [
                'driver' => config('session.driver', 'file'),
                'lifetime' => 480, // 8 hours for admin
                'expire_on_close' => false,
                'encrypt' => false,
                'cookie' => 'askproai_admin_session',
                'path' => '/',
                'domain' => null,
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