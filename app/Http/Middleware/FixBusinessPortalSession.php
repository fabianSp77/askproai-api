<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FixBusinessPortalSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only for business routes
        if (!$request->is('business/*') && !$request->is('business-api/*')) {
            return $next($request);
        }

        // Force proper session configuration for business portal
        config([
            'session.cookie' => 'askproai_portal_session',
            'session.path' => '/', // Changed from '/business' to '/' for broader compatibility
            'session.domain' => null, // Important: null allows cookie to work on subdomain
            'session.secure' => true,
            'session.same_site' => 'lax',
            'session.files' => storage_path('framework/sessions/portal'),
        ]);

        Log::debug('FixBusinessPortalSession - Configuration', [
            'url' => $request->url(),
            'session_cookie' => config('session.cookie'),
            'session_path' => config('session.path'),
            'session_domain' => config('session.domain'),
        ]);

        return $next($request);
    }
}