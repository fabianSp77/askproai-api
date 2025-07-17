<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableSessionForAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // If it's an admin route and POST request, disable CSRF
        if ($request->is('admin/*') && $request->isMethod('POST')) {
            // Skip CSRF verification
            $request->session()->token(); // Generate token to prevent errors
            config(['session.driver' => 'array']); // Use array driver (in-memory)
        }
        
        return $next($request);
    }
}