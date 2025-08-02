<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Unified Session Configuration for both Admin and Business Portals
 * 
 * This middleware ensures consistent session handling across the entire application
 * eliminating conflicts between admin and portal sessions.
 */
class UnifiedSessionConfig
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Determine the context based on the URL
        $isPortal = $request->is('business/*') || $request->is('business-api/*');
        $isAdmin = $request->is('admin/*');
        
        // Use a single session cookie with context-based prefixes for data
        $sessionConfig = [
            'driver' => config('session.driver', 'file'),
            'lifetime' => 480, // 8 hours
            'expire_on_close' => false,
            'encrypt' => false, // Let EncryptCookies middleware handle this
            'cookie' => 'askproai_session', // ONE cookie for all
            'path' => '/',
            'domain' => null, // Current domain
            'secure' => $request->secure(), // Auto-detect HTTPS
            'http_only' => true,
            'same_site' => 'lax',
            'files' => storage_path('framework/sessions'),
        ];
        
        // Apply the configuration
        foreach ($sessionConfig as $key => $value) {
            config(["session.$key" => $value]);
        }
        
        // Log configuration for debugging
        \Log::debug('UnifiedSessionConfig applied', [
            'url' => $request->url(),
            'is_portal' => $isPortal,
            'is_admin' => $isAdmin,
            'secure' => $sessionConfig['secure'],
            'cookie' => $sessionConfig['cookie'],
        ]);
        
        return $next($request);
    }
}