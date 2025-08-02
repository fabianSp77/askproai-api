<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnsurePortalSessionCookie
{
    /**
     * This middleware runs LAST to ensure the portal session cookie is set
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only for business routes
        if ($request->is('business/*') || $request->is('business-api/*')) {
            if (session()->isStarted()) {
                $sessionId = session()->getId();
                
                // Determine secure flag based on current request
                $isSecure = $request->secure();
                
                // For redirect responses, use withCookie to ensure cookie is set
                if ($response instanceof \Illuminate\Http\RedirectResponse) {
                    $response = $response->withCookie(
                        cookie(
                            'askproai_portal_session',
                            $sessionId,
                            config('session.lifetime', 120),
                            '/',
                            null, // Let Laravel determine domain
                            $isSecure,
                            true,
                            false,
                            'lax'
                        )
                    );
                } else {
                    // For regular responses, set cookie on headers
                    $response->headers->setCookie(
                        cookie(
                            'askproai_portal_session',
                            $sessionId,
                            config('session.lifetime', 120),
                            '/',
                            null, // Let Laravel determine domain
                            $isSecure,
                            true,
                            false,
                            'lax'
                        )
                    );
                }
                
                Log::debug('EnsurePortalSessionCookie - Cookie set', [
                    'session_id' => $sessionId,
                    'url' => $request->url(),
                    'is_redirect' => $response instanceof \Illuminate\Http\RedirectResponse,
                    'status' => method_exists($response, 'status') ? $response->status() : 'n/a',
                ]);
            }
        }
        
        return $response;
    }
}