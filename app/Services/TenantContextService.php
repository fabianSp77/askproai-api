<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Exceptions\TenantContextException;

/**
 * Central Tenant Context Service
 * 
 * This service provides a secure, centralized way to handle multi-tenant context
 * throughout the application. It ensures data isolation and prevents cross-tenant
 * data access while maintaining performance and developer experience.
 * 
 * Key Features:
 * - Secure tenant context detection from authenticated users only
 * - Background job context propagation with auditing
 * - Cross-tenant operation support for legitimate use cases
 * - Comprehensive audit logging for security monitoring
 * - Performance-optimized with caching
 */
class TenantContextService
{
    private static ?int $cachedCompanyId = null;
    private static ?string $contextSource = null;
    private static array $auditLog = [];
    
    /**
     * Get current company ID with comprehensive security checks
     * 
     * @return int|null Company ID or null if no valid context
     * @throws TenantContextException For security violations
     */
    public function getCurrentCompanyId(): ?int
    {
        // Return cached value if available (within same request)
        if (self::$cachedCompanyId !== null) {
            return self::$cachedCompanyId;
        }
        
        $companyId = $this->resolveCompanyId();
        
        // Cache the result for this request
        if ($companyId) {
            self::$cachedCompanyId = $companyId;
            $this->auditAccess('company_context_resolved', [
                'company_id' => $companyId,
                'source' => self::$contextSource
            ]);
        }
        
        return $companyId;
    }
    
    /**
     * Resolve company ID from various trusted sources
     */
    private function resolveCompanyId(): ?int
    {
        // 1. Trusted application context (background jobs, middleware)
        if ($companyId = $this->getFromTrustedContext()) {
            self::$contextSource = 'trusted_context';
            return $companyId;
        }
        
        // 2. Authenticated user context (all guards)
        if ($companyId = $this->getFromAuthenticatedUser()) {
            self::$contextSource = 'authenticated_user';
            return $companyId;
        }
        
        // 3. Console context (migrations, seeders)
        if ($companyId = $this->getFromConsoleContext()) {
            self::$contextSource = 'console_context';
            return $companyId;
        }
        
        // 4. Security check for unauthorized access attempts
        $this->checkForSecurityViolations();
        
        return null;
    }
    
    /**
     * Get company ID from trusted application context
     */
    private function getFromTrustedContext(): ?int
    {
        if (!app()->bound('current_company_id') || !app()->bound('company_context_source')) {
            return null;
        }
        
        $contextSource = app('company_context_source');
        $companyId = app('current_company_id');
        
        // Validate trusted web sources
        $trustedWebSources = [
            'web_auth', 'early_middleware', 'force_company_context_middleware',
            'auth_event', 'request_handled_event', 'route_matched_event',
            'portal_auth', 'session_restore', 'api_auth'
        ];
        
        if (in_array($contextSource, $trustedWebSources) && !app()->runningInConsole()) {
            return (int) $companyId;
        }
        
        // Validate trusted job context
        if ($contextSource === 'trusted_job' && app()->runningInConsole()) {
            return $this->validateTrustedJobContext($companyId);
        }
        
        return null;
    }
    
    /**
     * Validate trusted job context
     */
    private function validateTrustedJobContext(int $companyId): ?int
    {
        if (!app()->bound('trusted_job_class')) {
            $this->auditAccess('security_violation', [
                'type' => 'missing_job_class',
                'company_id' => $companyId
            ]);
            return null;
        }
        
        $jobClass = app('trusted_job_class');
        $trustedJobs = config('tenant.trusted_job_classes', [
            'App\Jobs\ProcessRetellCallJob',
            'App\Jobs\ProcessRetellWebhookJob',
            'App\Jobs\RefreshCallDataJob',
            'App\Jobs\SyncCalcomEventTypesJob',
            'App\Jobs\ProcessAppointmentBookingJob',
            'App\Jobs\SendAppointmentReminderJob',
            'App\Jobs\ProcessRetellCallEndedJob',
            'App\Jobs\SendCallSummaryEmailJob',
            'App\Console\Commands\MonitorRetellIntegration',
            'App\Console\Commands\TestRetellIntegration'
        ]);
        
        if (!in_array($jobClass, $trustedJobs)) {
            $this->auditAccess('security_violation', [
                'type' => 'untrusted_job_class',
                'job_class' => $jobClass,
                'company_id' => $companyId
            ]);
            return null;
        }
        
        return $companyId;
    }
    
