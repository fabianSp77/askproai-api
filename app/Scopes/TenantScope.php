<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * TenantScope for multi-tenant data isolation
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        // Get company_id from authenticated user
        $companyId = $this->getCurrentCompanyId();
        
        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
    
    /**
     * Get current company ID from authenticated user
     */
    protected function getCurrentCompanyId(): ?int
    {
        // Prevent circular dependency during authentication
        // If we're currently authenticating, don't apply scope
        if (app()->runningInConsole() || !app()->bound('auth')) {
            return null;
        }
        
        // Check app container (for background jobs)
        if (app()->bound('current_company_id')) {
            return (int) app('current_company_id');
        }
        
        // Use hasUser() to check if user is loaded without triggering user loading
        try {
            // Check portal guard first (for business portal)
            if (Auth::guard('portal')->hasUser()) {
                $user = Auth::guard('portal')->user();
                if ($user && isset($user->company_id)) {
                    return (int) $user->company_id;
                }
            }
            
            // Get from authenticated user (default web guard)
            if (Auth::hasUser()) {
                $user = Auth::user();
                if ($user && isset($user->company_id) && $user->company_id) {
                    return (int) $user->company_id;
                }
            }
            
            // For API requests with sanctum
            if (Auth::guard('sanctum')->hasUser()) {
                $user = Auth::guard('sanctum')->user();
                if ($user && isset($user->company_id)) {
                    return (int) $user->company_id;
                }
            }
        } catch (\Exception $e) {
            // If any exception occurs during auth check, return null
            // This prevents infinite loops during authentication
            return null;
        }
        
        return null;
    }
}