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
        // 1. Check app container for trusted context
        if (app()->bound('current_company_id') && app()->bound('company_context_source')) {
            $contextSource = app('company_context_source');
            
            // Allow web auth context from our middleware
            $allowedWebSources = ['web_auth', 'early_middleware', 'force_company_context_middleware', 'auth_event', 'request_handled_event', 'route_matched_event', 'portal_auth', 'session_restore'];
            if (in_array($contextSource, $allowedWebSources) && !app()->runningInConsole()) {
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
                    'App\Jobs\SendAppointmentReminderJob',
                    // Console commands that need company context
                    'App\Console\Commands\MonitorRetellIntegration',
                    'App\Console\Commands\TestRetellIntegration'
                ];
                
                $jobClass = app('trusted_job_class');
                if (in_array($jobClass, $trustedJobs)) {
                    return (int) app('current_company_id');
                } else {
                    Log::warning('Untrusted job attempted to set company context', [
                        'job_class' => $jobClass,
                        'company_id' => app('current_company_id')
                    ]);
                }
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
        
        // 3. Check portal authentication (portal guard)
        if (Auth::guard('portal')->check()) {
            $user = Auth::guard('portal')->user();
            if ($user && isset($user->company_id)) {
                return (int) $user->company_id;
            }
        }
        
        // 4. Check API authentication (sanctum guard)
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            if ($user && isset($user->company_id)) {
                return (int) $user->company_id;
            }
        }
        
        // 5. For system operations in console
        if (app()->runningInConsole()) {
            // Allow explicit company context for migrations/seeders
            if (app()->has('tenant.id')) {
                return (int) app('tenant.id');
            }
            
            // Console commands without company context
            return null;
        }
        
        // 6. Security check - log if headers are attempted
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