<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Configure Admin-specific session settings
 */
class AdminSessionConfig
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
        // Load admin-specific session configuration
        $adminConfig = config('session_admin');
        
        if ($adminConfig) {
            // Override default session configuration
            foreach ($adminConfig as $key => $value) {
                Config::set('session.' . $key, $value);
            }
        }
        
        return $next($request);
    }
}