<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Support\Facades\Cookie as CookieFacade;

class ForceSessionCookie
{
    /**
     * Force session cookie to be set on every response
     * This is a more aggressive approach to ensure cookies are sent
     */
    public function handle(Request $request, Closure $next)
    {
        // Process the request
        $response = $next($request);
        
        // Get the session
        $session = $request->session();
        
        if ($session) {
            $sessionId = $session->getId();
            $cookieName = config('session.cookie');
            
            // Check if we need to update the cookie
            $currentCookie = $request->cookie($cookieName);
            
            // Encrypt the session ID properly
            $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
            
            try {
                // Decrypt current cookie if exists
                $decryptedCurrent = $currentCookie ? $encrypter->decrypt($currentCookie, false) : null;
                
                // Only set new cookie if session ID changed
                if ($decryptedCurrent !== $sessionId) {
                    // Create properly encrypted Laravel cookie
                    $cookie = cookie(
                        $cookieName,
                        $encrypter->encrypt($sessionId, false),
                        config('session.lifetime'),
                        config('session.path'),
                        config('session.domain'),
                        config('session.secure'),
                        config('session.http_only'),
                        false,
                        config('session.same_site')
                    );
                    
                    // Add cookie to response
                    $response = $response->withCookie($cookie);
                }
            } catch (\Exception $e) {
                // If decryption fails, set new cookie
                $cookie = cookie(
                    $cookieName,
                    $encrypter->encrypt($sessionId, false),
                    config('session.lifetime'),
                    config('session.path'),
                    config('session.domain'),
                    config('session.secure'),
                    config('session.http_only'),
                    false,
                    config('session.same_site')
                );
                
                $response = $response->withCookie($cookie);
            }
        }
        
        return $response;
    }
}