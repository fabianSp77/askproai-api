<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use App\Traits\BelongsToCompany;

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
            // Use the secure method to set company context
            BelongsToCompany::setTrustedCompanyContext($this->companyId, static::class);
            
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
        // Use the secure method to clear company context
        BelongsToCompany::clearCompanyContext();
        
        Log::debug('Cleared company context after job', [
            'job' => static::class,
            'company_id' => $this->companyId,
        ]);
    }
}