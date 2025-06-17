<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class EnsureTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Try to determine company context from various sources
        $companyId = $this->determineCompanyId($request);
        
        if ($companyId) {
            // Bind company ID to container for use in TenantScope
            app()->instance('current_company_id', $companyId);
            
            // Also set in session for persistence
            session(['current_company_id' => $companyId]);
        }
        
        return $next($request);
    }
    
    /**
     * Determine company ID from various sources
     */
    protected function determineCompanyId(Request $request): ?int
    {
        // 1. From authenticated user
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->company_id) {
                return $user->company_id;
            }
        }
        
        // 2. From request header (API requests)
        if ($request->hasHeader('X-Company-ID')) {
            $companyId = (int) $request->header('X-Company-ID');
            
            // Validate that user has access to this company
            if (Auth::check() && !Auth::user()->hasRole(['super_admin', 'reseller'])) {
                if (Auth::user()->company_id !== $companyId) {
                    abort(403, 'Access denied to this company');
                }
            }
            
            return $companyId;
        }
        
        // 3. From subdomain
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        
        if ($subdomain && $subdomain !== 'www' && $subdomain !== 'api') {
            $company = Company::where('slug', $subdomain)->first();
            if ($company) {
                return $company->id;
            }
        }
        
        // 4. From session (web requests)
        if (session()->has('current_company_id')) {
            return session('current_company_id');
        }
        
        // 5. From route parameter
        if ($request->route('company')) {
            $company = $request->route('company');
            return $company instanceof Company ? $company->id : (int) $company;
        }
        
        return null;
    }
}