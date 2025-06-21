<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Exceptions\MissingTenantException;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    /**
     * Routes that don't require tenant context
     */
    protected array $excludedRoutes = [
        'login',
        'register',
        'password.*',
        'verification.*',
        'admin.company.select',
        'admin.company.switch',
        'api.health',
        'api.metrics',
        'portal.login',
        'portal.login.submit',
        'portal.magic-link',
        'portal.magic-link.verify',
        'portal.password.*',
        'portal.cookie-policy',
        'portal.privacy-policy',
    ];
    
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip for excluded routes
        if ($this->shouldSkip($request)) {
            return $next($request);
        }
        
        // Check if we have tenant context
        $companyId = $this->getCurrentCompanyId($request);
        
        if (!$companyId) {
            throw new MissingTenantException(
                'Tenant context is required for this request'
            );
        }
        
        // Store in container for easy access
        app()->instance('current_company_id', $companyId);
        
        // Add to response headers for debugging
        $response = $next($request);
        
        if ($response instanceof Response) {
            $response->headers->set('X-Company-ID', $companyId);
        }
        
        return $response;
    }
    
    /**
     * Check if the request should skip tenant validation
     */
    protected function shouldSkip(Request $request): bool
    {
        // Super admins can access without tenant
        if ($request->user()?->hasRole('super_admin')) {
            return true;
        }
        
        // Check excluded routes
        foreach ($this->excludedRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get current company ID from various sources
     */
    protected function getCurrentCompanyId(Request $request): ?int
    {
        // 1. From authenticated user
        if ($request->user()) {
            if ($request->user()->company_id) {
                return $request->user()->company_id;
            }
        }
        
        // 2. From request header (API)
        if ($request->hasHeader('X-Company-ID')) {
            $companyId = (int) $request->header('X-Company-ID');
            
            // Validate that user has access to this company
            if ($this->validateCompanyAccess($companyId, $request->user())) {
                return $companyId;
            }
        }
        
        // 3. From session (Web)
        if ($request->session()->has('current_company_id')) {
            return $request->session()->get('current_company_id');
        }
        
        // 4. From subdomain (if slug column exists)
        $subdomain = $this->getSubdomain($request);
        if ($subdomain) {
            // Try to find by slug first (newer approach)
            $company = \App\Models\Company::where('slug', $subdomain)->first();
            if ($company) {
                return $company->id;
            }
        }
        
        return null;
    }
    
    /**
     * Validate user has access to company
     */
    protected function validateCompanyAccess(int $companyId, ?\App\Models\User $user): bool
    {
        if (!$user) {
            return false;
        }
        
        // Super admins have access to all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Check if user belongs to company
        return $user->company_id === $companyId;
    }
    
    /**
     * Extract subdomain from request
     */
    protected function getSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        // If we have at least 3 parts, first is subdomain
        if (count($parts) >= 3) {
            return $parts[0];
        }
        
        return null;
    }
}