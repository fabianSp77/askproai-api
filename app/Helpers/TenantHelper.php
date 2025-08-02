<?php

namespace App\Helpers;

use App\Services\TenantContextService;
use App\Exceptions\TenantContextException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Tenant Helper Functions
 * 
 * Provides safe, audited helper methods for legitimate cross-tenant operations
 * and common tenant-related tasks throughout the application.
 * 
 * Key Features:
 * - Safe cross-tenant operations with auditing
 * - Tenant context validation helpers
 * - Performance-optimized tenant queries
 * - Developer-friendly API
 * - Comprehensive security logging
 */
class TenantHelper
{
    protected static TenantContextService $tenantContext;
    
    /**
     * Initialize the helper with tenant context service
     */
    public static function initialize(): void
    {
        if (!isset(self::$tenantContext)) {
            self::$tenantContext = app(TenantContextService::class);
        }
    }
    
    /**
     * Get current tenant company ID
     */
    public static function getCurrentCompanyId(): ?int
    {
        self::initialize();
        return self::$tenantContext->getCurrentCompanyId();
    }
    
    /**
     * Check if current user belongs to specific company
     */
    public static function belongsToCompany(int $companyId): bool
    {
        self::initialize();
        return self::$tenantContext->belongsToCompany($companyId);
    }
    
    /**
     * Ensure current user belongs to specific company
     * 
     * @throws TenantContextException If user doesn't belong to company
     */
    public static function ensureBelongsToCompany(int $companyId): void
    {
        self::initialize();
        self::$tenantContext->ensureBelongsToCompany($companyId);
    }
    
    /**
     * Safely execute a cross-tenant operation with auditing
     * 
     * @param int $targetCompanyId Target company ID
     * @param callable $callback Operation to execute
     * @param string $reason Business reason for cross-tenant access
     * @param array $metadata Additional metadata for auditing
     * @return mixed Result of callback
     * @throws TenantContextException If operation is not allowed
     */
    public static function executeCrossTenantOperation(
        int $targetCompanyId,
        callable $callback,
        string $reason,
        array $metadata = []
    ): mixed {
        self::initialize();
        
        return self::$tenantContext->executeCrossTenantOperation(
            $targetCompanyId,
            $callback,
            $reason,
            array_merge($metadata, [
                'helper_method' => 'executeCrossTenantOperation',
                'caller_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ])
        );
    }
    
    /**
     * Get a tenant-scoped query builder for a model
     * 
     * @param string $modelClass Model class name
     * @param int|null $companyId Company ID (null for current tenant)
     * @return Builder Tenant-scoped query builder
     */
    public static function scopedQuery(string $modelClass, ?int $companyId = null): Builder
    {
        self::initialize();
        
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model class {$modelClass} does not exist");
        }
        
        $model = new $modelClass;
        $query = $model->newQuery();
        
        // Determine company ID to use
        $targetCompanyId = $companyId ?? self::getCurrentCompanyId();
        
        if (!$targetCompanyId) {
            // No company context - return empty results for security
            return $query->whereRaw('0 = 1');
        }
        
        // Check if model has company_id column
        if (!$model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'company_id')) {
            // Model doesn't support tenant scoping
            self::auditHelper('non_tenant_model_access', [
                'model_class' => $modelClass,
                'requested_company_id' => $targetCompanyId
            ]);
            
            return $query;
        }
        
        // If accessing different company, validate permission
        if ($companyId && $companyId !== self::getCurrentCompanyId()) {
            self::auditHelper('cross_tenant_query_request', [
                'model_class' => $modelClass,
                'target_company_id' => $companyId,
                'current_company_id' => self::getCurrentCompanyId()
            ]);
            
            // Only allow for super admins or system operations
            if (!self::canAccessOtherTenant()) {
                throw new TenantContextException(
                    "Cross-tenant access denied for model {$modelClass}"
                );
            }
        }
        
        return $query->where('company_id', $targetCompanyId);
    }
    
    /**
     * Get a model instance with tenant validation
     * 
     * @param string $modelClass Model class name
     * @param int $id Model ID
     * @param int|null $companyId Expected company ID (null for current tenant)
     * @return Model|null Model instance or null if not found
     * @throws TenantContextException If model belongs to different tenant
     */
    public static function findTenantModel(string $modelClass, int $id, ?int $companyId = null): ?Model
    {
        $query = self::scopedQuery($modelClass, $companyId);
        $model = $query->find($id);
        
        if ($model) {
            self::auditHelper('tenant_model_found', [
                'model_class' => $modelClass,
                'model_id' => $id,
                'company_id' => $companyId ?? self::getCurrentCompanyId()
            ]);
        } else {
            self::auditHelper('tenant_model_not_found', [
                'model_class' => $modelClass,
                'model_id' => $id,
                'company_id' => $companyId ?? self::getCurrentCompanyId()
            ]);
        }
        
        return $model;
    }
    
    /**
     * Find tenant model or fail with appropriate error
     * 
     * @param string $modelClass Model class name
     * @param int $id Model ID
     * @param int|null $companyId Expected company ID (null for current tenant)
     * @return Model Model instance
     * @throws TenantContextException If model not found or access denied
     */
    public static function findTenantModelOrFail(string $modelClass, int $id, ?int $companyId = null): Model
    {
        $model = self::findTenantModel($modelClass, $id, $companyId);
        
        if (!$model) {
            self::auditHelper('tenant_model_access_denied', [
                'model_class' => $modelClass,
                'model_id' => $id,
                'company_id' => $companyId ?? self::getCurrentCompanyId()
            ]);
            
            throw new TenantContextException(
                "Resource not found or access denied for {$modelClass}#{$id}"
            );
        }
        
        return $model;
    }
    
