<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Cookie;

class ForcePortalSessionCookie
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

        // Force session configuration
        config([
            'session.cookie' => 'askproai_portal_session',
            'session.path' => '/',
            'session.domain' => null,
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'session.driver' => 'file',
            'session.files' => storage_path('framework/sessions/portal'),
        ]);

        $response = $next($request);

        // After processing, ensure session cookie is set
        if (session()->isStarted() && !$request->cookies->has('askproai_portal_session')) {
            $sessionId = session()->getId();
            
            // Create the session cookie
            $cookie = cookie(
                'askproai_portal_session',
                $sessionId,
                config('session.lifetime', 120),
                '/',
                null, // domain
                true, // secure
                true, // httpOnly
                false, // raw
                'lax' // sameSite
            );
            
            $response->withCookie($cookie);
            
            Log::debug('ForcePortalSessionCookie - Cookie set', [
                'session_id' => $sessionId,
                'cookie_name' => 'askproai_portal_session',
            ]);
        }

        return $response;
    }
}