<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }
        
        // Check if this is a business portal route
        if ($request->is('business/*') || $request->routeIs('business.*')) {
            return route('business.login');
        }
        
        // Check if this is a portal/customer route
        if ($request->is('portal/*') || $request->routeIs('portal.*')) {
            return route('portal.login');
        }
        
        // Try to use Filament login route, fallback to /admin/login
        try {
            return route('filament.admin.auth.login');
        } catch (\Exception $e) {
            return '/admin/login';
        }
    }
}