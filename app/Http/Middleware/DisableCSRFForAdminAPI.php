<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableCSRFForAdminAPI
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
        // Force the request to be treated as non-stateful for Sanctum
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        
        // Remove any CSRF cookie to prevent conflicts
        if ($request->hasCookie('XSRF-TOKEN')) {
            $request->cookies->remove('XSRF-TOKEN');
        }
        
        return $next($request);
    }
}