<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnsurePortalSession
{
    public function handle(Request $request, Closure $next)
    {
        // Only for business portal routes
        if (!$request->is('business/*')) {
            return $next($request);
        }

        // Ensure we're using the portal session configuration
        if (config('session.cookie') !== 'askproai_portal_session') {
            config([
                'session.cookie' => 'askproai_portal_session',
                'session.path' => '/',
                'session.domain' => null,
                'session.files' => storage_path('framework/sessions/portal'),
            ]);
        }

        // Laravel automatically decrypts the session cookie, so we should not manually set the ID
        // The StartSession middleware will handle this correctly
        
        Log::debug('EnsurePortalSession - Pre-Session', [
            'url' => $request->url(),
            'has_portal_cookie' => $request->hasCookie('askproai_portal_session'),
            'session_config' => [
                'cookie' => config('session.cookie'),
                'files' => config('session.files'),
            ],
        ]);

        $response = $next($request);
        
        // After middleware chain, log session state
        Log::debug('EnsurePortalSession - Post-Session', [
            'session_id' => session()->getId(),
            'session_started' => session()->isStarted(),
            'auth_check' => auth()->guard('portal')->check(),
            'session_keys' => array_keys(session()->all()),
        ]);

        return $response;
    }
}