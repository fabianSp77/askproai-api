<?php

namespace App\HealthChecks;

use App\Services\CalcomV2Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalcomAvailabilityHealthCheck
{
    protected CalcomV2Service $calcomService;
    
    public function __construct(CalcomV2Service $calcomService)
    {
        $this->calcomService = $calcomService;
    }
    
    /**
     * Check if Cal.com availability API is working
     */
    public function check(): array
    {
        try {
            // Use a test event type ID from config or environment
            $testEventTypeId = config('health.calcom_test_event_type_id', env('HEALTH_CHECK_EVENT_TYPE_ID'));
            
            if (!$testEventTypeId) {
                return [
                    'status' => 'warning',
                    'message' => 'No test event type configured for health check',
                    'meta' => [
                        'checked_at' => now()->toIso8601String()
                    ]
                ];
            }
            
            // Check availability for tomorrow
            $tomorrow = Carbon::tomorrow()->format('Y-m-d');
            
            $startTime = microtime(true);
            $result = $this->calcomService->checkAvailability(
                $testEventTypeId,
                $tomorrow,
                'Europe/Berlin'
            );
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result['success']) {
                $slotCount = count($result['data']['slots'] ?? []);
                
                // Check if we got reasonable results
                if ($slotCount === 0) {
                    return [
                        'status' => 'warning',
                        'message' => 'Cal.com API working but no slots available',
                        'meta' => [
                            'event_type_id' => $testEventTypeId,
                            'date' => $tomorrow,
                            'response_time_ms' => $responseTime,
                            'checked_at' => now()->toIso8601String()
                        ]
                    ];
                }
                
                return [
                    'status' => 'ok',
                    'message' => 'Cal.com availability check working',
                    'meta' => [
                        'event_type_id' => $testEventTypeId,
                        'date' => $tomorrow,
                        'slot_count' => $slotCount,
                        'response_time_ms' => $responseTime,
                        'fallback' => $result['data']['fallback'] ?? false,
                        'source' => $result['data']['source'] ?? 'api',
                        'checked_at' => now()->toIso8601String()
                    ]
                ];
            }
            
            return [
                'status' => 'failed',
                'message' => 'Cal.com availability check failed: ' . ($result['error'] ?? 'Unknown error'),
                'meta' => [
                    'event_type_id' => $testEventTypeId,
                    'date' => $tomorrow,
                    'response_time_ms' => $responseTime,
                    'fallback' => $result['fallback'] ?? false,
                    'checked_at' => now()->toIso8601String()
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => 'failed',
                'message' => 'Cal.com health check error: ' . $e->getMessage(),
                'meta' => [
                    'checked_at' => now()->toIso8601String()
                ]
            ];
        }
    }
    
    /**
     * Get health check name
     */
    public function getName(): string
    {
        return 'Cal.com Availability API';
    }
    
    /**
     * Get health check description
     */
    public function getDescription(): string
    {
        return 'Checks if Cal.com availability API is accessible and returning valid slot data';
    }
}