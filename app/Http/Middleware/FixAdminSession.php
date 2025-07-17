<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FixAdminSession
{
    /**
     * Handle an incoming request.
     * Fix session issues for admin panel
     */
    public function handle(Request $request, Closure $next)
    {
        // Ensure session is started
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session.store'));
        }
        
        // Ensure CSRF token exists
        if (!$request->session()->has('_token')) {
            $request->session()->regenerateToken();
        }
        
        // For admin routes, ensure session is properly configured
        if ($request->is('admin/*') || $request->is('livewire/*')) {
            // Force session driver to file if having issues
            if (config('session.driver') !== 'file') {
                config(['session.driver' => 'file']);
            }
            
            // Ensure session cookie is set
            if (!$request->hasCookie(config('session.cookie'))) {
                $request->session()->regenerate();
            }
        }
        
        return $next($request);
    }
}