<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

/**
 * Share Portal Session between Web and API
 * 
 * This middleware ensures that the portal session is available
 * for API requests by manually restoring the authenticated user
 * from the session data.
 */
class SharePortalSession
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
        // If already authenticated, continue
        if (Auth::guard('portal')->check()) {
            $user = Auth::guard('portal')->user();
            if ($user && $user->company_id) {
                app()->instance('current_company_id', $user->company_id);
            }
            return $next($request);
        }
        
        // Try to restore from session
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        $userId = session($portalSessionKey) ?? session('portal_user_id');
        
        if ($userId) {
            $user = PortalUser::withoutGlobalScopes()->find($userId);
            
            if ($user && $user->is_active) {
                // Check if company is active
                if ($user->company && $user->company->is_active) {
                    Auth::guard('portal')->login($user);
                    app()->instance('current_company_id', $user->company_id);
                }
            }
        }
        
        // Also check for admin viewing
        if (!Auth::guard('portal')->check() && session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            if ($companyId) {
                app()->instance('current_company_id', $companyId);
            }
        }
        
        return $next($request);
    }
}