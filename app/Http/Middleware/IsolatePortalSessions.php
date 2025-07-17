<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsolatePortalSessions
{
    public function handle(Request $request, Closure $next)
    {
        // If accessing admin routes, ensure portal session doesn't interfere
        if ($request->is('admin/*') || $request->is('admin')) {
            // Don't use portal guard in admin area
            if (auth()->guard('portal')->check() && !session('is_admin_viewing')) {
                auth()->guard('portal')->logout();
            }
        }
        
        // If accessing business routes, ensure web guard doesn't interfere  
        if ($request->is('business/*') || $request->is('business')) {
            // Only logout web guard if not admin viewing portal
            if (auth()->guard('web')->check() && !session('is_admin_viewing')) {
                auth()->guard('web')->logout();
            }
        }
        
        return $next($request);
    }
}