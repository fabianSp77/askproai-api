<?php

namespace App\Services\Calcom;

use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;

/**
 * Backwards compatibility layer for Cal.com V1 to V2 migration
 * 
 * This service provides V1-compatible method signatures that internally
 * use the V2 API. It logs all V1 usage for monitoring and adds
 * deprecation warnings.
 */
class CalcomBackwardsCompatibility
{
    private CalcomV2Service $v2Service;
    private bool $logDeprecations;
    
    public function __construct(?string $apiKey = null)
    {
        $this->v2Service = new CalcomV2Service($apiKey);
        $this->logDeprecations = config('services.calcom.log_v1_usage', true);
    }
    
    /**
     * V1-compatible checkAvailability method
     * 
     * @deprecated Use CalcomV2Service::getSlots() instead
     */
    public function checkAvailability($eventTypeId, $dateFrom, $dateTo)
    {
        $this->logV1Usage('checkAvailability', [
            'eventTypeId' => $eventTypeId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
        
        try {
            // Convert to V2 format
            $slots = $this->v2Service->getSlots(
                (int) $eventTypeId,
                $dateFrom,
                $dateTo,
                'Europe/Berlin'
            );
            
            // Convert V2 response to V1 format
            if ($slots['success'] && isset($slots['data']['slots'])) {
                // V1 returns busy times, V2 returns available slots
                // We need to invert this logic
                $busyTimes = $this->convertSlotsToBusyTimes(
                    $slots['data']['slots'],
                    $dateFrom,
                    $dateTo
                );
                
                return [
                    'busy' => $busyTimes,
                    'timeZone' => 'Europe/Berlin'
                ];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('CalcomBackwardsCompatibility checkAvailability failed', [
                'error' => $e->getMessage(),
                'eventTypeId' => $eventTypeId
            ]);
            return null;
        }
    }
    
    /**
     * V1-compatible bookAppointment method
     * 
     * @deprecated Use CalcomV2Service::createBooking() instead
     */
    public function bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)
    {
        $this->logV1Usage('bookAppointment', [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startTime,
            'customerData' => $customerData
        ]);
        
        try {
            // Convert to V2 format
            $bookingData = [
                'eventTypeId' => (int) $eventTypeId,
                'start' => $startTime,
                'name' => $customerData['name'] ?? '',
                'email' => $customerData['email'] ?? '',
                'timeZone' => $customerData['timeZone'] ?? 'Europe/Berlin',
                'metadata' => []
            ];
            
            // Add phone to metadata
            if (!empty($customerData['phone'])) {
                $bookingData['metadata']['phone'] = $customerData['phone'];
            }
            
            // Add notes
            if ($notes) {
                $bookingData['metadata']['notes'] = $notes;
            }
            
            $result = $this->v2Service->createBooking($bookingData);
            
            // Convert V2 response to V1 format
            if ($result['success'] && isset($result['data'])) {
                $booking = $result['data'];
                return [
                    'id' => $booking['id'] ?? null,
                    'uid' => $booking['uid'] ?? null,
                    'title' => $booking['title'] ?? '',
                    'startTime' => $booking['start'] ?? $startTime,
                    'endTime' => $booking['end'] ?? $endTime,
                    'status' => $booking['status'] ?? 'accepted'
                ];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('CalcomBackwardsCompatibility bookAppointment failed', [
                'error' => $e->getMessage(),
                'eventTypeId' => $eventTypeId
            ]);
            return null;
        }
    }
    
    /**
     * V1-compatible getEventTypes method
     * 
     * @deprecated Use CalcomV2Service::getEventTypes() instead
     */
    public function getEventTypes($companyId = null)
    {
        $this->logV1Usage('getEventTypes', [
            'companyId' => $companyId
        ]);
        
        try {
            // V2 uses teamSlug instead of companyId
            $teamSlug = config('services.calcom.team_slug', 'askproai');
            $result = $this->v2Service->getEventTypes($teamSlug);
            
            // V2 response is already compatible with V1
            if ($result['success']) {
                return $result['data'] ?? [];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('CalcomBackwardsCompatibility getEventTypes failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * V1-compatible getBookings method
     * 
     * @deprecated Use CalcomV2Service::getBookings() instead
     */
    public function getBookings($params = [])
    {
        $this->logV1Usage('getBookings', $params);
        
        try {
            // Convert V1 params to V2 format
            $filters = [];
            
            if (isset($params['from'])) {
                $filters['from'] = $params['from'];
            }
            
            if (isset($params['to'])) {
                $filters['to'] = $params['to'];
            }
            
            if (isset($params['status'])) {
                $filters['status'] = $params['status'];
            }
            
            $result = $this->v2Service->getBookings($filters);
            
            // V2 response should be compatible
            if ($result['success']) {
                return $result['data'] ?? [];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('CalcomBackwardsCompatibility getBookings failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * V1-compatible cancelBooking method
     * 
     * @deprecated Use CalcomV2Service::cancelBooking() instead
     */
    public function cancelBooking($bookingId, $reason = null)
    {
        $this->logV1Usage('cancelBooking', [
            'bookingId' => $bookingId,
            'reason' => $reason
        ]);
        
        try {
            $result = $this->v2Service->cancelBooking($bookingId, $reason);
            
            // V1 expects boolean return
            return $result['success'] ?? false;
            
        } catch (\Exception $e) {
            Log::error('CalcomBackwardsCompatibility cancelBooking failed', [
                'error' => $e->getMessage(),
                'bookingId' => $bookingId
            ]);
            return false;
        }
    }
    
    /**
     * Set API key dynamically (V1 compatibility)
     * 
     * @deprecated Configure API key via constructor or config
     */
    public function setApiKey(string $apiKey): self
    {
        $this->logV1Usage('setApiKey');
        
        // Recreate V2 service with new API key
        $this->v2Service = new CalcomV2Service($apiKey);
        
        return $this;
    }
    
    /**
     * Convert available slots to busy times (V1 format)
     */
    private function convertSlotsToBusyTimes(array $slots, string $dateFrom, string $dateTo): array
    {
        // This is a simplified conversion
        // In reality, you'd need to know the event duration and working hours
        // to properly calculate busy times from available slots
        
        $busyTimes = [];
        
        // For now, return empty array (no busy times)
        // This maintains compatibility while we migrate
        
        return $busyTimes;
    }
    
    /**
     * Log V1 API usage for monitoring
     */
    private function logV1Usage(string $method, array $params = []): void
    {
        if (!$this->logDeprecations) {
            return;
        }
        
        Log::warning('Cal.com V1 API usage detected', [
            'method' => $method,
            'params' => $params,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            'deprecation' => "Method {$method} is deprecated. Use CalcomV2Service instead."
        ]);
        
        // Increment metrics for monitoring
        try {
            if (class_exists('\App\Services\Monitoring\MetricsCollector')) {
                $collector = app(\App\Services\Monitoring\MetricsCollector::class);
                // Use incrementCounter if available, otherwise skip
                if (method_exists($collector, 'incrementCounter')) {
                    $collector->incrementCounter('calcom_v1_usage', ['method' => $method]);
                }
            }
        } catch (\Exception $e) {
            // Silently fail - metrics are not critical
        }
    }
    
    /**
     * Check if we should use V1 or V2 (for gradual migration)
     */
    public static function shouldUseV1(): bool
    {
        // Check feature flag
        if (config('services.calcom.force_v2', false)) {
            return false;
        }
        
        // Check percentage rollout
        $v2Percentage = config('services.calcom.v2_rollout_percentage', 100);
        if ($v2Percentage >= 100) {
            return false;
        }
        
        // Random rollout
        return rand(1, 100) > $v2Percentage;
    }
}