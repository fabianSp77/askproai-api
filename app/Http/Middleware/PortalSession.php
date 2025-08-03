<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PortalSession
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
        // Force start session with portal configuration
        if (!Session::isStarted()) {
            // Override session config for portal
            config([
                'session.cookie' => 'askproai_portal_session',
                'session.table' => 'portal_sessions',
                'session.lifetime' => 480, // 8 hours
            ]);
            
            Session::start();
        }
        
        return $next($request);
    }
}