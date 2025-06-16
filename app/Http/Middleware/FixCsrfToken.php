<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FixCsrfToken
{
    public function handle(Request $request, Closure $next)
    {
        // Regenerate session if it's missing or expired
        if (!$request->hasSession() || !$request->session()->has('_token')) {
            $request->session()->regenerateToken();
        }
        
        return $next($request);
    }
}