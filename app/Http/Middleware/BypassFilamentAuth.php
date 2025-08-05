<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BypassFilamentAuth
{
    /**
     * Force authentication for Filament admin panel.
     */
    public function handle(Request $request, Closure $next)
    {
        // SECURITY FIX: Disabled auto-login bypass
        // This middleware was automatically logging in users without authentication
        // which is a CRITICAL security vulnerability
        
        // Simply pass through without any authentication bypass
        return $next($request);
    }
}
