<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableFilamentCSRF
{
    /**
     * Completely disable CSRF for Filament admin panel
     */
    public function handle(Request $request, Closure $next)
    {
        // For admin and livewire routes, ensure CSRF token exists
        if ($request->is('admin/*') || $request->is('livewire/*')) {
            // Ensure CSRF token exists (don't regenerate on every request!)
            if (!$request->session()->has('_token')) {
                $request->session()->regenerateToken();
            }
            
            // Add CSRF token to response headers for Livewire
            $response = $next($request);
            
            if (method_exists($response, 'header')) {
                $response->header('X-CSRF-TOKEN', csrf_token());
            }
            
            return $response;
        }
        
        return $next($request);
    }
    
    /**
     * Skip CSRF token verification
     */
    public function tokensMatch($request)
    {
        return true;
    }
}