    /**
     * Create a new model with automatic tenant assignment
     * 
     * @param string $modelClass Model class name
     * @param array $attributes Model attributes
     * @param int|null $companyId Company ID (null for current tenant)
     * @return Model Created model instance
     * @throws TenantContextException If creation fails or not allowed
     */
    public static function createTenantModel(string $modelClass, array $attributes, ?int $companyId = null): Model
    {
        $targetCompanyId = $companyId ?? self::getCurrentCompanyId();
        
        if (!$targetCompanyId) {
            throw new TenantContextException(
                'Cannot create model without tenant context'
            );
        }
        
        // Validate cross-tenant creation
        if ($companyId && $companyId !== self::getCurrentCompanyId()) {
            if (!self::canAccessOtherTenant()) {
                throw new TenantContextException(
                    "Cross-tenant creation denied for model {$modelClass}"
                );
            }
        }
        
        $model = new $modelClass;
        
        // Auto-assign company_id if model supports it
        if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'company_id')) {
            $attributes['company_id'] = $targetCompanyId;
        }
        
        $createdModel = $model->create($attributes);
        
        self::auditHelper('tenant_model_created', [
            'model_class' => $modelClass,
            'model_id' => $createdModel->id,
            'company_id' => $targetCompanyId,
            'attributes_count' => count($attributes)
        ]);
        
        return $createdModel;
    }
    
    /**
     * Check if current user can access other tenants
     */
    protected static function canAccessOtherTenant(): bool
    {
        // Allow for console operations
        if (app()->runningInConsole()) {
            return true;
        }
        
        // Allow for super admins
        $user = auth()->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate model belongs to current tenant
     * 
     * @param Model $model Model to validate
     * @throws TenantContextException If model doesn't belong to current tenant
     */
    public static function validateTenantModel(Model $model): void
    {
        $currentCompanyId = self::getCurrentCompanyId();
        
        if (!$currentCompanyId) {
            throw new TenantContextException(
                'No tenant context available for model validation'
            );
        }
        
        // Check if model has company_id
        if (!isset($model->company_id)) {
            // Model doesn't support tenant scoping - allow access
            return;
        }
        
        if ($model->company_id !== $currentCompanyId) {
            self::auditHelper('cross_tenant_model_access_attempt', [
                'model_class' => get_class($model),
                'model_id' => $model->id,
                'model_company_id' => $model->company_id,
                'current_company_id' => $currentCompanyId
            ]);
            
            throw new TenantContextException(
                'Model does not belong to current tenant'
            );
        }
    }
    
    /**
     * Get tenant statistics for monitoring
     */
    public static function getTenantStatistics(): array
    {
        self::initialize();
        
        $currentCompanyId = self::getCurrentCompanyId();
        $auditLog = self::$tenantContext->getAuditLog();
        
        return [
            'current_company_id' => $currentCompanyId,
            'has_tenant_context' => $currentCompanyId !== null,
            'audit_entries' => count($auditLog),
            'cross_tenant_operations' => count(array_filter($auditLog, function ($entry) {
                return str_contains($entry['event'], 'cross_tenant');
            })),
            'security_events' => count(array_filter($auditLog, function ($entry) {
                return str_contains($entry['event'], 'security_violation');
            }))
        ];
    }
    
    /**
     * Clear tenant context (use with caution)
     */
    public static function clearTenantContext(): void
    {
        self::initialize();
        self::$tenantContext->clearContext();
        
        self::auditHelper('tenant_context_manually_cleared', [
            'caller_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
    }
    
    /**
     * Check if a specific model class supports tenant scoping
     */
    public static function modelSupportsTenantScoping(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }
        
        $model = new $modelClass;
        
        return $model->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($model->getTable(), 'company_id');
    }
    
    /**
     * Get all companies (super admin only)
     */
    public static function getAllCompanies(): \Illuminate\Database\Eloquent\Collection
    {
        if (!self::canAccessOtherTenant()) {
            throw new TenantContextException(
                'Access denied. Super admin privileges required.'
            );
        }
        
        self::auditHelper('all_companies_accessed', [
            'user_id' => auth()->id(),
            'caller_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
        
        return \App\Models\Company::all();
    }
    
    /**
     * Audit helper operations
     */
    protected static function auditHelper(string $event, array $data = []): void
    {
        Log::info("TenantHelper: {$event}", array_merge($data, [
            'timestamp' => now()->toISOString(),
            'helper_class' => self::class,
            'user_id' => auth()->id(),
            'current_company_id' => self::getCurrentCompanyId()
        ]));
        
        // Log security events to security channel
        $securityEvents = [
            'cross_tenant_query_request',
            'cross_tenant_model_access_attempt',
            'tenant_model_access_denied',
            'all_companies_accessed'
        ];
        
        if (in_array($event, $securityEvents)) {
            Log::channel('security')->warning("TenantHelper Security Event: {$event}", $data);
        }
    }
}