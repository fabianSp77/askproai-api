<?php

namespace App\Traits;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToCompany(): void
    {
        // Add global scope
        static::addGlobalScope(new CompanyScope);
        
        // Auto-set company_id on create
        static::creating(function (Model $model) {
            if (empty($model->company_id)) {
                $companyId = static::getCurrentCompanyId();
                
                if (!$companyId) {
                    throw new \RuntimeException(
                        'Cannot create record without company context. Please authenticate.'
                    );
                }
                
                $model->company_id = $companyId;
            }
            
            // Validate company_id matches current user's company
            $currentCompanyId = static::getCurrentCompanyId();
            if ($currentCompanyId && $model->company_id !== $currentCompanyId) {
                \Log::warning('Attempted cross-tenant data creation', [
                    'model' => get_class($model),
                    'attempted_company_id' => $model->company_id,
                    'current_company_id' => $currentCompanyId,
                    'user_id' => Auth::id(),
                    'ip' => request()->ip()
                ]);
                
                throw new \RuntimeException(
                    'Attempted to create record for different company. Access denied.'
                );
            }
        });
        
        // Prevent updating company_id
        static::updating(function (Model $model) {
            if ($model->isDirty('company_id')) {
                \Log::critical('Attempted to change company_id', [
                    'model' => get_class($model),
                    'model_id' => $model->id,
                    'old_company_id' => $model->getOriginal('company_id'),
                    'new_company_id' => $model->company_id,
                    'user_id' => Auth::id(),
                    'ip' => request()->ip()
                ]);
                
                throw new \RuntimeException(
                    'Company ID cannot be changed after creation.'
                );
            }
        });
    }
    
    /**
     * Get current company ID from authenticated user ONLY
     * 
     * SECURITY: Never accept company_id from request headers, query params, or session
     * Only trust the authenticated user's company association
     */
    protected static function getCurrentCompanyId(): ?int
    {
        // 1. Check app container for trusted context
        if (app()->bound('current_company_id') && app()->bound('company_context_source')) {
            $contextSource = app('company_context_source');
            
            // Allow web auth context from our middleware
            if ($contextSource === 'web_auth' && !app()->runningInConsole()) {
                return (int) app('current_company_id');
            }
            
            // Verify this is actually a background job and not a web request
            if ($contextSource === 'trusted_job' && 
                app()->runningInConsole() &&
                app()->bound('trusted_job_class')) {
                
                // Validate the job class is in our allowed list
                $trustedJobs = [
                    'App\Jobs\ProcessRetellCallJob',
                    'App\Jobs\ProcessRetellWebhookJob',
                    'App\Jobs\RefreshCallDataJob',
                    'App\Jobs\SyncCalcomEventTypesJob',
                    'App\Jobs\ProcessAppointmentBookingJob',
                    'App\Jobs\SendAppointmentReminderJob'
                ];
                
                $jobClass = app('trusted_job_class');
                if (in_array($jobClass, $trustedJobs)) {
                    return (int) app('current_company_id');
                } else {
                    \Log::warning('Untrusted job attempted to set company context', [
                        'job_class' => $jobClass,
                        'company_id' => app('current_company_id')
                    ]);
                }
            }
        }
        
        // 2. Get from authenticated user ONLY
        if ($user = Auth::user()) {
            // First check direct company_id on user
            if (isset($user->company_id) && $user->company_id) {
                return (int) $user->company_id;
            }
            
            // Then check company relationship
            if (method_exists($user, 'company')) {
                $company = $user->company()->first();
                if ($company && $company->id) {
                    return (int) $company->id;
                }
            }
        }
        
        // 3. For API requests with token authentication
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            if ($user && isset($user->company_id)) {
                return (int) $user->company_id;
            }
        }
        
        // NO FALLBACKS! If we can't determine company from authentication, fail safely
        return null;
    }
    
    /**
     * Set company context for background jobs
     * 
     * SECURITY: Only use this in trusted job middleware after validating the job's company ownership
     */
    public static function setTrustedCompanyContext(int $companyId, string $jobClass): void
    {
        // Only allow this to be called from console (background jobs)
        if (!app()->runningInConsole()) {
            \Log::critical('Attempted to set trusted company context from web request', [
                'company_id' => $companyId,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            throw new \RuntimeException('Cannot set company context from web request');
        }
        
        app()->instance('current_company_id', $companyId);
        app()->instance('company_context_source', 'trusted_job');
        app()->instance('trusted_job_class', $jobClass);
    }
    
    /**
     * Clear company context (for job cleanup)
     */
    public static function clearCompanyContext(): void
    {
        app()->forgetInstance('current_company_id');
        app()->forgetInstance('company_context_source');
        app()->forgetInstance('trusted_job_class');
    }
    
    /**
     * Scope to company with explicit ID
     */
    public function scopeForCompany($query, $companyId)
    {
        if (!$companyId) {
            // Return empty result for null company
            return $query->whereRaw('0 = 1');
        }
        
        return $query->withoutGlobalScope(CompanyScope::class)
            ->where($this->getTable() . '.company_id', $companyId);
    }
    
    /**
     * Scope to current company
     */
    public function scopeCurrentCompany($query)
    {
        $companyId = static::getCurrentCompanyId();
        
        if (!$companyId) {
            // Log this as it might indicate a security issue
            \Log::warning('Attempted to scope to current company without company context', [
                'model' => get_class($this),
                'user_id' => Auth::id(),
                'url' => request()->fullUrl(),
                'ip' => request()->ip()
            ]);
            
            // Return empty result if no company context
            return $query->whereRaw('0 = 1');
        }
        
        return $query->forCompany($companyId);
    }
    
    /**
     * Check if model belongs to current company
     */
    public function belongsToCurrentCompany(): bool
    {
        $currentCompanyId = static::getCurrentCompanyId();
        
        return $currentCompanyId && $this->company_id == $currentCompanyId;
    }
    
    /**
     * Ensure model belongs to current company or throw exception
     */
    public function ensureBelongsToCurrentCompany(): void
    {
        if (!$this->belongsToCurrentCompany()) {
            \Log::warning('Cross-tenant access attempt', [
                'model' => get_class($this),
                'model_id' => $this->id,
                'model_company_id' => $this->company_id,
                'current_company_id' => static::getCurrentCompanyId(),
                'user_id' => Auth::id(),
                'url' => request()->fullUrl(),
                'ip' => request()->ip()
            ]);
            
            abort(403, 'Access denied. This resource belongs to a different company.');
        }
    }
    
    /**
     * Company relationship
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}