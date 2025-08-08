<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecureCompanyScopeMiddleware
{
    /**
     * Handle an incoming request with enhanced security
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $requestedCompanyId = $request->input('switch_company') ?? session('current_company');
        
        // Security: Validate CSRF token for company switching
        if ($request->has('switch_company')) {
            if (!$this->validateCompanySwitchRequest($request)) {
                Log::warning('Invalid company switch attempt', [
                    'user_id' => $user->id,
                    'requested_company' => $request->input('switch_company'),
                    'ip' => $request->ip()
                ]);
                
                abort(403, 'Unauthorized company switch attempt');
            }
        }
        
        // Get validated company ID
        $currentCompanyId = $this->getValidatedCompanyId($user, $requestedCompanyId);
        
        // Double-check authorization
        if (!$this->userCanAccessCompany($user, $currentCompanyId)) {
            Log::error('Unauthorized company access attempt', [
                'user_id' => $user->id,
                'company_id' => $currentCompanyId,
                'ip' => $request->ip()
            ]);
            
            // Reset to user's own company
            $currentCompanyId = $user->company_id;
        }
        
        // Set secure session with fingerprint
        $this->setSecureCompanySession($request, $user, $currentCompanyId);
        
        // Set the tenant scope for the current request
        // Use app container instead of static property
        app()->instance('current_company_id', $currentCompanyId);
        
        // Share current company with all views
        $currentCompany = $this->getCachedCompany($currentCompanyId);
        view()->share('currentCompany', $currentCompany);
        
        // Add security headers
        $response = $next($request);
        
        return $this->addSecurityHeaders($response);
    }
    
    /**
     * Validate company switch request
     */
    private function validateCompanySwitchRequest(Request $request): bool
    {
        // Check for valid CSRF token
        if (!$request->session()->token() || 
            $request->input('_token') !== $request->session()->token()) {
            return false;
        }
        
        // Check request method (should be POST for switching)
        if (!$request->isMethod('post')) {
            return false;
        }
        
        // Validate company ID format
        $companyId = $request->input('switch_company');
        if (!is_numeric($companyId) || $companyId < 1) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get validated company ID with security checks
     */
    private function getValidatedCompanyId(User $user, $requestedCompanyId): int
    {
        // If no requested company, use user's default
        if (!$requestedCompanyId) {
            return $user->company_id;
        }
        
        // Validate requested company exists
        if (!Company::where('id', $requestedCompanyId)->exists()) {
            return $user->company_id;
        }
        
        // Check authorization
        if (!$this->userCanAccessCompany($user, $requestedCompanyId)) {
            return $user->company_id;
        }
        
        return (int) $requestedCompanyId;
    }
    
    /**
     * Check if user can access the given company with caching
     */
    private function userCanAccessCompany(User $user, int $companyId): bool
    {
        // Cache key includes user permissions for cache invalidation
        $cacheKey = "user_company_access:{$user->id}:{$companyId}:" . $user->updated_at->timestamp;
        
        return Cache::remember($cacheKey, 300, function () use ($user, $companyId) {
            // Super admin can access all
            if ($user->hasRole('super_admin')) {
                return true;
            }
            
            // User can access their own company
            if ($user->company_id == $companyId) {
                return true;
            }
            
            // Reseller users can access child companies
            if ($user->hasRole(['reseller_owner', 'reseller_admin', 'reseller_support'])) {
                if (!$user->company || !$user->company->isReseller()) {
                    return false;
                }
                
                // Check if it's a child company
                return $user->company->childCompanies()
                    ->where('id', $companyId)
                    ->exists();
            }
            
            return false;
        });
    }
    
    /**
     * Set secure company session with fingerprinting
     */
    private function setSecureCompanySession(Request $request, User $user, int $companyId): void
    {
        // Create session fingerprint to prevent hijacking
        $fingerprint = $this->generateSessionFingerprint($request, $user);
        
        session([
            'current_company' => $companyId,
            'company_fingerprint' => $fingerprint,
            'company_switched_at' => now()->timestamp
        ]);
        
        // Log company switches for audit trail
        if (session('previous_company') !== $companyId) {
            Log::info('Company context switched', [
                'user_id' => $user->id,
                'from_company' => session('previous_company'),
                'to_company' => $companyId,
                'ip' => $request->ip()
            ]);
            
            session(['previous_company' => $companyId]);
        }
    }
    
    /**
     * Generate session fingerprint for security
     */
    private function generateSessionFingerprint(Request $request, User $user): string
    {
        $data = [
            $user->id,
            $request->ip(),
            $request->userAgent(),
            $user->updated_at->timestamp
        ];
        
        return hash('sha256', implode('|', $data));
    }
    
    /**
     * Get cached company instance
     */
    private function getCachedCompany(int $companyId): ?Company
    {
        return Cache::remember("company_instance:{$companyId}", 600, function () use ($companyId) {
            return Company::with(['parentCompany', 'childCompanies'])
                ->find($companyId);
        });
    }
    
    /**
     * Add security headers to response
     */
    private function addSecurityHeaders($response)
    {
        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-XSS-Protection', '1; mode=block');
        }
        
        return $response;
    }
}