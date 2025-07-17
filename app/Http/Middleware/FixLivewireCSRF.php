<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FixLivewireCSRF
{
    /**
     * Fix Livewire CSRF issues
     */
    public function handle(Request $request, Closure $next)
    {
        // For Livewire requests, always refresh CSRF token
        if ($request->header('X-Livewire') || $request->is('livewire/*')) {
            // Regenerate token if missing
            if (!$request->session()->has('_token')) {
                $request->session()->regenerateToken();
            }
            
            // Add CSRF token to response headers
            $response = $next($request);
            
            if (method_exists($response, 'header')) {
                $response->header('X-CSRF-TOKEN', csrf_token());
            }
            
            return $response;
        }
        
        return $next($request);
    }
}