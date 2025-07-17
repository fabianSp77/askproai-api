<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BypassSanctumForAdminAPI
{
    public function handle(Request $request, Closure $next)
    {
        // For admin API routes, bypass Sanctum's stateful check
        if ($request->is('api/admin/*')) {
            // Force request to be non-stateful
            config(['sanctum.stateful' => []]);
            
            // Remove referrer check
            $request->headers->remove('referer');
            
            // Ensure it's treated as an API request
            $request->headers->set('Accept', 'application/json');
        }
        
        return $next($request);
    }
}