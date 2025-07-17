<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

class PortalApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Force session start if not started
        if (!session()->isStarted()) {
            session()->start();
        }
        
        // Check if already authenticated
        if (Auth::guard('portal')->check()) {
            $this->ensureCompanyContext(Auth::guard('portal')->user());
            return $next($request);
        }
        
        // Get the Laravel auth session key for portal
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        
        // Try multiple sources for user ID
        $userId = session($portalSessionKey) ?? session('portal_user_id') ?? session('portal_login');
        
        // Also check if we have admin viewing a portal
        $isAdminViewing = session('is_admin_viewing');
        $adminImpersonation = session('admin_impersonation');
        if ($isAdminViewing && $adminImpersonation && isset($adminImpersonation['portal_user_id'])) {
            $userId = $adminImpersonation['portal_user_id'];
        }
        
        if ($userId) {
            // Load user without scopes
            $user = PortalUser::withoutGlobalScopes()->find($userId);
            
            if ($user && $user->is_active) {
                // Check company is active
                $company = \App\Models\Company::withoutGlobalScopes()->find($user->company_id);
                if ($company && $company->is_active) {
                    // Login the user for this request
                    Auth::guard('portal')->login($user);
                    
                    // Set company context
                    $this->ensureCompanyContext($user);
                    
                    return $next($request);
                }
            }
        }
        
        // Check if we have a web user who might be conflicting
        $webUser = Auth::guard('web')->user();
        if ($webUser) {
            // If we have portal session data but web user is logged in, switch to portal
            if ($userId) {
                // Logout web user to avoid conflicts
                Auth::guard('web')->logout();
                
                // Re-attempt portal login
                $user = PortalUser::withoutGlobalScopes()->find($userId);
                if ($user && $user->is_active) {
                    $company = \App\Models\Company::withoutGlobalScopes()->find($user->company_id);
                    if ($company && $company->is_active) {
                        Auth::guard('portal')->login($user);
                        $this->ensureCompanyContext($user);
                        return $next($request);
                    }
                }
            } else if ($webUser->hasRole('Super Admin') && $adminImpersonation && isset($adminImpersonation['company_id'])) {
                // Admin accessing portal - allow but set context
                app()->instance('current_company_id', $adminImpersonation['company_id']);
                return $next($request);
            } else if ($webUser->hasRole('Super Admin') && (!$adminImpersonation || !isset($adminImpersonation['company_id']))) {
                // Admin directly accessing business portal without proper session
                // Get first active company for admin access
                $company = \App\Models\Company::withoutGlobalScopes()
                    ->where('is_active', true)
                    ->first();
                    
                if ($company) {
                    // Set up temporary admin viewing session
                    session([
                        'is_admin_viewing' => true,
                        'admin_impersonation' => [
                            'admin_id' => $webUser->id,
                            'company_id' => $company->id,
                            'admin_session' => true,
                            'started_at' => now(),
                        ]
                    ]);
                    
                    app()->instance('current_company_id', $company->id);
                    return $next($request);
                }
            }
        }
        
        // If not authenticated, return 401
        return response()->json([
            'message' => 'Unauthenticated.',
            'debug' => [
                'session_id' => session()->getId(),
                'has_portal_session' => session()->has($portalSessionKey),
                'portal_user_id' => session('portal_user_id'),
                'user_found' => isset($user),
                'is_admin_viewing' => $isAdminViewing,
                'web_user' => $webUser ? $webUser->email : null
            ]
        ], 401);
    }
    
    private function ensureCompanyContext($user)
    {
        // Set company context
        app()->instance('current_company_id', $user->company_id);
        
        // Set branch context if not set
        if (!session('current_branch_id')) {
            $branch = \App\Models\Branch::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->first();
                
            if ($branch) {
                session(['current_branch_id' => $branch->id]);
            }
        }
    }
}