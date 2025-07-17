<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RefreshCsrfTokenForPortal
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Ensure session has started
        if (!session()->isStarted()) {
            session()->start();
        }
        
        // Regenerate CSRF token if missing
        if (!session()->has('_token')) {
            session()->regenerateToken();
        }
        
        $response = $next($request);
        
        // Add CSRF token to response headers for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            $response->headers->set('X-CSRF-TOKEN', csrf_token());
        }
        
        return $response;
    }
}