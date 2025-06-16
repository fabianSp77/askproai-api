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
        
        // Try to use Filament login route, fallback to /admin/login
        try {
            return route('filament.admin.auth.login');
        } catch (\Exception $e) {
            return '/admin/login';
        }
    }
}