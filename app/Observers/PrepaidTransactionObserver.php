<?php

namespace App\Observers;

use App\Models\PrepaidTransaction;
use App\Services\ResellerMetricsService;

class PrepaidTransactionObserver
{
    protected ResellerMetricsService $metricsService;
    
    public function __construct(ResellerMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }
    
    /**
     * Handle the PrepaidTransaction "created" event.
     */
    public function created(PrepaidTransaction $transaction): void
    {
        $this->invalidateResellerCache($transaction);
    }
    
    /**
     * Handle the PrepaidTransaction "updated" event.
     */
    public function updated(PrepaidTransaction $transaction): void
    {
        $this->invalidateResellerCache($transaction);
    }
    
    /**
     * Handle the PrepaidTransaction "deleted" event.
     */
    public function deleted(PrepaidTransaction $transaction): void
    {
        $this->invalidateResellerCache($transaction);
    }
    
    /**
     * Invalidate reseller cache if transaction belongs to a reseller client
     */
    private function invalidateResellerCache(PrepaidTransaction $transaction): void
    {
        if ($transaction->company && $transaction->company->parent_company_id) {
            // This is a reseller client, invalidate the reseller's cache
            $this->metricsService->invalidateResellerCache($transaction->company->parent_company_id);
        }
    }
}