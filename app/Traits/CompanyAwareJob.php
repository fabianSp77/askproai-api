<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait for jobs that need company context
 * 
 * This trait ensures that the company context is properly set
 * when jobs are executed in the background
 */
trait CompanyAwareJob
{
    /**
     * The company ID for this job
     */
    public ?int $companyId = null;
    
    /**
     * Set the company ID for this job
     */
    public function setCompanyId(?int $companyId): self
    {
        $this->companyId = $companyId;
        return $this;
    }
    
    /**
     * Apply company context before job execution
     */
    protected function applyCompanyContext(): void
    {
        if ($this->companyId) {
            // Set the company context for tenant scoping
            app()->instance('current_company_id', $this->companyId);
            
            Log::debug('Applied company context in job', [
                'job' => static::class,
                'company_id' => $this->companyId,
            ]);
        } else {
            Log::warning('No company context available for job', [
                'job' => static::class,
            ]);
        }
    }
    
    /**
     * Clear company context after job execution
     */
    protected function clearCompanyContext(): void
    {
        if (app()->bound('current_company_id')) {
            app()->forgetInstance('current_company_id');
            
            Log::debug('Cleared company context after job', [
                'job' => static::class,
                'company_id' => $this->companyId,
            ]);
        }
    }
}