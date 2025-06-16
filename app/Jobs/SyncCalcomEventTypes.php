<?php

namespace App\Jobs;

use App\Services\CalcomSyncService;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCalcomEventTypes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    
    public function __construct(
        private int $companyId,
        private ?int $serviceId = null,
        private ?string $staffId = null
    ) {}
    
    public function handle(CalcomSyncService $syncService): void
    {
        try {
            Log::info('Starting Cal.com sync job', [
                'company_id' => $this->companyId,
                'service_id' => $this->serviceId,
                'staff_id' => $this->staffId
            ]);
            
            $syncService->syncCompanyEventTypes($this->companyId);
            
            Log::info('Cal.com sync job completed successfully');
            
        } catch (\Exception $e) {
            Log::error('Cal.com sync job failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error('Cal.com EventType sync failed after all retries', [
            'company_id' => $this->companyId,
            'service_id' => $this->serviceId,
            'staff_id' => $this->staffId,
            'error' => $exception->getMessage()
        ]);
    }
}
