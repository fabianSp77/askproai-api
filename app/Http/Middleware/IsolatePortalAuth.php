<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsolatePortalAuth
{
    /**
     * Isolate portal auth from admin auth to prevent conflicts
     */
    public function handle(Request $request, Closure $next)
    {
        // Only for business routes
        if ($request->is('business/*') || $request->is('business/api/*')) {
            // If admin user is logged in, temporarily logout from web guard
            // to prevent auth conflicts
            if (Auth::guard('web')->check()) {
                \Log::debug('IsolatePortalAuth: Admin user detected in portal, isolating sessions', [
                    'admin_user' => Auth::guard('web')->user()->email,
                    'url' => $request->url(),
                ]);
                
                // Don't logout, just ensure we use the correct guard
                // by setting a flag
                $request->attributes->set('portal_isolated', true);
            }
            
            // Ensure we're using portal guard for all auth checks
            Auth::shouldUse('portal');
        }
        
        return $next($request);
    }
}