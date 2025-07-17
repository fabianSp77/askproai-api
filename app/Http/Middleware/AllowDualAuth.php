<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AllowDualAuth
{
    /**
     * Allow admin users to view portal while staying logged in as admin
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is admin
        $adminUser = Auth::guard('web')->user();
        
        // Check if portal user is logged in
        $portalUser = Auth::guard('portal')->user();
        
        // If admin is logged in but no portal user, check for impersonation
        if ($adminUser && !$portalUser) {
            // Check if admin is trying to impersonate
            $impersonateId = session('admin_impersonate_portal_user');
            if ($impersonateId) {
                $portalUser = \App\Models\PortalUser::withoutGlobalScopes()->find($impersonateId);
                if ($portalUser && $portalUser->is_active) {
                    // Login as portal user WITHOUT logging out admin
                    Auth::guard('portal')->login($portalUser);
                    
                    // Set company context
                    app()->instance('current_company_id', $portalUser->company_id);
                    
                    // Store in session for persistence
                    session(['portal_user_id' => $portalUser->id]);
                }
            }
        }
        
        return $next($request);
    }
}