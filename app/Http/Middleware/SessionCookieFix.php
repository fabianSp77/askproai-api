<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionCookieFix
{
    /**
     * Handle session cookie properly after regeneration
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only process if we have a session
        if (!$request->hasSession()) {
            return $response;
        }
        
        $session = $request->session();
        $sessionId = $session->getId();
        $cookieName = config('session.cookie');
        
        // Check if we need to update the cookie
        $shouldUpdateCookie = false;
        
        // Check current cookie
        $currentCookie = $request->cookie($cookieName);
        if (!$currentCookie) {
            $shouldUpdateCookie = true;
        } else {
            try {
                // Decrypt current cookie value
                $payload = app('encrypter')->decrypt($currentCookie, false);
                
                // Extract session ID from payload (format: session_id|guard_hash)
                if (strpos($payload, '|') !== false) {
                    list($cookieSessionId, $guardHash) = explode('|', $payload, 2);
                } else {
                    $cookieSessionId = $payload;
                }
                
                // If session ID changed, we need to update
                if ($cookieSessionId !== $sessionId) {
                    $shouldUpdateCookie = true;
                }
            } catch (\Exception $e) {
                // Cookie is invalid or can't be decrypted
                $shouldUpdateCookie = true;
            }
        }
        
        // Update cookie if needed
        if ($shouldUpdateCookie) {
            // Get the guard
            $guard = Auth::guard();
            $guardName = Auth::getDefaultDriver();
            
            // Create the payload
            $payload = $sessionId;
            
            // If user is authenticated, add guard hash
            if (Auth::check()) {
                $recaller = $guard->getRecallerName();
                $hasher = hash_hmac('sha256', $guardName . '|' . $sessionId, $recaller);
                $payload = $sessionId . '|' . $hasher;
            }
            
            // Queue the cookie
            \Cookie::queue(
                $cookieName,
                $payload,
                config('session.lifetime', 120),
                config('session.path', '/'),
                config('session.domain'),
                config('session.secure', false),
                config('session.http_only', true),
                false,
                config('session.same_site', 'lax')
            );
        }
        
        return $response;
    }
}