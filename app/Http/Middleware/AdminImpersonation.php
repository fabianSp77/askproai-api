<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\PortalUser;

/**
 * Dedicated middleware for admin impersonation in Business Portal
 * Handles admin viewing portal as specific company/user
 */
class AdminImpersonation
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
        // Only process if admin is authenticated
        $adminUser = Auth::guard('web')->user();
        
        if (!$adminUser || !$adminUser->hasRole('Super Admin')) {
            return $next($request);
        }
        
        // Check for impersonation session
        if (!session()->has('admin_impersonation')) {
            return $next($request);
        }
        
        $impersonation = session('admin_impersonation');
        
        // Validate impersonation data
        if (!isset($impersonation['company_id']) || !isset($impersonation['admin_id'])) {
            session()->forget('admin_impersonation');
            return $next($request);
        }
        
        // Verify admin is still the same
        if ($impersonation['admin_id'] !== $adminUser->id) {
            session()->forget('admin_impersonation');
            return $next($request);
        }
        
        // Set company context
        $company = Company::withoutGlobalScopes()->find($impersonation['company_id']);
        if (!$company || !$company->is_active) {
            session()->forget('admin_impersonation');
            return redirect()->route('admin.companies.index')
                ->with('error', 'Die ausgewählte Firma ist nicht mehr aktiv.');
        }
        
        // Bind company context for the request
        app()->instance('current_company_id', $company->id);
        
        // If portal user is specified, login as that user
        if (isset($impersonation['portal_user_id'])) {
            $portalUser = PortalUser::withoutGlobalScopes()
                ->where('id', $impersonation['portal_user_id'])
                ->where('company_id', $company->id)
                ->first();
                
            if ($portalUser && $portalUser->is_active) {
                // Login portal user for this request only
                Auth::guard('portal')->login($portalUser);
            }
        }
        
        // Add impersonation indicator to view
        view()->share('is_admin_impersonation', true);
        view()->share('admin_user', $adminUser);
        view()->share('impersonated_company', $company);
        
        return $next($request);
    }
    
    /**
     * Start admin impersonation session
     */
    public static function start($adminId, $companyId, $portalUserId = null)
    {
        session([
            'admin_impersonation' => [
                'admin_id' => $adminId,
                'company_id' => $companyId,
                'portal_user_id' => $portalUserId,
                'started_at' => now()->toISOString(),
            ],
            'is_admin_viewing' => true,
        ]);
    }
    
    /**
     * Stop admin impersonation session
     */
    public static function stop()
    {
        session()->forget(['admin_impersonation', 'is_admin_viewing']);
        
        // Logout from portal guard if logged in
        if (Auth::guard('portal')->check()) {
            Auth::guard('portal')->logout();
        }
    }
    
    /**
     * Check if currently impersonating
     */
    public static function isImpersonating(): bool
    {
        return session()->has('admin_impersonation') && session('is_admin_viewing');
    }
    
    /**
     * Get current impersonation data
     */
    public static function getImpersonation(): ?array
    {
        return session('admin_impersonation');
    }
}