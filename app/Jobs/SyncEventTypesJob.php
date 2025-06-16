<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CalcomSyncService;
use Illuminate\Support\Facades\Log;

class SyncEventTypesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $timeout = 300; // 5 Minuten
    
    protected $companyId;
    
    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }
    
    public function handle(CalcomSyncService $syncService)
    {
        Log::info('Starting event type sync job', ['company_id' => $this->companyId]);
        
        try {
            $result = $syncService->syncEventTypesForCompany($this->companyId);
            
            Log::info('Event type sync job completed', [
                'company_id' => $this->companyId,
                'synced_count' => $result['synced_count']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Event type sync job failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
            
            throw $e; // Re-throw für Retry-Mechanismus
        }
    }
    
    public function failed(\Throwable $exception)
    {
        Log::error('Event type sync job permanently failed', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage()
        ]);
    }
}