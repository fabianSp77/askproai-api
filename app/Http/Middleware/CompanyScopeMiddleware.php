<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Scopes\TenantScope;

class CompanyScopeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();
            $currentCompanyId = session('current_company');
            
            // If no company in session, use user's default company
            if (!$currentCompanyId) {
                $currentCompanyId = $user->company_id;
                session(['current_company' => $currentCompanyId]);
            }
            
            // Validate user has access to this company
            if (!$this->userCanAccessCompany($user, $currentCompanyId)) {
                // Reset to user's own company
                $currentCompanyId = $user->company_id;
                session(['current_company' => $currentCompanyId]);
            }
            
            // Set the tenant scope for the current request
            TenantScope::$tenantId = $currentCompanyId;
            
            // Share current company with all views
            view()->share('currentCompany', Company::find($currentCompanyId));
        }
        
        return $next($request);
    }
    
    /**
     * Check if user can access the given company
     */
    private function userCanAccessCompany($user, $companyId): bool
    {
        // Super admin can access all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // User can access their own company
        if ($user->company_id == $companyId) {
            return true;
        }
        
        // Reseller users can access child companies
        if ($user->hasRole(['reseller_owner', 'reseller_admin', 'reseller_support']) && 
            $user->company && $user->company->isReseller()) {
            
            $childCompanyIds = $user->company->childCompanies()->pluck('id')->toArray();
            return in_array($companyId, $childCompanyIds);
        }
        
        return false;
    }
}