<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSessionCookieResponse
{
    /**
     * Force Laravel to send session cookie with every response.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Get session store
        $session = $request->session();

        if ($session && method_exists($session, 'getId')) {
            $sessionId = $session->getId();
            $cookieName = config('session.cookie');

            // Check if we need to set a new cookie
            $currentCookie = $request->cookie($cookieName);

            // Decrypt current cookie to get session ID
            $currentSessionId = null;
            if ($currentCookie) {
                try {
                    $currentSessionId = app('encrypter')->decrypt($currentCookie, false);
                } catch (\Exception $e) {
                    // Cookie might be invalid
                }
            }

            // If session ID changed or no cookie exists, set new cookie
            if ($sessionId && ($currentSessionId !== $sessionId || ! $currentCookie)) {
                $cookie = cookie(
                    $cookieName,
                    $sessionId,
                    config('session.lifetime'),
                    config('session.path'),
                    config('session.domain'),
                    config('session.secure'),
                    config('session.http_only'),
                    false, // raw
                    config('session.same_site')
                );

                $response->headers->setCookie($cookie);
            }
        }

        return $response;
    }
}
