<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Optimized TenantScope that prevents memory exhaustion during authentication
 */
class OptimizedTenantScope implements Scope
{
    /**
     * Skip certain operations to prevent circular dependencies
     */
    protected static array $skipOperations = [
        'Illuminate\Auth\EloquentUserProvider@retrieveByCredentials',
        'Illuminate\Auth\EloquentUserProvider@retrieveById',
        'Illuminate\Auth\SessionGuard@user',
        'Illuminate\Database\Eloquent\Builder@first',
        'Illuminate\Database\Eloquent\Builder@get',
    ];
    
    /**
     * Skip when querying by email (typical authentication pattern)
     */
    protected static array $skipPatterns = [
        'email' => true,
        'username' => true,
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        // Skip during authentication to prevent circular dependency
        if ($this->shouldSkipScope($builder)) {
            return;
        }

        // Get company_id from authenticated user
        $companyId = $this->getCurrentCompanyId();
        
        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
    
    /**
     * Check if we should skip the scope to prevent circular dependencies
     */
    protected function shouldSkipScope(Builder $builder = null): bool
    {
        // Skip during console commands unless explicitly set
        if (app()->runningInConsole()) {
            // But allow if current_company_id is explicitly bound
            if (!app()->bound('current_company_id')) {
                return true;
            }
        }

        // Check if querying by authentication fields
        if ($builder !== null) {
            $query = $builder->getQuery();
            if ($query->wheres) {
                foreach ($query->wheres as $where) {
                    if (isset($where['column']) && isset(self::$skipPatterns[$where['column']])) {
                        return true;
                    }
                }
            }
        }

        // Check the backtrace for authentication operations
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        
        foreach ($backtrace as $frame) {
            if (!isset($frame['class']) || !isset($frame['function'])) {
                continue;
            }
            
            $operation = $frame['class'] . '@' . $frame['function'];
            
            if (in_array($operation, self::$skipOperations)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get current company ID from authenticated user
     */
    protected function getCurrentCompanyId(): ?int
    {
        // Check app container first (for background jobs)
        if (app()->bound('current_company_id')) {
            return (int) app('current_company_id');
        }
        
        // For web requests, check if we already have a cached company_id
        static $cachedCompanyId = null;
        
        if ($cachedCompanyId !== null) {
            return $cachedCompanyId;
        }
        
        try {
            // Check portal guard first (for business portal)
            if (Auth::guard('portal')->hasUser()) {
                $user = Auth::guard('portal')->user();
                if ($user && isset($user->company_id)) {
                    $cachedCompanyId = (int) $user->company_id;
                    return $cachedCompanyId;
                }
            }
            
            // Check default guard
            if (Auth::hasUser()) {
                $user = Auth::user();
                if ($user && isset($user->company_id) && $user->company_id) {
                    $cachedCompanyId = (int) $user->company_id;
                    return $cachedCompanyId;
                }
            }
            
            // For API requests with sanctum
            if (Auth::guard('sanctum')->hasUser()) {
                $user = Auth::guard('sanctum')->user();
                if ($user && isset($user->company_id)) {
                    $cachedCompanyId = (int) $user->company_id;
                    return $cachedCompanyId;
                }
            }
        } catch (\Exception $e) {
            // If any exception occurs, return null to prevent blocking
            return null;
        }
        
        return null;
    }
}