<?php

namespace App\Services\Calcom;

use App\Services\CalcomV2Service as LegacyService;
use App\Services\Calcom\CalcomV2Client;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

/**
 * Migration Adapter for Cal.com V2
 * 
 * This adapter provides a gradual migration path from the mixed V1/V2 implementation
 * to pure V2 by wrapping the new CalcomV2Client with the existing interface.
 * 
 * Usage:
 * 1. Replace CalcomV2Service with CalcomV2MigrationAdapter in dependency injection
 * 2. Use feature flags to control which methods use V2
 * 3. Monitor and compare results
 * 4. Gradually enable V2 for all methods
 */
class CalcomV2MigrationAdapter extends LegacyService
{
    private CalcomV2Client $v2Client;
    private array $v2EnabledMethods;
    
    public function __construct(?Company $company = null)
    {
        parent::__construct($company);
        
        $apiKey = $company?->calcom_api_key ?? config('services.calcom.api_key');
        $this->v2Client = new CalcomV2Client($apiKey);
        
        // Control which methods use V2 via config
        $this->v2EnabledMethods = config('calcom-v2.enabled_methods', [
            'getEventTypes' => true,
            'checkAvailability' => true,
            'bookAppointment' => false, // Start with non-critical methods
            'cancelBooking' => false,
            'getBooking' => true,
        ]);
    }
    
    /**
     * Get event types - migrate to V2
     */
    public function getEventTypes()
    {
        if ($this->shouldUseV2('getEventTypes')) {
            try {
                Log::info('Using Cal.com V2 for getEventTypes');
                $result = $this->v2Client->getEventTypes();
                
                // Log for comparison
                $this->logMigration('getEventTypes', 'v2', true, $result);
                
                return ['event_types' => $result];
            } catch (\Exception $e) {
                Log::error('Cal.com V2 getEventTypes failed, falling back to legacy', [
                    'error' => $e->getMessage()
                ]);
                $this->logMigration('getEventTypes', 'v2', false, $e->getMessage());
            }
        }
        
        // Fallback to legacy implementation
        return parent::getEventTypes();
    }
    
    /**
     * Check availability - migrate to V2
     */
    public function checkAvailability($eventTypeId, $date, $timezone = 'Europe/Berlin')
    {
        if ($this->shouldUseV2('checkAvailability')) {
            try {
                Log::info('Using Cal.com V2 for checkAvailability');
                
                $startTime = $date . 'T00:00:00.000Z';
                $endTime = $date . 'T23:59:59.999Z';
                
                $params = [
                    'eventTypeId' => $eventTypeId,
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'timeZone' => $timezone
                ];
                
                $slots = $this->v2Client->getAvailableSlots($params);
                
                // Transform to legacy format
                $result = [
                    'success' => true,
                    'slots' => $slots
                ];
                
                $this->logMigration('checkAvailability', 'v2', true, $result);
                
                return $result;
            } catch (\Exception $e) {
                Log::error('Cal.com V2 checkAvailability failed, falling back to legacy', [
                    'error' => $e->getMessage()
                ]);
                $this->logMigration('checkAvailability', 'v2', false, $e->getMessage());
            }
        }
        
        return parent::checkAvailability($eventTypeId, $date, $timezone);
    }
    
    /**
     * Book appointment - migrate to V2
     */
    public function bookAppointment(array $bookingData)
    {
        if ($this->shouldUseV2('bookAppointment')) {
            try {
                Log::info('Using Cal.com V2 for bookAppointment', [
                    'event_type_id' => $bookingData['eventTypeId'] ?? null
                ]);
                
                $result = $this->v2Client->createBooking($bookingData);
                
                $this->logMigration('bookAppointment', 'v2', true, $result);
                
                return $result;
            } catch (\Exception $e) {
                Log::error('Cal.com V2 bookAppointment failed, falling back to legacy', [
                    'error' => $e->getMessage()
                ]);
                $this->logMigration('bookAppointment', 'v2', false, $e->getMessage());
            }
        }
        
        return parent::bookAppointment($bookingData);
    }
    
    /**
     * Cancel booking - migrate to V2
     */
    public function cancelBooking(string $bookingUid, string $reason = '')
    {
        if ($this->shouldUseV2('cancelBooking')) {
            try {
                Log::info('Using Cal.com V2 for cancelBooking', [
                    'booking_uid' => $bookingUid
                ]);
                
                $result = $this->v2Client->cancelBooking($bookingUid, $reason);
                
                $this->logMigration('cancelBooking', 'v2', true, $result);
                
                return $result;
            } catch (\Exception $e) {
                Log::error('Cal.com V2 cancelBooking failed, falling back to legacy', [
                    'error' => $e->getMessage()
                ]);
                $this->logMigration('cancelBooking', 'v2', false, $e->getMessage());
            }
        }
        
        return parent::cancelBooking($bookingUid, $reason);
    }
    
    /**
     * Get booking - migrate to V2
     */
    public function getBooking(string $bookingUid)
    {
        if ($this->shouldUseV2('getBooking')) {
            try {
                Log::info('Using Cal.com V2 for getBooking', [
                    'booking_uid' => $bookingUid
                ]);
                
                $result = $this->v2Client->getBooking($bookingUid);
                
                $this->logMigration('getBooking', 'v2', true, $result);
                
                return $result;
            } catch (\Exception $e) {
                Log::error('Cal.com V2 getBooking failed, falling back to legacy', [
                    'error' => $e->getMessage()
                ]);
                $this->logMigration('getBooking', 'v2', false, $e->getMessage());
            }
        }
        
        return parent::getBooking($bookingUid);
    }
    
    /**
     * Check if V2 should be used for a method
     */
    private function shouldUseV2(string $method): bool
    {
        // Check feature flag
        if (!config('calcom-v2.enabled', false)) {
            return false;
        }
        
        // Check method-specific flag
        return $this->v2EnabledMethods[$method] ?? false;
    }
    
    /**
     * Log migration attempt for monitoring
     */
    private function logMigration(string $method, string $version, bool $success, $data = null): void
    {
        Log::channel('calcom_migration')->info('Cal.com API migration attempt', [
            'method' => $method,
            'version' => $version,
            'success' => $success,
            'company_id' => $this->company?->id,
            'data_sample' => is_array($data) ? array_slice($data, 0, 100) : $data
        ]);
    }
}