<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BypassAuthorizationForAdmin
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
        // For admin API routes, disable automatic policy checks
        if ($request->is('api/admin/*')) {
            // Temporarily disable authorization
            app()['auth']->shouldUse('sanctum');
        }

        return $next($request);
    }
}