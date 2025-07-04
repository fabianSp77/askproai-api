<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = $this->getCompanyId();
        
        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        } else {
            // If no company context, prevent any data access
            // This is safer than showing all data
            if (!app()->runningInConsole()) {
                $builder->whereRaw('0 = 1');
                
                // Log this as potential security issue in web context
                Log::warning('CompanyScope applied without company context', [
                    'model' => get_class($model),
                    'user_id' => Auth::id(),
                    'url' => request()->fullUrl(),
                    'ip' => request()->ip()
                ]);
            }
        }
    }

    /**
     * Get the current company ID from authenticated context ONLY
     * 
     * SECURITY: This method is critical for tenant isolation
     * - Never accept company_id from request headers
     * - Never accept company_id from query parameters
     * - Never accept company_id from session
     * - Only trust the authenticated user's company association
     */
    protected function getCompanyId(): ?int
    {
        // 1. Check app container for trusted job context
        if (app()->bound('current_company_id') && app()->bound('company_context_source')) {
            if (app('company_context_source') === 'trusted_job') {
                return (int) app('current_company_id');
            }
        }
        
        // 2. Get from authenticated user (web guard)
        if (Auth::check()) {
            $user = Auth::user();
            
            // Direct company_id on user
            if (isset($user->company_id) && $user->company_id) {
                return (int) $user->company_id;
            }
            
            // Company relationship
            if (method_exists($user, 'company')) {
                $company = $user->company()->first();
                if ($company && $company->id) {
                    return (int) $company->id;
                }
            }
        }
        
        // 3. Check API authentication (sanctum guard)
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            if ($user && isset($user->company_id)) {
                return (int) $user->company_id;
            }
        }
        
        // 4. For system operations in console
        if (app()->runningInConsole()) {
            // Allow explicit company context for migrations/seeders
            if (app()->has('tenant.id')) {
                return (int) app('tenant.id');
            }
            
            // Console commands without company context
            return null;
        }
        
        // 5. Security check - log if headers are attempted
        if (request()->hasHeader('X-Company-Id') || request()->has('company_id')) {
            Log::critical('SECURITY: Attempted to use untrusted company_id source', [
                'headers' => request()->headers->all(),
                'query' => request()->query(),
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
                'user_id' => Auth::id(),
                'user_agent' => request()->userAgent()
            ]);
            
            // Don't use the untrusted value!
        }
        
        // NO FALLBACKS - return null if no trusted company context
        return null;
    }
    
    /**
     * Check if the scope is currently active
     */
    public function isActive(): bool
    {
        return $this->getCompanyId() !== null;
    }
    
    /**
     * Get the current company ID (for debugging/logging only)
     */
    public function getCurrentCompanyId(): ?int
    {
        return $this->getCompanyId();
    }
}