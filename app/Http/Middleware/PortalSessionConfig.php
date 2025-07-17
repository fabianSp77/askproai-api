<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Configure Portal-specific session settings
 */
class PortalSessionConfig
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
        // Load portal-specific session configuration
        $portalConfig = config('session_portal');
        
        if ($portalConfig) {
            // Override default session configuration
            foreach ($portalConfig as $key => $value) {
                Config::set('session.' . $key, $value);
            }
        }
        
        return $next($request);
    }
}
