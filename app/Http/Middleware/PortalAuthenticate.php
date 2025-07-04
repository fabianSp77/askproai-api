<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Allow admin viewing without portal user
        if (session('is_admin_viewing') && session('admin_impersonation')) {
            $adminImpersonation = session('admin_impersonation');
            
            // Set company context for admin viewing
            if (isset($adminImpersonation['company_id'])) {
                // Only bind to container for admin viewing (setTrustedCompanyContext only works in console)
                app()->instance('current_company_id', $adminImpersonation['company_id']);
            }
            
            return $next($request);
        }
        
        if (!Auth::guard('portal')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            
            return redirect()->guest(route('business.login'));
        }
        
        // Check if user is active
        $user = Auth::guard('portal')->user();
        if (!$user->is_active) {
            Auth::guard('portal')->logout();
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Account deactivated.'], 403);
            }
            
            return redirect()->route('business.login')
                ->with('error', 'Ihr Konto wurde deaktiviert.');
        }
        
        // Set company context for portal users
        if ($user->company_id) {
            // Bind company context to container
            app()->instance('current_company_id', $user->company_id);
        }
        
        return $next($request);
    }
}