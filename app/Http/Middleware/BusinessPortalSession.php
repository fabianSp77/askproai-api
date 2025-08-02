<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessPortalSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to business routes
        if (!$request->is('business/*') && !$request->is('business-api/*')) {
            return $next($request);
        }

        // Configure portal session before it starts
        config([
            'session.cookie' => 'askproai_portal_session',
            'session.path' => '/',
            'session.domain' => '.askproai.de',
            'session.secure' => $request->secure(), // Dynamic based on request
            'session.http_only' => true,
            'session.same_site' => 'lax',
        ]);

        // Check if we have the portal session cookie
        $portalSessionId = $request->cookie('askproai_portal_session');
        
        if ($portalSessionId) {
            // If session is already started with wrong ID, we need to handle it
            if (session()->isStarted() && session()->getId() !== $portalSessionId) {
                Log::debug('BusinessPortalSession - Session mismatch, forcing correct session', [
                    'current_id' => session()->getId(),
                    'cookie_id' => $portalSessionId,
                ]);
                
                // Save and close current session
                session()->save();
                
                // Set the correct session ID from cookie
                session()->setId($portalSessionId);
                
                // Restart session with correct ID
                session()->start();
            } else if (!session()->isStarted()) {
                // Session not started yet, set ID before starting
                session()->setId($portalSessionId);
            }
            
            Log::debug('BusinessPortalSession - Using session from cookie', [
                'session_id' => $portalSessionId,
                'cookie_value' => substr($portalSessionId, 0, 8) . '...',
                'url' => $request->url(),
                'session_started' => session()->isStarted(),
            ]);
        } else {
            Log::debug('BusinessPortalSession - No existing session cookie', [
                'url' => $request->url(),
                'cookies' => array_keys($request->cookies->all()),
            ]);
        }
        
        // Process request
        $response = $next($request);
        
        // After processing, ensure we have a portal session cookie
        if (session()->isStarted()) {
            $sessionId = session()->getId();
            
            // Determine secure flag based on current request
            $isSecure = $request->secure();
            
            // Always set/refresh the portal session cookie with proper domain
            $cookie = cookie(
                'askproai_portal_session',  // name
                $sessionId,                  // value
                config('session.lifetime', 120), // minutes
                '/',                         // path
                '.askproai.de',             // domain - explicit subdomain support
                $isSecure,                   // secure - based on request
                true,                        // httpOnly
                false,                       // raw
                'lax'                        // sameSite
            );
            
            $response->headers->setCookie($cookie);
            
            Log::debug('BusinessPortalSession - Cookie set/refreshed', [
                'session_id' => $sessionId,
                'url' => $request->url(),
                'secure' => $isSecure,
                'domain' => '.askproai.de',
                'lifetime' => config('session.lifetime', 120),
            ]);
        }
        
        return $response;
    }
}