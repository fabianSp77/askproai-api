<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

class EnsurePortalApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Start session if not started
        if (!session()->isStarted()) {
            session()->start();
        }
        
        // Check if already authenticated
        if (Auth::guard('portal')->check()) {
            $this->ensureCompanyContext(Auth::guard('portal')->user());
            return $next($request);
        }
        
        // Try to authenticate from session
        // Laravel uses a specific session key format for auth
        $portalSessionKey = 'login_portal_59ba36addc2b2f9401580f014c7f58ea4e30989d';
        $userId = session($portalSessionKey) ?? session('portal_user_id') ?? session('portal_login');
        
        if ($userId) {
            // Must bypass CompanyScope since we're authenticating
            $user = PortalUser::withoutGlobalScopes()->find($userId);
            if ($user && $user->is_active) {
                // Check company is active
                $company = \App\Models\Company::withoutGlobalScopes()->find($user->company_id);
                if ($company && $company->is_active) {
                    Auth::guard('portal')->login($user);
                    
                    // Set company context
                    $this->ensureCompanyContext($user);
                    
                    return $next($request);
                }
            }
        }
        
        // If not authenticated, return 401
        return response()->json(['message' => 'Unauthenticated.'], 401);
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