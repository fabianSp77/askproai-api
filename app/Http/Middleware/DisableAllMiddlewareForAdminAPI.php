<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableAllMiddlewareForAdminAPI
{
    public function handle(Request $request, Closure $next)
    {
        // Skip all session-based middleware for admin API routes
        if ($request->is('api/admin/*')) {
            // Remove session from request
            $request->setLaravelSession(null);
            
            // Remove CSRF token validation
            config(['session.driver' => 'array']);
            
            // Ensure request is treated as stateless
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
            
            // Remove any session cookies
            foreach ($request->cookies->all() as $key => $value) {
                if (str_contains($key, 'session') || $key === 'XSRF-TOKEN') {
                    $request->cookies->remove($key);
                }
            }
        }
        
        return $next($request);
    }
}