<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FixSessionCookieAfterRegeneration
{
    /**
     * This middleware runs BEFORE EncryptCookies to ensure session cookie is properly set
     */
    public function handle(Request $request, Closure $next)
    {
        // Let the request process first
        $response = $next($request);
        
        // Check if session was started
        if ($request->hasSession()) {
            $session = $request->session();
            $sessionId = $session->getId();
            $cookieName = config('session.cookie');
            
            // Check if a new session ID was generated (e.g., after login)
            $sessionIdChanged = false;
            
            // Get current cookie value
            $currentCookieValue = $request->cookie($cookieName);
            
            if ($currentCookieValue) {
                try {
                    // Try to decrypt current cookie
                    $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
                    $decryptedValue = $encrypter->decrypt($currentCookieValue, false);
                    
                    if ($decryptedValue !== $sessionId) {
                        $sessionIdChanged = true;
                    }
                } catch (\Exception $e) {
                    // Cookie couldn't be decrypted, assume it's invalid
                    $sessionIdChanged = true;
                }
            } else {
                // No cookie present
                $sessionIdChanged = true;
            }
            
            // If session ID changed, queue a new cookie
            if ($sessionIdChanged) {
                \Cookie::queue(
                    $cookieName,
                    $sessionId,
                    config('session.lifetime', 120),
                    config('session.path', '/'),
                    config('session.domain'),
                    config('session.secure', false),
                    config('session.http_only', true),
                    false,
                    config('session.same_site', 'lax')
                );
            }
        }
        
        return $response;
    }
}