<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InitializePortalSession
{
    /**
     * Handle an incoming request.
     * This middleware MUST run before StartSession
     */
    public function handle(Request $request, Closure $next)
    {
        // Only for business routes
        if ($request->is('business/*') || $request->is('business-api/*')) {
            // Set session config BEFORE session starts
            config([
                'session.cookie' => 'askproai_portal_session',
                'session.path' => '/',
                'session.domain' => null,
                'session.secure' => true,
                'session.same_site' => 'lax',
                'session.files' => storage_path('framework/sessions/portal'),
                'session.driver' => 'file',
                'session.http_only' => true,
                'session.lifetime' => 480, // 8 hours
                'session.expire_on_close' => false,
            ]);
            
            // Also ensure the session manager gets reconfigured
            if (app()->bound('session')) {
                $sessionManager = app('session');
                $sessionManager->setDefaultDriver('file');
            }
            
            Log::debug('InitializePortalSession - Config set before session start', [
                'url' => $request->url(),
                'cookie_name' => config('session.cookie'),
                'session_path' => config('session.path'),
                'session_driver' => config('session.driver'),
                'files_path' => config('session.files'),
            ]);
        }
        
        return $next($request);
    }
}