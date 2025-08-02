<?php

namespace App\Jobs;

use App\Services\TenantContextService;
use App\Exceptions\TenantContextException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Tenant-Aware Job Base Class
 * 
 * This base class ensures proper tenant context propagation in background jobs,
 * preventing cross-tenant data contamination while maintaining performance.
 * 
 * Key Features:
 * - Automatic tenant context serialization and restoration
 * - Tenant validation before job execution
 * - Comprehensive audit logging
 * - Error handling with tenant context
 * - Performance monitoring
 */
abstract class TenantAwareJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected ?int $tenantCompanyId = null;
    protected ?string $tenantContextSource = null;
    protected array $tenantMetadata = [];
    protected bool $requiresTenantContext = true;
    protected bool $allowCrossTenantAccess = false;
    
    /**
     * Create a new job instance with tenant context
     */
    public function __construct()
    {
        // Capture tenant context during job creation
        $this->captureTenantContext();
    }
    
    /**
     * Execute the job with tenant context
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            // Restore tenant context
            $this->restoreTenantContext();
            
            // Validate tenant context
            $this->validateTenantContext();
            
            // Execute the actual job logic
            $this->execute();
            
            // Log successful execution
            $this->auditJobExecution('job_completed', [
                'execution_time' => microtime(true) - $startTime
            ]);
            
        } catch (TenantContextException $e) {
            $this->auditJobExecution('tenant_context_error', [
                'error' => $e->getMessage(),
                'execution_time' => microtime(true) - $startTime
            ]);
            
            // Re-throw for proper error handling
            throw $e;
            
        } catch (\Exception $e) {
            $this->auditJobExecution('job_error', [
                'error' => $e->getMessage(),
                'execution_time' => microtime(true) - $startTime
            ]);
            
            throw $e;
            
        } finally {
            // Always clean up tenant context
            $this->cleanupTenantContext();
        }
    }
    
    /**
     * The actual job logic - to be implemented by subclasses
     */
    abstract protected function execute(): void;
    
    /**
     * Capture tenant context during job creation
     */
    protected function captureTenantContext(): void
    {
        $tenantContext = app(TenantContextService::class);
        $this->tenantCompanyId = $tenantContext->getCurrentCompanyId();
        
        if ($this->tenantCompanyId) {
            $this->tenantContextSource = 'job_creation';
            $this->tenantMetadata = [
                'created_at' => now()->toISOString(),
                'created_by_user_id' => auth()->id(),
                'created_in_console' => app()->runningInConsole(),
                'job_class' => static::class
            ];
            
            $this->auditJobExecution('tenant_context_captured', [
                'company_id' => $this->tenantCompanyId,
                'metadata' => $this->tenantMetadata
            ]);
        } elseif ($this->requiresTenantContext) {
            $this->auditJobExecution('missing_tenant_context_on_creation', [
                'job_class' => static::class,
                'requires_tenant' => $this->requiresTenantContext,
                'user_id' => auth()->id(),
                'in_console' => app()->runningInConsole()
            ]);
        }
    }
    
    /**
     * Restore tenant context during job execution
     */
    protected function restoreTenantContext(): void
    {
        if (!$this->tenantCompanyId) {
            return;
        }
        
        $tenantContext = app(TenantContextService::class);
        
        try {
            $tenantContext->setTrustedJobContext(
                $this->tenantCompanyId,
                static::class
            );
            
            $this->auditJobExecution('tenant_context_restored', [
                'company_id' => $this->tenantCompanyId,
                'job_class' => static::class
            ]);
            
        } catch (\Exception $e) {
            $this->auditJobExecution('tenant_context_restoration_failed', [
                'company_id' => $this->tenantCompanyId,
                'error' => $e->getMessage(),
                'job_class' => static::class
            ]);
            
            throw new TenantContextException(
                "Failed to restore tenant context for job: {$e->getMessage()}"
            );
        }
    }
    
    /**
     * Validate tenant context before job execution
     */
    protected function validateTenantContext(): void
    {
        if ($this->requiresTenantContext && !$this->tenantCompanyId) {
            throw new TenantContextException(
                'Job requires tenant context but none was captured'
            );
        }
        
        // Validate that the tenant context is still valid
        if ($this->tenantCompanyId) {
            $tenantContext = app(TenantContextService::class);
            $currentCompanyId = $tenantContext->getCurrentCompanyId();
            
            if ($currentCompanyId !== $this->tenantCompanyId) {
                $this->auditJobExecution('tenant_context_mismatch', [
                    'expected_company_id' => $this->tenantCompanyId,
                    'actual_company_id' => $currentCompanyId,
                    'job_class' => static::class
                ]);
                
                throw new TenantContextException(
                    'Tenant context mismatch during job execution'
                );
            }
        }
    }
    
    /**
     * Clean up tenant context after job execution
     */
    protected function cleanupTenantContext(): void
    {
        if ($this->tenantCompanyId) {
            $tenantContext = app(TenantContextService::class);
            $tenantContext->clearContext();
            
            $this->auditJobExecution('tenant_context_cleaned', [
                'company_id' => $this->tenantCompanyId
            ]);
        }
    }
    
    /**
     * Set job to not require tenant context
     */
    protected function withoutTenantContext(): self
    {
        $this->requiresTenantContext = false;
        return $this;
    }
    
    /**
     * Allow cross-tenant access for this job
     */
    protected function allowCrossTenantAccess(): self
    {
        $this->allowCrossTenantAccess = true;
        return $this;
    }
    
    /**
     * Execute a cross-tenant operation within the job
     */
    protected function executeCrossTenantOperation(int $targetCompanyId, callable $callback, string $reason): mixed
    {
        if (!$this->allowCrossTenantAccess) {
            throw new TenantContextException(
                'Cross-tenant access not allowed for this job type'
            );
        }
        
        $tenantContext = app(TenantContextService::class);
        
        return $tenantContext->executeCrossTenantOperation(
            $targetCompanyId,
            $callback,
            $reason,
            [
                'job_class' => static::class,
                'original_company_id' => $this->tenantCompanyId
            ]
        );
    }
    
    /**
     * Get tenant-scoped models for the job
     */
    protected function getTenantModel(string $modelClass): \Illuminate\Database\Eloquent\Builder
    {
        if (!$this->tenantCompanyId) {
            throw new TenantContextException(
                'Cannot get tenant-scoped model without tenant context'
            );
        }
        
        $model = new $modelClass;
        
        // Check if model has company_id column
        if (!$model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'company_id')) {
            return $model->newQuery();
        }
        
        return $model->newQuery()->where('company_id', $this->tenantCompanyId);
    }
    
    /**
     * Get current tenant company ID
     */
    protected function getTenantCompanyId(): ?int
    {
        return $this->tenantCompanyId;
    }
    
    /**
     * Check if job has tenant context
     */
    protected function hasTenantContext(): bool
    {
        return $this->tenantCompanyId !== null;
    }
    
    /**
     * Get tenant metadata
     */
    protected function getTenantMetadata(): array
    {
        return $this->tenantMetadata;
    }
    
    /**
     * Handle job failure with tenant context
     */
    public function failed(\Throwable $exception): void
    {
        $this->auditJobExecution('job_failed', [
            'error' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'company_id' => $this->tenantCompanyId
        ]);
        
        // Clean up tenant context on failure
        $this->cleanupTenantContext();
        
        // Subclasses can override this method for custom failure handling
    }
    
    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        $tags = ['tenant-aware'];
        
        if ($this->tenantCompanyId) {
            $tags[] = "company:{$this->tenantCompanyId}";
        }
        
        $tags[] = 'job:' . class_basename(static::class);
        
        return $tags;
    }
    
    /**
     * Audit job execution events
     */
    protected function auditJobExecution(string $event, array $data = []): void
    {
        Log::info("TenantAwareJob: {$event}", array_merge($data, [
            'timestamp' => now()->toISOString(),
            'job_class' => static::class,
            'job_id' => $this->job?->getJobId(),
            'queue' => $this->queue,
            'tenant_company_id' => $this->tenantCompanyId,
            'tenant_context_source' => $this->tenantContextSource
        ]));
        
        // Log security-relevant events to security channel
        $securityEvents = [
            'tenant_context_mismatch',
            'tenant_context_restoration_failed',
            'missing_tenant_context_on_creation',
            'cross_tenant_operation_denied'
        ];
        
        if (in_array($event, $securityEvents)) {
            Log::channel('security')->warning("Job Security Event: {$event}", [
                'job_class' => static::class,
                'tenant_company_id' => $this->tenantCompanyId,
                'data' => $data
            ]);
        }
    }
}