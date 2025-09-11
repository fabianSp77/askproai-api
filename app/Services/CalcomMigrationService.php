<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cal.com V1 to V2 Migration Service
 * 
 * Handles the transition from Cal.com V1 API (deprecated end 2025) to V2 API
 * with automatic fallback, migration tracking, and zero-downtime transition.
 */
class CalcomMigrationService
{
    protected CalcomService $v1Service;
    protected CalcomV2Service $v2Service;
    protected CalcomHybridService $hybridService;
    
    /**
     * Migration status tracking
     */
    protected array $migrationStatus = [
        'bookings' => 'v2_ready',      // V2 fully supported
        'event_types' => 'v1_only',     // Still on V1
        'availability' => 'hybrid',      // Using both with fallback
        'webhooks' => 'v2_ready',        // V2 fully supported
        'users' => 'v2_ready',           // V2 fully supported
        'schedules' => 'v2_ready',       // V2 fully supported
    ];
    
    /**
     * Feature flags for gradual rollout
     */
    protected array $featureFlags = [
        'use_v2_bookings' => true,
        'use_v2_event_types' => false,  // Not yet migrated
        'use_v2_availability' => true,
        'use_v2_webhooks' => true,
        'enable_fallback' => true,       // Auto-fallback to V1 on V2 failure
        'log_migration_metrics' => true,
    ];
    
    public function __construct(
        CalcomService $v1Service,
        CalcomV2Service $v2Service,
        CalcomHybridService $hybridService
    ) {
        $this->v1Service = $v1Service;
        $this->v2Service = $v2Service;
        $this->hybridService = $hybridService;
        
        // Load migration status from cache/config
        $this->loadMigrationStatus();
    }
    
    /**
     * Create a booking with automatic V1/V2 selection and fallback
     */
    public function createBooking(array $data): array
    {
        $startTime = microtime(true);
        
        try {
            if ($this->shouldUseV2('bookings')) {
                try {
                    $result = $this->v2Service->createBooking($data);
                    $this->logMigrationSuccess('bookings', 'v2', microtime(true) - $startTime);
                    return $result;
                } catch (\Exception $v2Error) {
                    Log::warning('[CalcomMigration] V2 booking failed, attempting fallback', [
                        'error' => $v2Error->getMessage()
                    ]);
                    
                    if ($this->featureFlags['enable_fallback']) {
                        return $this->fallbackToV1Booking($data);
                    }
                    throw $v2Error;
                }
            }
            
            // Still using V1
            return $this->v1CreateBookingAdapter($data);
            
        } catch (\Exception $e) {
            $this->logMigrationError('bookings', $e);
            throw $e;
        }
    }
    
    /**
     * Get event types with migration handling
     */
    public function getEventTypes(array $filters = []): array
    {
        // Event types still on V1 - needs migration
        if ($this->shouldUseV2('event_types')) {
            try {
                return $this->v2Service->getEventTypes($filters);
            } catch (\Exception $e) {
                Log::warning('[CalcomMigration] V2 event types failed, using V1', [
                    'error' => $e->getMessage()
                ]);
                
                if ($this->featureFlags['enable_fallback']) {
                    return $this->v1GetEventTypesAdapter($filters);
                }
                throw $e;
            }
        }
        
        return $this->v1GetEventTypesAdapter($filters);
    }
    
    /**
     * Check availability with hybrid approach
     */
    public function checkAvailability(array $params): array
    {
        if ($this->shouldUseV2('availability')) {
            try {
                // V2 availability check
                $slots = $this->v2Service->getAvailableSlots(
                    $params['eventTypeId'],
                    $params['dateFrom'],
                    $params['dateTo'],
                    $params['duration'] ?? null
                );
                
                $this->logMigrationSuccess('availability', 'v2', 0);
                return $this->formatAvailabilityResponse($slots);
                
            } catch (\Exception $e) {
                Log::warning('[CalcomMigration] V2 availability failed, using V1', [
                    'error' => $e->getMessage()
                ]);
                
                if ($this->featureFlags['enable_fallback']) {
                    return $this->v1Service->checkAvailability($params);
                }
                throw $e;
            }
        }
        
        return $this->v1Service->checkAvailability($params);
    }
    
    /**
     * Cancel booking with V2 preference
     */
    public function cancelBooking(string $uid, string $reason): array
    {
        try {
            // Always try V2 first for cancellations
            return $this->v2Service->cancelBooking($uid, $reason);
        } catch (\Exception $e) {
            Log::error('[CalcomMigration] Failed to cancel booking', [
                'uid' => $uid,
                'error' => $e->getMessage()
            ]);
            
            // V1 doesn't support direct cancellation - mark locally
            return $this->localCancellation($uid, $reason);
        }
    }
    
    /**
     * Reschedule booking with V2 preference
     */
    public function rescheduleBooking(string $uid, array $data): array
    {
        try {
            return $this->v2Service->rescheduleBooking($uid, $data);
        } catch (\Exception $e) {
            Log::error('[CalcomMigration] Failed to reschedule booking', [
                'uid' => $uid,
                'error' => $e->getMessage()
            ]);
            
            // Fallback: Cancel and create new
            if ($this->featureFlags['enable_fallback']) {
                $this->cancelBooking($uid, 'Rescheduling');
                return $this->createBooking($data);
            }
            throw $e;
        }
    }
    
