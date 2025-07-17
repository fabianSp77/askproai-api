<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RefreshCsrfToken
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
        // For Livewire requests, ensure session and CSRF token are fresh
        if ($request->header('X-Livewire') || $request->is('livewire/*')) {
            // Regenerate session ID to prevent expiration
            if ($request->hasSession() && !$request->session()->has('_token')) {
                $request->session()->regenerateToken();
            }
            
            // Extend session lifetime
            if ($request->hasSession()) {
                $request->session()->put('last_activity', now());
            }
        }

        $response = $next($request);

        // Add CSRF token to response headers for Livewire
        if ($request->header('X-Livewire')) {
            $response->headers->set('X-CSRF-TOKEN', $request->session()->token());
        }

        return $response;
    }
}