<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Always allow API calls
        if ($request->is('business/api/*') || $request->is('api/business/*')) {
            return $next($request);
        }

        // Check if user is authenticated
        if (auth()->guard('portal')->check()) {
            return $next($request);
        }

        // REMOVED: Dangerous auto-login for demo user

        // Only redirect if not expecting JSON
        if (! $request->expectsJson()) {
            return redirect()->route('business.login');
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