    /**
     * Get migration readiness report
     */
    public function getMigrationReport(): array
    {
        $report = [
            'overall_progress' => $this->calculateMigrationProgress(),
            'api_status' => $this->migrationStatus,
            'feature_flags' => $this->featureFlags,
            'v1_deprecation_date' => '2025-12-31',
            'days_until_deprecation' => $this->daysUntilDeprecation(),
            'recommendations' => $this->getMigrationRecommendations(),
            'metrics' => $this->getMigrationMetrics(),
        ];
        
        return $report;
    }
    
    /**
     * Run migration health check
     */
    public function healthCheck(): array
    {
        $health = [
            'v1_status' => $this->checkV1Health(),
            'v2_status' => $this->checkV2Health(),
            'migration_ready' => true,
            'issues' => [],
        ];
        
        // Check V1 dependencies
        if ($this->hasV1Dependencies()) {
            $health['issues'][] = 'Critical V1 dependencies detected';
            $health['migration_ready'] = false;
        }
        
        // Check V2 readiness
        if (!$this->isV2Ready()) {
            $health['issues'][] = 'V2 API not fully configured';
            $health['migration_ready'] = false;
        }
        
        return $health;
    }
    
    /**
     * Migrate specific feature to V2
     */
    public function migrateFeature(string $feature): bool
    {
        if (!isset($this->migrationStatus[$feature])) {
            throw new \InvalidArgumentException("Unknown feature: {$feature}");
        }
        
        try {
            // Test V2 endpoint
            $this->testV2Feature($feature);
            
            // Update migration status
            $this->migrationStatus[$feature] = 'v2_ready';
            $this->saveMigrationStatus();
            
            // Enable V2 for this feature
            $this->featureFlags["use_v2_{$feature}"] = true;
            
            Log::info("[CalcomMigration] Successfully migrated {$feature} to V2");
            return true;
            
        } catch (\Exception $e) {
            Log::error("[CalcomMigration] Failed to migrate {$feature} to V2", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Rollback feature to V1 (emergency use only)
     */
    public function rollbackFeature(string $feature): bool
    {
        Log::warning("[CalcomMigration] Rolling back {$feature} to V1");
        
        $this->migrationStatus[$feature] = 'v1_only';
        $this->featureFlags["use_v2_{$feature}"] = false;
        $this->saveMigrationStatus();
        
        return true;
    }
    
    // ========== Private Helper Methods ==========
    
    private function shouldUseV2(string $feature): bool
    {
        // Check feature flag
        $flagKey = "use_v2_{$feature}";
        if (!isset($this->featureFlags[$flagKey])) {
            return false;
        }
        
        // Check if V2 is enabled for this feature
        return $this->featureFlags[$flagKey] && 
               in_array($this->migrationStatus[$feature], ['v2_ready', 'hybrid']);
    }
    
    private function fallbackToV1Booking(array $data): array
    {
        Log::info('[CalcomMigration] Falling back to V1 for booking');
        return $this->v1CreateBookingAdapter($data);
    }
    
    private function v1CreateBookingAdapter(array $data): array
    {
        // Adapt V2 data format to V1
        $v1Data = [
            'eventTypeId' => $data['eventTypeId'],
            'start' => $data['start'],
            'name' => $data['attendees'][0]['name'] ?? $data['name'],
            'email' => $data['attendees'][0]['email'] ?? $data['email'],
            'timeZone' => $data['timeZone'] ?? 'Europe/Berlin',
            'language' => $data['language'] ?? 'de',
        ];
        
        return $this->v1Service->createBookingFromCall($v1Data);
    }
    
    private function v1GetEventTypesAdapter(array $filters): array
    {
        // V1 doesn't have a direct getEventTypes method
        // We need to work with individual event types
        try {
            $eventTypeId = config('services.calcom.event_type_id');
            if (!$eventTypeId) {
                throw new \Exception('No event type ID configured for V1 API');
            }
            
            // Get single event type and wrap in array
            $eventType = $this->v1Service->getEventType($eventTypeId);
            return ['event_types' => [$eventType]];
            
        } catch (\Exception $e) {
            Log::error('[CalcomMigration] V1 event types adapter failed', [
                'error' => $e->getMessage()
            ]);
            return ['event_types' => []];
        }
    }
    
    private function formatAvailabilityResponse(array $slots): array
    {
        // Format V2 slots to match expected response format
        return [
            'slots' => $slots,
            'timezone' => config('services.calcom.timezone'),
        ];
    }
    
    private function localCancellation(string $uid, string $reason): array
    {
        // Handle cancellation locally when API doesn't support it
        DB::table('appointments')
            ->where('calcom_booking_uid', $uid)
            ->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'updated_at' => now(),
            ]);
            
        return [
            'status' => 'cancelled_locally',
            'uid' => $uid,
            'reason' => $reason,
        ];
    }
    
    private function loadMigrationStatus(): void
    {
        $cached = Cache::get('calcom_migration_status');
        if ($cached) {
            $this->migrationStatus = $cached['status'] ?? $this->migrationStatus;
            $this->featureFlags = $cached['flags'] ?? $this->featureFlags;
        }
    }
    
    private function saveMigrationStatus(): void
    {
        Cache::put('calcom_migration_status', [
            'status' => $this->migrationStatus,
            'flags' => $this->featureFlags,
            'updated_at' => now()->toIso8601String(),
        ], now()->addHours(24));
    }
    
    private function logMigrationSuccess(string $feature, string $version, float $duration): void
    {
        if ($this->featureFlags['log_migration_metrics']) {
            Cache::increment("calcom_migration_{$feature}_{$version}_success");
            Cache::put("calcom_migration_{$feature}_last_success", now());
            
            Log::info("[CalcomMigration] {$feature} success via {$version}", [
                'duration_ms' => round($duration * 1000, 2)
            ]);
        }
    }
    
    private function logMigrationError(string $feature, \Exception $error): void
    {
        Cache::increment("calcom_migration_{$feature}_errors");
        
        Log::error("[CalcomMigration] {$feature} error", [
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString()
        ]);
    }
    
    private function calculateMigrationProgress(): float
    {
        $total = count($this->migrationStatus);
        $migrated = count(array_filter($this->migrationStatus, function($status) {
            return $status === 'v2_ready';
        }));
        
        return round(($migrated / $total) * 100, 2);
    }
    
    private function daysUntilDeprecation(): int
    {
        $deprecationDate = new \DateTime('2025-12-31');
        $today = new \DateTime();
        $diff = $today->diff($deprecationDate);
        
        return $diff->days;
    }
    
    private function getMigrationRecommendations(): array
    {
        $recommendations = [];
        
        if ($this->migrationStatus['event_types'] === 'v1_only') {
            $recommendations[] = [
                'priority' => 'high',
                'feature' => 'event_types',
                'action' => 'Migrate event types to V2 API',
                'reason' => 'Still using deprecated V1 endpoint',
            ];
        }
        
        if ($this->daysUntilDeprecation() < 180) {
            $recommendations[] = [
                'priority' => 'critical',
                'feature' => 'all',
                'action' => 'Complete V2 migration urgently',
                'reason' => 'Less than 6 months until V1 deprecation',
            ];
        }
        
        return $recommendations;
    }
    
    private function getMigrationMetrics(): array
    {
        return [
            'v1_calls_today' => Cache::get('calcom_migration_v1_calls_today', 0),
            'v2_calls_today' => Cache::get('calcom_migration_v2_calls_today', 0),
            'fallback_count' => Cache::get('calcom_migration_fallback_count', 0),
            'error_rate' => $this->calculateErrorRate(),
        ];
    }
    
    private function calculateErrorRate(): float
    {
        $total = Cache::get('calcom_migration_total_calls', 0);
        $errors = Cache::get('calcom_migration_total_errors', 0);
        
        return $total > 0 ? round(($errors / $total) * 100, 2) : 0;
    }
    
    private function checkV1Health(): string
    {
        try {
            // Simple health check for V1 - check if we can get a specific event type
            $eventTypeId = config('services.calcom.event_type_id');
            if ($eventTypeId) {
                $this->v1Service->getEventType($eventTypeId);
            } else {
                // Just try to make a basic API call to check connectivity
                $this->v1Service->checkAvailability([
                    'dateFrom' => now()->toDateString(),
                    'dateTo' => now()->addDay()->toDateString(),
                    'eventTypeId' => 1, // Dummy ID for health check
                ]);
            }
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }
    
    private function checkV2Health(): string
    {
        try {
            // Simple health check for V2
            $this->v2Service->getOrganization(config('services.calcom.organization_id'));
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }
    
    private function hasV1Dependencies(): bool
    {
        // Check if any critical features still depend on V1
        foreach ($this->migrationStatus as $feature => $status) {
            if ($status === 'v1_only' && in_array($feature, ['bookings', 'event_types'])) {
                return true;
            }
        }
        return false;
    }
    
    private function isV2Ready(): bool
    {
        // Check if V2 configuration is complete
        return !empty(config('services.calcom.api_key')) &&
               !empty(config('services.calcom.v2_base_url')) &&
               !empty(config('services.calcom.organization_id'));
    }
    
    private function testV2Feature(string $feature): void
    {
        // Test specific V2 feature endpoint
        switch ($feature) {
            case 'bookings':
                $this->v2Service->getBookings(['limit' => 1]);
                break;
            case 'event_types':
                $this->v2Service->getEventTypes(['limit' => 1]);
                break;
            case 'availability':
                // Test with dummy data
                $eventTypeId = config('services.calcom.event_type_id');
                if ($eventTypeId) {
                    $this->v2Service->getAvailableSlots(
                        $eventTypeId,
                        now()->toDateString(),
                        now()->addDays(7)->toDateString()
                    );
                }
                break;
            default:
                throw new \Exception("No test available for feature: {$feature}");
        }
    }
}