    /**
     * Get company ID from authenticated user
     */
    private function getFromAuthenticatedUser(): ?int
    {
        // Check all authentication guards
        $guards = ['web', 'portal', 'sanctum', 'api'];
        
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                if ($user && isset($user->company_id) && $user->company_id) {
                    return (int) $user->company_id;
                }
                
                // Check company relationship
                if ($user && method_exists($user, 'company')) {
                    $company = $user->company()->first();
                    if ($company && $company->id) {
                        return (int) $company->id;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get company ID from console context
     */
    private function getFromConsoleContext(): ?int
    {
        if (!app()->runningInConsole()) {
            return null;
        }
        
        // Allow explicit tenant context for migrations/seeders
        if (app()->has('tenant.id')) {
            return (int) app('tenant.id');
        }
        
        return null;
    }
    
    /**
     * Check for security violations
     */
    private function checkForSecurityViolations(): void
    {
        if (!request()) {
            return;
        }
        
        // Check for untrusted company_id sources
        $suspiciousSources = [
            'header' => request()->header('X-Company-Id'),
            'query' => request()->query('company_id'),
            'post' => request()->input('company_id'),
            'session' => session('company_id')
        ];
        
        $violations = array_filter($suspiciousSources);
        
        if (!empty($violations)) {
            $this->auditAccess('security_violation', [
                'type' => 'untrusted_company_id_sources',
                'sources' => $violations,
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
                'user_agent' => request()->userAgent(),
                'user_id' => Auth::id()
            ]);
        }
    }
    
    /**
     * Set trusted company context for background jobs
     * 
     * @param int $companyId Company ID
     * @param string $jobClass Job class name
     * @throws TenantContextException If called from web context
     */
    public function setTrustedJobContext(int $companyId, string $jobClass): void
    {
        if (!app()->runningInConsole()) {
            $this->auditAccess('security_violation', [
                'type' => 'web_context_job_attempt',
                'company_id' => $companyId,
                'job_class' => $jobClass,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            
            throw new TenantContextException('Cannot set job context from web request');
        }
        
        // Validate company exists
        if (!Company::find($companyId)) {
            throw new TenantContextException("Company {$companyId} does not exist");
        }
        
        app()->instance('current_company_id', $companyId);
        app()->instance('company_context_source', 'trusted_job');
        app()->instance('trusted_job_class', $jobClass);
        
        // Clear cache
        self::$cachedCompanyId = null;
        
        $this->auditAccess('job_context_set', [
            'company_id' => $companyId,
            'job_class' => $jobClass
        ]);
    }
    
    /**
     * Set web authentication context
     * 
     * @param int $companyId Company ID
     * @param string $source Context source identifier
     */
    public function setWebAuthContext(int $companyId, string $source): void
    {
        if (app()->runningInConsole()) {
            return; // Don't set web context from console
        }
        
        app()->instance('current_company_id', $companyId);
        app()->instance('company_context_source', $source);
        
        // Clear cache
        self::$cachedCompanyId = null;
        
        $this->auditAccess('web_context_set', [
            'company_id' => $companyId,
            'source' => $source
        ]);
    }
    
    /**
     * Clear all tenant context
     */
    public function clearContext(): void
    {
        app()->forgetInstance('current_company_id');
        app()->forgetInstance('company_context_source');
        app()->forgetInstance('trusted_job_class');
        
        self::$cachedCompanyId = null;
        self::$contextSource = null;
        
        $this->auditAccess('context_cleared');
    }
    
    /**
     * Check if current user belongs to specific company
     * 
     * @param int $companyId Company ID to check
     * @return bool True if user belongs to company
     */
    public function belongsToCompany(int $companyId): bool
    {
        $currentCompanyId = $this->getCurrentCompanyId();
        return $currentCompanyId && $currentCompanyId === $companyId;
    }
    
    /**
     * Ensure current user belongs to specific company
     * 
     * @param int $companyId Company ID to check
     * @throws TenantContextException If user doesn't belong to company
     */
    public function ensureBelongsToCompany(int $companyId): void
    {
        if (!$this->belongsToCompany($companyId)) {
            $this->auditAccess('cross_tenant_access_attempt', [
                'target_company_id' => $companyId,
                'current_company_id' => $this->getCurrentCompanyId(),
                'user_id' => Auth::id(),
                'url' => request() ? request()->fullUrl() : null,
                'ip' => request() ? request()->ip() : null
            ]);
            
            throw new TenantContextException("Access denied. You don't belong to company {$companyId}");
        }
    }
    
    /**
     * Execute a cross-tenant operation with proper auditing
     * 
     * @param int $targetCompanyId Target company ID
     * @param callable $callback Operation to execute
     * @param string $reason Reason for cross-tenant operation
     * @param array $metadata Additional metadata
     * @return mixed Result of callback
     * @throws TenantContextException If operation is not allowed
     */
    public function executeCrossTenantOperation(int $targetCompanyId, callable $callback, string $reason, array $metadata = [])
    {
        // Only allow for super admins or system operations
        if (!$this->canExecuteCrossTenantOperation($reason)) {
            throw new TenantContextException("Cross-tenant operation not allowed: {$reason}");
        }
        
        $originalCompanyId = $this->getCurrentCompanyId();
        
        $this->auditAccess('cross_tenant_operation_start', [
            'target_company_id' => $targetCompanyId,
            'original_company_id' => $originalCompanyId,
            'reason' => $reason,
            'metadata' => $metadata,
            'user_id' => Auth::id()
        ]);
        
        try {
            // Temporarily switch context
            $this->setWebAuthContext($targetCompanyId, 'cross_tenant_operation');
            
            $result = $callback();
            
            $this->auditAccess('cross_tenant_operation_success', [
                'target_company_id' => $targetCompanyId,
                'reason' => $reason
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->auditAccess('cross_tenant_operation_error', [
                'target_company_id' => $targetCompanyId,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            // Restore original context
            if ($originalCompanyId) {
                $this->setWebAuthContext($originalCompanyId, 'context_restored');
            } else {
                $this->clearContext();
            }
        }
    }
    
    /**
     * Check if cross-tenant operation is allowed
     */
    private function canExecuteCrossTenantOperation(string $reason): bool
    {
        // Allow for system operations
        if (app()->runningInConsole()) {
            return true;
        }
        
        // Allow for super admins
        $user = Auth::user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }
        
        // Allow specific reasons
        $allowedReasons = [
            'system_maintenance',
            'data_migration',
            'super_admin_operation',
            'webhook_processing',
            'cross_company_integration'
        ];
        
        return in_array($reason, $allowedReasons);
    }
    
    /**
     * Get audit log for current request
     */
    public function getAuditLog(): array
    {
        return self::$auditLog;
    }
    
    /**
     * Audit access and log to security log
     */
    private function auditAccess(string $event, array $data = []): void
    {
        $auditEntry = [
            'timestamp' => now()->toISOString(),
            'event' => $event,
            'data' => $data,
            'request_id' => request() ? request()->header('X-Request-ID') : uniqid(),
            'user_id' => Auth::id(),
            'ip' => request() ? request()->ip() : null,
            'user_agent' => request() ? request()->userAgent() : null
        ];
        
        self::$auditLog[] = $auditEntry;
        
        // Log to security channel for monitoring
        Log::channel('security')->info("TenantContext: {$event}", $auditEntry);
        
        // Log security violations at higher level
        if (str_contains($event, 'security_violation')) {
            Log::channel('security')->warning("SECURITY VIOLATION: {$event}", $auditEntry);
        }
    }
}