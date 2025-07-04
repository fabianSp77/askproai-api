<?php

namespace App\Listeners;

use App\Models\Call;
use App\Events\CallCreated;
use App\Events\CallUpdated;
use App\Services\CallNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CallEventListener
{
    /**
     * Handle call created event
     */
    public function onCallCreated(CallCreated $event): void
    {
        $call = $event->call;
        
        try {
            // Send notification for new call
            CallNotificationService::notifyNewCall($call);
            
            // Invalidate call statistics cache
            $this->invalidateCallCache($call->company_id);
            
            Log::info('New call notification sent', [
                'call_id' => $call->id,
                'company_id' => $call->company_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process new call event', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Handle call updated event
     */
    public function onCallUpdated(CallUpdated $event): void
    {
        $call = $event->call;
        
        try {
            // Check if call was converted to appointment
            if ($call->appointment_id && $call->wasChanged('appointment_id')) {
                CallNotificationService::notifyCallConverted($call);
            }
            
            // Check if call failed
            if ($call->call_status === 'failed' && $call->wasChanged('call_status')) {
                CallNotificationService::notifyFailedCall($call);
            }
            
            // Invalidate cache
            $this->invalidateCallCache($call->company_id);
            
        } catch (\Exception $e) {
            Log::error('Failed to process call update event', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Invalidate call-related caches
     */
    private function invalidateCallCache(int $companyId): void
    {
        $cacheKeys = [
            "calls.stats.company.{$companyId}",
            "calls.recent.company.{$companyId}",
            "calls.today.company.{$companyId}",
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}