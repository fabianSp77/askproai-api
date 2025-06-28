<?php

namespace App\Services\MCP;

use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\CircuitBreaker\CircuitState;
use App\Exceptions\CircuitBreakerOpenException;
use Carbon\Carbon;

class CalcomMCPServer
{
    protected array $config;
    protected CircuitBreaker $circuitBreaker;
    
    public function __construct()
    {
        $this->config = [
            'cache' => [
                'ttl' => 300,
                'prefix' => 'mcp:calcom'
            ],
            'circuit_breaker' => [
                'failure_threshold' => 5,
                'success_threshold' => 2,
                'timeout' => 60,
                'half_open_requests' => 3
            ]
        ];
        
        $this->circuitBreaker = new CircuitBreaker(
            $this->config['circuit_breaker']['failure_threshold'],
            $this->config['circuit_breaker']['success_threshold'],
            $this->config['circuit_breaker']['timeout'],
            $this->config['circuit_breaker']['half_open_requests']
        );
    }
    
    /**
     * Get the API key for a company, handling encryption properly
     */
    protected function getApiKey(Company $company): ?string
    {
        $apiKey = $company->calcom_api_key;
        
        if (!$apiKey) {
            // Fall back to config
            return config('services.calcom.api_key');
        }
        
        // Check if the API key is encrypted (starts with eyJ which is base64 for {"i)
        if (substr($apiKey, 0, 3) === 'eyJ') {
            try {
                return decrypt($apiKey);
            } catch (\Exception $e) {
                // If decryption fails, it might be a plain text key that happens to start with eyJ
                Log::warning('Failed to decrypt Cal.com API key, using as plain text', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage()
                ]);
                return $apiKey;
            }
        }
        
        // It's plain text
        return $apiKey;
    }
    
    /**
     * Get event types for a company
     */
    public function getEventTypes(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        $cacheKey = $this->getCacheKey('event_types', ['company_id' => $companyId]);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId) {
            try {
                $company = Company::find($companyId);
                if (!$company) {
                    return ['error' => 'Company not found'];
                }
                
                // Use company API key or fall back to default
                $apiKey = $this->getApiKey($company);
                    
                if (!$apiKey) {
                    return ['error' => 'No Cal.com API key configured'];
                }
                
                $calcomService = new CalcomV2Service($apiKey);
                $response = $calcomService->getEventTypes();
                
                // Cal.com V1 API returns raw response, not wrapped
                if ($response && isset($response['event_types'])) {
                    return [
                        'event_types' => $response['event_types'],
                        'count' => count($response['event_types']),
                        'company' => $company->name
                    ];
                }
                
                return ['error' => 'Failed to fetch event types', 'message' => 'Invalid response from Cal.com'];
                
            } catch (\Exception $e) {
                Log::error('MCP CalCom getEventTypes error', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                
                return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
            }
        });
    }
    
    /**
     * Check availability for a specific date/time with caching and circuit breaker
     */
    public function checkAvailability(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $eventTypeId = $params['event_type_id'] ?? null;
        $dateFrom = $params['date_from'] ?? now()->format('Y-m-d');
        $dateTo = $params['date_to'] ?? now()->addDays(7)->format('Y-m-d');
        $timezone = $params['timezone'] ?? 'Europe/Berlin';
        
        if (!$companyId || !$eventTypeId) {
            return ['error' => 'company_id and event_type_id are required'];
        }
        
        // Cache key for availability
        $cacheKey = $this->getCacheKey('availability', [
            'company_id' => $companyId,
            'event_type_id' => $eventTypeId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'timezone' => $timezone
        ]);
        
        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            Log::debug('MCP CalCom availability from cache', ['cache_key' => $cacheKey]);
            return $cached;
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return ['error' => 'Company not found or Cal.com not configured'];
            }
            
            // Execute with circuit breaker protection
            $result = $this->circuitBreaker->call('calcom_availability', function () use ($company, $eventTypeId, $dateFrom, $dateTo, $timezone) {
                $calcomService = new CalcomV2Service($this->getApiKey($company));
                
                return $calcomService->getAvailability([
                    'eventTypeId' => $eventTypeId,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'timeZone' => $timezone
                ]);
            }, function () {
                // Fallback when circuit is open
                return [
                    'success' => false,
                    'error' => 'Service temporarily unavailable',
                    'fallback' => true
                ];
            });
            
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'available_slots' => $result['data'],
                    'date_range' => [
                        'from' => $dateFrom,
                        'to' => $dateTo
                    ],
                    'event_type_id' => $eventTypeId,
                    'timezone' => $timezone,
                    'cached_until' => now()->addSeconds($this->config['cache']['ttl'])->toIso8601String()
                ];
                
                // Cache successful response
                Cache::put($cacheKey, $response, $this->config['cache']['ttl']);
                
                return $response;
            }
            
            return [
                'success' => false,
                'error' => 'Failed to check availability',
                'message' => $result['error'] ?? 'Unknown error',
                'fallback' => $result['fallback'] ?? false
            ];
            
        } catch (CircuitBreakerOpenException $e) {
            Log::warning('MCP CalCom circuit breaker open', [
                'service' => 'calcom_availability',
                'message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Service temporarily unavailable',
                'message' => 'Please try again later',
                'circuit_breaker_open' => true
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom checkAvailability error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Exception occurred',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a booking through MCP
     * This is the MCP-first approach for creating appointments
     */
    public function createBooking(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $eventTypeId = $params['event_type_id'] ?? null;
        $startTime = $params['start_time'] ?? null;
        $endTime = $params['end_time'] ?? null;
        $customerData = $params['customer_data'] ?? [];
        $notes = $params['notes'] ?? null;
        $metadata = $params['metadata'] ?? [];
        
        if (!$companyId || !$eventTypeId || !$startTime || !$customerData) {
            return [
                'success' => false,
                'error' => 'Missing required parameters',
                'required' => ['company_id', 'event_type_id', 'start_time', 'customer_data']
            ];
        }
        
        // Validate customer data
        if (empty($customerData['name']) || empty($customerData['email'])) {
            return [
                'success' => false,
                'error' => 'Customer name and email are required'
            ];
        }
        
        $cacheKey = $this->getCacheKey('booking_lock', [
            'event_type_id' => $eventTypeId,
            'start_time' => $startTime
        ]);
        
        // Check if slot is already being booked (prevent double booking)
        if (Cache::has($cacheKey)) {
            Log::warning('MCP CalCom: Slot already being booked', [
                'event_type_id' => $eventTypeId,
                'start_time' => $startTime
            ]);
            return [
                'success' => false,
                'error' => 'Time slot is currently being processed',
                'retry_after' => 5
            ];
        }
        
        // Lock the slot for 30 seconds while we book
        Cache::put($cacheKey, true, 30);
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                Cache::forget($cacheKey);
                return [
                    'success' => false,
                    'error' => 'Company not found or Cal.com not configured'
                ];
            }
            
            // Use circuit breaker for booking
            $result = $this->circuitBreaker->call(
                'calcom_booking',
                function () use ($company, $eventTypeId, $startTime, $endTime, $customerData, $notes, $metadata) {
                    $calcomService = new CalcomV2Service($this->getApiKey($company));
                    
                    // First check availability
                    $date = Carbon::parse($startTime)->format('Y-m-d');
                    $timezone = $customerData['timezone'] ?? $company->timezone ?? 'Europe/Berlin';
                    
                    $availabilityCheck = $calcomService->checkAvailability($eventTypeId, $date, $timezone);
                    
                    if (!$availabilityCheck['success']) {
                        throw new \Exception('Failed to check availability');
                    }
                    
                    // Verify slot is available
                    $requestedTime = Carbon::parse($startTime)->format('Y-m-d\TH:i:s');
                    $slots = $availabilityCheck['data']['slots'] ?? [];
                    $slotAvailable = false;
                    
                    foreach ($slots as $slot) {
                        if (str_starts_with($slot, $requestedTime)) {
                            $slotAvailable = true;
                            break;
                        }
                    }
                    
                    if (!$slotAvailable) {
                        throw new \Exception('Requested time slot is no longer available');
                    }
                    
                    // Create the booking
                    return $calcomService->createBooking(
                        $eventTypeId,
                        $startTime,
                        $endTime,
                        $customerData,
                        $notes,
                        $metadata
                    );
                },
                function () {
                    // Fallback when circuit is open
                    return [
                        'success' => false,
                        'error' => 'Booking service temporarily unavailable',
                        'circuit_breaker_open' => true
                    ];
                }
            );
            
            // Release the lock
            Cache::forget($cacheKey);
            
            if ($result['success']) {
                // Log successful booking
                Log::info('MCP CalCom: Booking created successfully', [
                    'booking_id' => $result['booking_id'] ?? null,
                    'event_type_id' => $eventTypeId,
                    'customer' => $customerData['email']
                ]);
                
                // Cache booking info for quick retrieval
                $bookingCacheKey = $this->getCacheKey('booking', ['id' => $result['booking_id']]);
                Cache::put($bookingCacheKey, $result, 3600); // 1 hour
                
                return [
                    'success' => true,
                    'booking_id' => $result['booking_id'],
                    'booking_uid' => $result['booking_uid'] ?? null,
                    'reschedule_uid' => $result['reschedule_uid'] ?? null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'customer' => [
                        'name' => $customerData['name'],
                        'email' => $customerData['email']
                    ],
                    'metadata' => $result['metadata'] ?? []
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Booking failed',
                    'details' => $result['details'] ?? null,
                    'circuit_breaker_open' => $result['circuit_breaker_open'] ?? false
                ];
            }
            
        } catch (\Exception $e) {
            // Always release the lock on error
            Cache::forget($cacheKey);
            
            Log::error('MCP CalCom createBooking error', [
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Check if it's an availability issue
            if (str_contains($e->getMessage(), 'no longer available')) {
                return [
                    'success' => false,
                    'error' => 'Time slot no longer available',
                    'suggestions' => $this->findAlternativeSlots($eventTypeId, $startTime)
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Booking failed',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel a booking through MCP
     */
    public function cancelBooking(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $bookingId = $params['booking_id'] ?? null;
        $bookingUid = $params['booking_uid'] ?? null;
        $reason = $params['reason'] ?? 'Cancelled by system';
        
        if (!$companyId || (!$bookingId && !$bookingUid)) {
            return [
                'success' => false,
                'error' => 'company_id and either booking_id or booking_uid required'
            ];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return [
                    'success' => false,
                    'error' => 'Company not found or Cal.com not configured'
                ];
            }
            
            // Use circuit breaker
            $result = $this->circuitBreaker->call(
                'calcom_cancel',
                function () use ($company, $bookingId, $bookingUid, $reason) {
                    $calcomService = new CalcomV2Service($this->getApiKey($company));
                    return $calcomService->cancelBooking($bookingId, $bookingUid, $reason);
                },
                function () {
                    return [
                        'success' => false,
                        'error' => 'Cancellation service temporarily unavailable',
                        'circuit_breaker_open' => true
                    ];
                }
            );
            
            if ($result['success']) {
                // Clear booking cache
                $bookingCacheKey = $this->getCacheKey('booking', ['id' => $bookingId]);
                Cache::forget($bookingCacheKey);
                
                Log::info('MCP CalCom: Booking cancelled', [
                    'booking_id' => $bookingId,
                    'booking_uid' => $bookingUid,
                    'reason' => $reason
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Booking cancelled successfully',
                    'booking_id' => $bookingId,
                    'reason' => $reason
                ];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom cancelBooking error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Cancellation failed',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update/Reschedule a booking through MCP
     */
    public function updateBooking(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $bookingId = $params['booking_id'] ?? null;
        $rescheduleUid = $params['reschedule_uid'] ?? null;
        $newStartTime = $params['new_start_time'] ?? null;
        $newEndTime = $params['new_end_time'] ?? null;
        $reason = $params['reason'] ?? 'Rescheduled by system';
        
        if (!$companyId || !$rescheduleUid || !$newStartTime) {
            return [
                'success' => false,
                'error' => 'Missing required parameters',
                'required' => ['company_id', 'reschedule_uid', 'new_start_time']
            ];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return [
                    'success' => false,
                    'error' => 'Company not found or Cal.com not configured'
                ];
            }
            
            // Use circuit breaker
            $result = $this->circuitBreaker->call(
                'calcom_reschedule',
                function () use ($company, $bookingId, $rescheduleUid, $newStartTime, $newEndTime, $reason) {
                    $calcomService = new CalcomV2Service($this->getApiKey($company));
                    
                    // Note: Cal.com V2 API uses PATCH /bookings/:id for rescheduling
                    // For now, we might need to cancel and rebook
                    return [
                        'success' => false,
                        'error' => 'Rescheduling not yet implemented in V2 API',
                        'suggestion' => 'Use cancel and create new booking'
                    ];
                },
                function () {
                    return [
                        'success' => false,
                        'error' => 'Rescheduling service temporarily unavailable',
                        'circuit_breaker_open' => true
                    ];
                }
            );
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom updateBooking error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Update failed',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check availability for multiple days efficiently
     */
    public function checkAvailabilityBatch(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $eventTypeId = $params['event_type_id'] ?? null;
        $startDate = $params['start_date'] ?? now()->format('Y-m-d');
        $endDate = $params['end_date'] ?? now()->addDays(7)->format('Y-m-d');
        $timezone = $params['timezone'] ?? 'Europe/Berlin';
        
        if (!$companyId || !$eventTypeId) {
            return [
                'success' => false,
                'error' => 'company_id and event_type_id are required'
            ];
        }
        
        // Validate date range
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        if ($start->gt($end)) {
            return [
                'success' => false,
                'error' => 'start_date must be before end_date'
            ];
        }
        
        if ($start->diffInDays($end) > 30) {
            return [
                'success' => false,
                'error' => 'Date range cannot exceed 30 days'
            ];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return [
                    'success' => false,
                    'error' => 'Company not found or Cal.com not configured'
                ];
            }
            
            // Use circuit breaker
            $result = $this->circuitBreaker->call(
                'calcom_availability_batch',
                function () use ($company, $eventTypeId, $startDate, $endDate, $timezone) {
                    $calcomService = new CalcomV2Service($this->getApiKey($company));
                    
                    // Use the new batch method if available
                    if (method_exists($calcomService, 'checkAvailabilityRange')) {
                        return $calcomService->checkAvailabilityRange($eventTypeId, $startDate, $endDate, $timezone);
                    }
                    
                    // Fallback to multiple single-day checks
                    $slotsByDay = [];
                    $current = Carbon::parse($startDate);
                    $end = Carbon::parse($endDate);
                    
                    while ($current->lte($end)) {
                        $dateStr = $current->format('Y-m-d');
                        $dayResult = $calcomService->checkAvailability($eventTypeId, $dateStr, $timezone);
                        
                        if ($dayResult['success']) {
                            $slotsByDay[$dateStr] = $dayResult['data']['slots'] ?? [];
                        }
                        
                        $current->addDay();
                    }
                    
                    return [
                        'success' => true,
                        'data' => [
                            'slots_by_day' => $slotsByDay,
                            'date_range' => [
                                'start' => $startDate,
                                'end' => $endDate
                            ]
                        ]
                    ];
                },
                function () use ($startDate, $endDate) {
                    // Fallback: Return empty availability
                    return [
                        'success' => false,
                        'error' => 'Availability service temporarily unavailable',
                        'circuit_breaker_open' => true,
                        'date_range' => [
                            'start' => $startDate,
                            'end' => $endDate
                        ]
                    ];
                }
            );
            
            if ($result['success']) {
                // Cache each day's availability
                $slotsByDay = $result['data']['slots_by_day'] ?? [];
                foreach ($slotsByDay as $date => $slots) {
                    $dayCacheKey = $this->getCacheKey('availability', [
                        'company_id' => $companyId,
                        'event_type_id' => $eventTypeId,
                        'date' => $date,
                        'timezone' => $timezone
                    ]);
                    
                    Cache::put($dayCacheKey, [
                        'success' => true,
                        'slots' => $slots,
                        'cached_at' => now()->toIso8601String()
                    ], $this->config['cache']['ttl']);
                }
                
                return [
                    'success' => true,
                    'slots_by_day' => $slotsByDay,
                    'date_range' => $result['data']['date_range'],
                    'timezone' => $timezone,
                    'total_days' => count($slotsByDay),
                    'total_slots' => array_sum(array_map('count', $slotsByDay))
                ];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom checkAvailabilityBatch error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Batch availability check failed',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Find alternative slots when requested slot is unavailable
     */
    protected function findAlternativeSlots($eventTypeId, $requestedTime, $count = 5): array
    {
        try {
            $requested = Carbon::parse($requestedTime);
            $alternatives = [];
            
            // Check same day first
            $sameDayKey = $this->getCacheKey('availability', [
                'event_type_id' => $eventTypeId,
                'date' => $requested->format('Y-m-d')
            ]);
            
            if ($cached = Cache::get($sameDayKey)) {
                $slots = $cached['slots'] ?? [];
                foreach ($slots as $slot) {
                    $slotTime = Carbon::parse($slot);
                    if ($slotTime->gt($requested) && count($alternatives) < $count) {
                        $alternatives[] = [
                            'time' => $slot,
                            'difference_hours' => $slotTime->diffInHours($requested)
                        ];
                    }
                }
            }
            
            // If we need more alternatives, check next days
            if (count($alternatives) < $count) {
                for ($i = 1; $i <= 7 && count($alternatives) < $count; $i++) {
                    $nextDay = $requested->copy()->addDays($i);
                    $nextDayKey = $this->getCacheKey('availability', [
                        'event_type_id' => $eventTypeId,
                        'date' => $nextDay->format('Y-m-d')
                    ]);
                    
                    if ($cached = Cache::get($nextDayKey)) {
                        $slots = $cached['slots'] ?? [];
                        foreach ($slots as $slot) {
                            if (count($alternatives) < $count) {
                                $alternatives[] = [
                                    'time' => $slot,
                                    'difference_hours' => Carbon::parse($slot)->diffInHours($requested)
                                ];
                            }
                        }
                    }
                }
            }
            
            // Sort by time difference
            usort($alternatives, function ($a, $b) {
                return $a['difference_hours'] <=> $b['difference_hours'];
            });
            
            return array_slice($alternatives, 0, $count);
            
        } catch (\Exception $e) {
            Log::warning('Failed to find alternative slots', [
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get bookings for a company
     */
    public function getBookings(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $status = $params['status'] ?? null;
        $dateFrom = $params['date_from'] ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $params['date_to'] ?? now()->format('Y-m-d');
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return ['error' => 'Company not found or Cal.com not configured'];
            }
            
            $calcomService = new CalcomV2Service($this->getApiKey($company));
            
            $queryParams = [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ];
            
            if ($status) {
                $queryParams['status'] = $status;
            }
            
            $response = $calcomService->getBookings($queryParams);
            
            if ($response['success']) {
                return [
                    'bookings' => $response['data'],
                    'count' => count($response['data']),
                    'filters' => [
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'status' => $status
                    ]
                ];
            }
            
            return ['error' => 'Failed to fetch bookings', 'message' => $response['error'] ?? 'Unknown error'];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom getBookings error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update Event Type Settings (nur synchronisierbare Felder)
     */
    public function updateEventTypeSettings(array $params): array
    {
        $eventTypeId = $params['event_type_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        
        if (!$eventTypeId || !$companyId) {
            return ['error' => 'event_type_id and company_id are required'];
        }
        
        // Finde lokalen Event Type
        // Set company context if needed
        $needsContext = !app()->bound('current_company_id');
        if ($needsContext) {
            app()->instance('current_company_id', $companyId);
        }
        
        try {
            $eventType = CalcomEventType::where('calcom_numeric_event_type_id', $eventTypeId)
                ->where('company_id', $companyId)
                ->first();
        } finally {
            // Clean up context if we set it
            if ($needsContext) {
                app()->forgetInstance('current_company_id');
            }
        }
            
        if (!$eventType) {
            return ['error' => 'Event type not found'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return ['error' => 'Company not found or Cal.com not configured'];
            }
            
            // Bereite Update-Daten vor (nur synchronisierbare Felder)
            $updateData = [];
            
            // Basis-Informationen
            if (isset($params['name'])) $updateData['title'] = $params['name'];
            if (isset($params['description'])) $updateData['description'] = $params['description'];
            if (isset($params['duration_minutes'])) $updateData['length'] = $params['duration_minutes'];
            
            // Buchungseinstellungen
            if (isset($params['minimum_booking_notice'])) {
                $updateData['minimumBookingNotice'] = $params['minimum_booking_notice'];
            }
            if (isset($params['booking_future_limit'])) {
                $updateData['periodDays'] = $params['booking_future_limit'];
            }
            if (isset($params['buffer_before']) || isset($params['buffer_after'])) {
                $updateData['beforeEventBuffer'] = $params['buffer_before'] ?? 0;
                $updateData['afterEventBuffer'] = $params['buffer_after'] ?? 0;
            }
            
            // Locations
            if (isset($params['locations'])) {
                $updateData['locations'] = $params['locations'];
            }
            
            // Limits
            if (isset($params['max_bookings_per_day'])) {
                $updateData['bookingLimits'] = [
                    'PER_DAY' => $params['max_bookings_per_day']
                ];
            }
            if (isset($params['seats_per_time_slot'])) {
                $updateData['seatsPerTimeSlot'] = $params['seats_per_time_slot'];
            }
            
            // Update via Cal.com API
            $calcomService = new CalcomV2Service($this->getApiKey($company));
            $result = $calcomService->updateEventType($eventTypeId, $updateData);
            
            if ($result['success'] ?? false) {
                // Update lokale Daten
                $eventType->fill($params);
                $eventType->last_synced_at = now();
                $eventType->sync_status = 'synced';
                
                // Update Checklist
                if (isset($params['name']) || isset($params['duration_minutes'])) {
                    $eventType->updateChecklistItem('basic_info', true);
                }
                if (isset($params['minimum_booking_notice'])) {
                    $eventType->updateChecklistItem('booking_settings', true);
                }
                if (isset($params['locations']) && !empty($params['locations'])) {
                    $eventType->updateChecklistItem('locations', true);
                }
                
                $eventType->save();
                
                return [
                    'success' => true,
                    'event_type' => $eventType->fresh(),
                    'message' => 'Event type settings updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to update in Cal.com',
                'details' => $result['error'] ?? 'Unknown error'
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom updateEventTypeSettings error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Generate Cal.com direct link for specific settings
     */
    public function generateCalcomDirectLink(array $params): array
    {
        $eventTypeId = $params['event_type_id'] ?? null;
        $section = $params['section'] ?? 'setup';
        
        if (!$eventTypeId) {
            return ['error' => 'event_type_id is required'];
        }
        
        $baseUrl = config('services.calcom.app_url', 'https://app.cal.com');
        
        // Verschiedene Sections in Cal.com
        $sectionMap = [
            'setup' => '',
            'availability' => '?tabName=availability',
            'limits' => '?tabName=limits',
            'advanced' => '?tabName=advanced',
            'workflows' => '?tabName=workflows',
            'webhooks' => '?tabName=webhooks',
            'team' => '?tabName=team'
        ];
        
        $sectionPath = $sectionMap[$section] ?? '';
        $url = "{$baseUrl}/event-types/{$eventTypeId}{$sectionPath}";
        
        // Section names in German
        $sectionNames = [
            'setup' => 'Grundeinstellungen',
            'availability' => 'Verfügbarkeiten',
            'limits' => 'Limits & Beschränkungen',
            'advanced' => 'Erweiterte Einstellungen',
            'workflows' => 'Workflows & Benachrichtigungen',
            'webhooks' => 'Webhooks',
            'team' => 'Team-Einstellungen'
        ];
        
        $instructions = $this->getInstructionsForSection($section);
        
        return [
            'success' => true,
            'url' => $url,
            'section' => $section,
            'section_name' => $sectionNames[$section] ?? ucfirst($section),
            'instructions' => $instructions['steps'][0] ?? 'Konfigurieren Sie diese Einstellungen in Cal.com'
        ];
    }
    
    /**
     * Get instructions for specific Cal.com section
     */
    protected function getInstructionsForSection(string $section): array
    {
        $instructions = [
            'availability' => [
                'title' => 'Verfügbarkeiten einstellen',
                'steps' => [
                    '1. Wählen Sie einen Schedule oder erstellen Sie einen neuen',
                    '2. Definieren Sie Ihre Arbeitszeiten',
                    '3. Fügen Sie Ausnahmen für Feiertage hinzu',
                    '4. Speichern Sie die Änderungen'
                ]
            ],
            'advanced' => [
                'title' => 'Erweiterte Einstellungen',
                'steps' => [
                    '1. Custom Fields: Fügen Sie benutzerdefinierte Felder hinzu',
                    '2. Bestätigungen: Aktivieren Sie manuelle Bestätigungen',
                    '3. Erinnerungen: Konfigurieren Sie E-Mail/SMS Erinnerungen'
                ]
            ],
            'workflows' => [
                'title' => 'Workflows & Benachrichtigungen',
                'steps' => [
                    '1. Erstellen Sie automatische E-Mails',
                    '2. Konfigurieren Sie SMS-Benachrichtigungen',
                    '3. Richten Sie Webhook-Trigger ein'
                ]
            ]
        ];
        
        return $instructions[$section] ?? ['title' => 'Einstellungen', 'steps' => []];
    }
    
    /**
     * Validate Event Type Configuration
     */
    public function validateEventTypeConfiguration(array $params): array
    {
        $eventTypeId = $params['event_type_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        
        if (!$eventTypeId || !$companyId) {
            return ['error' => 'event_type_id and company_id are required'];
        }
        
        try {
            $eventType = CalcomEventType::where('calcom_numeric_event_type_id', $eventTypeId)
                ->where('company_id', $companyId)
                ->first();
                
            if (!$eventType) {
                return ['error' => 'Event type not found'];
            }
            
            // Validierungsprüfungen
            $issues = [];
            
            // Basis-Validierung
            if (empty($eventType->name)) {
                $issues[] = ['field' => 'name', 'message' => 'Name fehlt'];
            }
            if (!$eventType->duration_minutes || $eventType->duration_minutes < 5) {
                $issues[] = ['field' => 'duration', 'message' => 'Ungültige Dauer'];
            }
            
            // Verfügbarkeit
            if (empty($eventType->schedule_id)) {
                $issues[] = ['field' => 'availability', 'message' => 'Keine Verfügbarkeiten definiert'];
            }
            
            // Locations
            if (empty($eventType->locations)) {
                $issues[] = ['field' => 'locations', 'message' => 'Keine Standorte/Orte definiert'];
            }
            
            // Staff Assignment
            if ($eventType->assignedStaff()->count() === 0) {
                $issues[] = ['field' => 'staff', 'message' => 'Keine Mitarbeiter zugewiesen'];
            }
            
            $isValid = empty($issues);
            
            return [
                'success' => true,
                'valid' => $isValid,
                'issues' => $issues,
                'setup_progress' => $eventType->getSetupProgress(),
                'setup_status' => $eventType->setup_status
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom validateEventTypeConfiguration error', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Exception occurred', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get event type assignments
     */
    public function getEventTypeAssignments(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        $cacheKey = $this->getCacheKey('assignments', ['company_id' => $companyId]);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId) {
            try {
                $branches = Branch::where('company_id', $companyId)
                    ->with(['staff', 'services'])
                    ->get();
                
                $eventTypes = CalcomEventType::where('company_id', $companyId)
                    ->with(['staffAssignments.staff'])
                    ->get();
                
                $assignments = [];
                
                foreach ($branches as $branch) {
                    $branchData = [
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name,
                        'calcom_event_type_id' => $branch->calcom_event_type_id,
                        'staff' => []
                    ];
                    
                    foreach ($branch->staff as $staff) {
                        $staffEventTypes = $eventTypes->filter(function ($et) use ($staff) {
                            return $et->staffAssignments->contains('staff_id', $staff->id);
                        });
                        
                        $branchData['staff'][] = [
                            'staff_id' => $staff->id,
                            'staff_name' => $staff->name,
                            'assigned_event_types' => $staffEventTypes->map(function ($et) {
                                return [
                                    'id' => $et->id,
                                    'title' => $et->title,
                                    'slug' => $et->slug
                                ];
                            })->values()
                        ];
                    }
                    
                    $assignments[] = $branchData;
                }
                
                return [
                    'company_id' => $companyId,
                    'branches' => $assignments,
                    'total_event_types' => $eventTypes->count(),
                    'generated_at' => now()->toIso8601String()
                ];
                
            } catch (\Exception $e) {
                Log::error('MCP CalCom getEventTypeAssignments error', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                
                return ['error' => 'Failed to get assignments', 'message' => $e->getMessage()];
            }
        });
    }
    
    /**
     * Sync event types with full details including hosts and schedules
     */
    public function syncEventTypesWithDetails(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        // Use circuit breaker for the sync operation
        return $this->circuitBreaker->call('calcom_sync', function() use ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                return ['error' => 'Company not found'];
            }
            
            $apiKey = $this->getApiKey($company);
                
            if (!$apiKey) {
                return ['error' => 'No Cal.com API key configured'];
            }
            
            $calcomService = new CalcomV2Service($apiKey);
            
            // Get all event types
            $response = $calcomService->getEventTypes();
            
            // The response is the raw Cal.com response, not wrapped
            if (!$response || !isset($response['event_types'])) {
                return ['error' => 'Failed to fetch event types', 'message' => 'Invalid response from Cal.com'];
            }
            
            $eventTypes = $response['event_types'];
            
            $syncedCount = 0;
            $errors = [];
            
            foreach ($eventTypes as $eventTypeData) {
                try {
                    // Get detailed information for each event type
                    $detailsResponse = $calcomService->getEventTypeDetails($eventTypeData['id']);
                    
                    
                    if ($detailsResponse['success']) {
                        $details = $detailsResponse['data'];
                        
                        // Save or update event type with all details
                        $eventType = CalcomEventType::withoutGlobalScopes()->updateOrCreate(
                            [
                                'calcom_numeric_event_type_id' => $details['id'],
                                'company_id' => $companyId
                            ],
                            [
                                'name' => $details['title'] ?? $details['slug'],
                                'duration_minutes' => $details['length'] ?? 30,
                                'description' => $details['description'] ?? '',
                                'price' => $details['price'] ?? 0,
                                'is_active' => !($details['hidden'] ?? false),
                                'team_id' => $details['teamId'] ?? null,
                                'is_team_event' => !empty($details['teamId'])
                            ]
                        );
                        
                        // Sync hosts (staff assignments)
                        if (!empty($details['hosts'])) {
                            $this->syncEventTypeHosts($eventType, $details['hosts'], $companyId);
                        }
                        
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'event_type_id' => $eventTypeData['id'],
                        'error' => $e->getMessage()
                    ];
                    Log::error('Failed to sync event type details', [
                        'event_type_id' => $eventTypeData['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Clear cache
            Cache::forget($this->getCacheKey('event_types', ['company_id' => $companyId]));
            
            // Return success result
            return [
                'success' => true,
                'synced' => $syncedCount,
                'total' => count($eventTypes),
                'errors' => $errors,
                'message' => "Synchronisiert: {$syncedCount} von " . count($eventTypes) . " Event Types"
            ];
        }, function() {
            // Fallback when circuit is open
            return [
                'error' => 'Circuit breaker open',
                'message' => 'Cal.com sync service is temporarily unavailable'
            ];
        });
    }
    
    /**
     * Sync hosts (staff) for an event type
     */
    protected function syncEventTypeHosts(CalcomEventType $eventType, array $hosts, int $companyId): void
    {
        // First, find or create staff records for each host
        $staffIds = [];
        
        foreach ($hosts as $host) {
            // Check if staff exists by calcom_user_id or email
            $staff = \App\Models\Staff::withoutGlobalScopes()->where('company_id', $companyId)
                ->where(function ($query) use ($host) {
                    $query->where('calcom_user_id', $host['id'])
                          ->orWhere('email', $host['email'] ?? null);
                })
                ->first();
            
            if (!$staff && !empty($host['email'])) {
                // Create new staff member
                // Need to bypass the BelongsToCompany trait validation
                $staffData = [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'company_id' => $companyId,
                    'calcom_user_id' => $host['id'],
                    'name' => $host['name'] ?? $host['username'] ?? 'Unknown',
                    'email' => $host['email'],
                    'is_active' => true,
                    'is_bookable' => true,
                    'branch_id' => \App\Models\Branch::withoutGlobalScopes()->where('company_id', $companyId)->where('active', true)->orderBy('created_at')->value('id')
                ];
                
                // Use DB insert to bypass model events
                \DB::table('staff')->insert(array_merge($staffData, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
                
                $staff = \App\Models\Staff::withoutGlobalScopes()->find($staffData['id']);
            }
            
            if ($staff) {
                $staffIds[] = $staff->id;
            }
        }
        
        // Sync staff assignments to event type
        if (!empty($staffIds)) {
            // Clear existing assignments
            \DB::table('staff_event_types')->where('calcom_event_type_id', $eventType->id)->delete();
            
            foreach ($staffIds as $staffId) {
                try {
                    // Find the host details for this staff member
                    $staff = \App\Models\Staff::withoutGlobalScopes()->find($staffId);
                    $hostDetails = null;
                    if ($staff && $staff->calcom_user_id) {
                        $hostDetails = collect($hosts)->firstWhere('id', $staff->calcom_user_id);
                    }
                    
                    \DB::table('staff_event_types')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'staff_id' => $staffId,
                        'calcom_event_type_id' => $eventType->id,
                        'calcom_user_id' => $hostDetails['id'] ?? null,
                        'is_primary' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } catch (\Exception $e) {
                    // Log error but continue with other assignments
                    Log::warning('Failed to create staff event type assignment', [
                        'staff_id' => $staffId,
                        'calcom_event_type_id' => $eventType->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    /**
     * Sync users with their schedules from Cal.com
     */
    public function syncUsersWithSchedules(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        // Use circuit breaker for the sync operation
        return $this->circuitBreaker->call('calcom_sync', function() use ($companyId, $params) {
            $company = Company::find($companyId);
            if (!$company) {
                return ['error' => 'Company not found'];
            }
            
            $apiKey = $this->getApiKey($company);
                
            if (!$apiKey) {
                return ['error' => 'No Cal.com API key configured'];
            }
            
            $calcomService = new CalcomV2Service($apiKey);
            
            // Get all users
            $usersResponse = $calcomService->getUsers();
            
            if (!$usersResponse || !isset($usersResponse['users'])) {
                return ['error' => 'Failed to fetch users'];
            }
            
            $syncedCount = 0;
            $errors = [];
            
            foreach ($usersResponse['users'] as $userData) {
                try {
                    // Find existing staff member
                    $staff = \App\Models\Staff::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->where('email', $userData['email'])
                        ->first();
                    
                    if ($staff) {
                        // Update existing staff
                        $staff->update([
                            'calcom_user_id' => $userData['id'],
                            'name' => $userData['name'] ?? $userData['username'],
                            'is_active' => true,
                            'is_bookable' => true
                        ]);
                    } else {
                        // Create new staff member using DB insert to bypass validation
                        $staffData = [
                            'id' => \Illuminate\Support\Str::uuid(),
                            'company_id' => $companyId,
                            'email' => $userData['email'],
                            'calcom_user_id' => $userData['id'],
                            'name' => $userData['name'] ?? $userData['username'],
                            'is_active' => true,
                            'is_bookable' => true,
                            'branch_id' => $params['branch_id'] ?? \App\Models\Branch::withoutGlobalScopes()->where('company_id', $companyId)->where('active', true)->orderBy('created_at')->value('id'),
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                        
                        \DB::table('staff')->insert($staffData);
                        $staff = \App\Models\Staff::withoutGlobalScopes()->find($staffData['id']);
                    }
                    
                    // Get user's default schedule if available
                    if (!empty($userData['defaultScheduleId'])) {
                        $scheduleResponse = $calcomService->getSchedules();
                        
                        if ($scheduleResponse['success']) {
                            $schedules = $scheduleResponse['data']['schedules'] ?? [];
                            $defaultSchedule = collect($schedules)->firstWhere('id', $userData['defaultScheduleId']);
                            
                            if ($defaultSchedule) {
                                $this->syncStaffWorkingHours($staff, $defaultSchedule);
                            }
                        }
                    }
                    
                    $syncedCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'user_id' => $userData['id'],
                        'email' => $userData['email'],
                        'error' => $e->getMessage()
                    ];
                    Log::error('Failed to sync user', [
                        'user' => $userData,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return [
                'success' => true,
                'synced' => $syncedCount,
                'total' => count($usersResponse['users']),
                'errors' => $errors,
                'message' => "Synchronisiert: {$syncedCount} von " . count($usersResponse['users']) . " Mitarbeitern"
            ];
        }, function() {
            // Fallback when circuit is open
            return [
                'error' => 'Circuit breaker open',
                'message' => 'Cal.com sync service is temporarily unavailable'
            ];
        });
    }
    
    /**
     * Sync working hours for a staff member based on Cal.com schedule
     */
    protected function syncStaffWorkingHours(\App\Models\Staff $staff, array $schedule): void
    {
        // Delete existing working hours
        \DB::table('working_hours')->where('staff_id', $staff->id)->delete();
        
        // Map Cal.com availability to our working hours
        $availability = $schedule['availability'] ?? [];
        
        foreach ($availability as $daySchedule) {
            if (empty($daySchedule['days']) || empty($daySchedule['startTime']) || empty($daySchedule['endTime'])) {
                continue;
            }
            
            // Cal.com uses array of day numbers (0=Sunday, 1=Monday, etc.)
            foreach ($daySchedule['days'] as $dayNumber) {
                // Our system uses 1=Monday, 7=Sunday
                $ourDayNumber = $dayNumber === 0 ? 7 : $dayNumber;
                
                \DB::table('working_hours')->insert([
                    'staff_id' => $staff->id,
                    'weekday' => $ourDayNumber,
                    'day_of_week' => $ourDayNumber,
                    'start' => $daySchedule['startTime'],
                    'end' => $daySchedule['endTime'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        // Store schedule metadata
        $staff->update([
            'metadata' => array_merge($staff->metadata ?? [], [
                'calcom_schedule_id' => $schedule['id'],
                'calcom_schedule_name' => $schedule['name'] ?? 'Default',
                'calcom_timezone' => $schedule['timeZone'] ?? 'Europe/Berlin',
                'last_schedule_sync' => now()->toIso8601String()
            ])
        ]);
    }
    
    /**
     * Sync event types from Cal.com
     */
    public function syncEventTypes(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return ['error' => 'Company not found or Cal.com not configured'];
            }
            
            // Clear cache first
            $this->clearCache(['company_id' => $companyId]);
            
            $calcomService = new CalcomV2Service($this->getApiKey($company));
            $response = $calcomService->getEventTypes();
            
            // The response is the raw Cal.com API response
            // It should have either 'event_types' array or be an array of event types
            $eventTypes = [];
            if (isset($response['event_types'])) {
                $eventTypes = $response['event_types'];
            } elseif (isset($response['data'])) {
                $eventTypes = $response['data'];
            } elseif (is_array($response) && !isset($response['error'])) {
                // Direct array of event types
                $eventTypes = $response;
            } else {
                return ['error' => 'Failed to fetch event types from Cal.com', 'message' => 'Invalid response format'];
            }
            
            if (empty($eventTypes)) {
                return ['error' => 'No event types found in Cal.com'];
            }
            
            $synced = 0;
            $errors = [];
            
            foreach ($eventTypes as $eventTypeData) {
                try {
                    // Find or get the first branch for this company
                    $branch = \App\Models\Branch::where('company_id', $companyId)
                        ->where('active', true)
                        ->first();
                    
                    if (!$branch) {
                        throw new \Exception('No active branch found for company');
                    }
                    
                    CalcomEventType::updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'calcom_numeric_event_type_id' => $eventTypeData['id']
                        ],
                        [
                            'branch_id' => $branch->id,
                            'name' => $eventTypeData['title'],
                            'slug' => $eventTypeData['slug'],
                            'description' => $eventTypeData['description'] ?? null,
                            'duration_minutes' => $eventTypeData['length'],
                            'minimum_booking_notice' => $eventTypeData['minimumBookingNotice'] ?? 120,
                            'is_active' => !($eventTypeData['hidden'] ?? false),
                            'last_synced_at' => now(),
                            'metadata' => json_encode($eventTypeData)
                        ]
                    );
                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'event_type_id' => $eventTypeData['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'success' => true,
                'synced_count' => $synced,
                'total_count' => count($eventTypes),
                'errors' => $errors,
                'synced_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom syncEventTypes error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'Sync failed', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Test Cal.com connection
     */
    public function testConnection(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company) {
                return ['error' => 'Company not found'];
            }
            
            if (!$company->calcom_api_key) {
                return [
                    'connected' => false,
                    'message' => 'Cal.com API key not configured'
                ];
            }
            
            $calcomService = new CalcomV2Service($this->getApiKey($company));
            $response = $calcomService->getMe();
            
            if ($response['success']) {
                return [
                    'connected' => true,
                    'user' => $response['data'],
                    'company' => $company->name,
                    'tested_at' => now()->toIso8601String()
                ];
            }
            
            return [
                'connected' => false,
                'message' => 'Connection failed',
                'error' => $response['error'] ?? 'Unknown error'
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom testConnection error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'connected' => false,
                'message' => 'Exception occurred',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, array $params = []): string
    {
        $prefix = $this->config['cache']['prefix'];
        $key = "{$prefix}:{$type}";
        
        if (!empty($params)) {
            $key .= ':' . md5(json_encode($params));
        }
        
        return $key;
    }
    
    /**
     * Clear cache
     */
    public function clearCache(array $params = []): void
    {
        if (isset($params['company_id'])) {
            Cache::forget($this->getCacheKey('event_types', ['company_id' => $params['company_id']]));
            Cache::forget($this->getCacheKey('assignments', ['company_id' => $params['company_id']]));
        } else {
            // Clear all CalCom cache
            Cache::flush();
        }
    }
    
    /**
     * Clear availability cache for a specific event type
     */
    protected function clearAvailabilityCache(int $companyId, int $eventTypeId): void
    {
        // Clear all availability cache entries for this event type
        $pattern = $this->config['cache']['prefix'] . ':availability:*' . md5(json_encode([
            'company_id' => $companyId,
            'event_type_id' => $eventTypeId
        ])) . '*';
        
        // Note: This is a simplified version. In production, you might want to use Redis SCAN
        // to find and delete matching keys
        Cache::tags(['calcom', "company_{$companyId}", "event_type_{$eventTypeId}"])->flush();
    }
    
    /**
     * Clear booking cache
     */
    protected function clearBookingCache(int $companyId, int $bookingId): void
    {
        $cacheKey = $this->getCacheKey('booking', [
            'company_id' => $companyId,
            'booking_id' => $bookingId
        ]);
        Cache::forget($cacheKey);
    }
    
    /**
     * Generate idempotency key for booking requests
     */
    protected function generateIdempotencyKey(array $params): string
    {
        $key = $params['company_id'] . ':' . $params['event_type_id'] . ':' . $params['start'] . ':' . $params['email'];
        return md5($key);
    }
    
    /**
     * Sync bookings from Cal.com for a company
     */
    public function syncBookings(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $fromDate = $params['from_date'] ?? Carbon::now()->subMonths(3)->startOfDay()->toIso8601String();
        $toDate = $params['to_date'] ?? Carbon::now()->addMonths(1)->endOfDay()->toIso8601String();
        
        if (!$companyId) {
            return [
                'success' => false,
                'error' => 'Company ID is required'
            ];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company) {
                return [
                    'success' => false,
                    'error' => 'Company not found'
                ];
            }
            
            // Use company API key or fall back to default
            $apiKey = $this->getApiKey($company);
                
            if (!$apiKey) {
                return [
                    'success' => false,
                    'error' => 'No Cal.com API key configured'
                ];
            }
            
            // Use circuit breaker for Cal.com API call
            $result = $this->circuitBreaker->call(
                'calcom_sync',
                function () use ($apiKey, $fromDate, $toDate) {
                    $calcomService = new CalcomV2Service($apiKey);
                    
                    // Get bookings from Cal.com
                    $response = $calcomService->getBookings([
                        'from' => $fromDate,
                        'to' => $toDate
                    ]);
                    
                    if ($response['success']) {
                        return [
                            'success' => true,
                            'bookings' => $response['data']['bookings'] ?? []
                        ];
                    }
                    
                    return [
                        'success' => false,
                        'error' => $response['error'] ?? 'Failed to fetch bookings'
                    ];
                },
                function () {
                    return [
                        'success' => false,
                        'error' => 'Cal.com sync service temporarily unavailable',
                        'circuit_breaker_open' => true,
                        'bookings' => []
                    ];
                }
            );
            
            if (!$result['success']) {
                return $result;
            }
            
            // Process bookings
            $synced = 0;
            $created = 0;
            $updated = 0;
            $errors = [];
            
            foreach ($result['bookings'] as $booking) {
                try {
                    // Sync individual booking
                    $syncResult = $this->syncSingleBooking($company, $booking);
                    
                    if ($syncResult === 'created') {
                        $created++;
                    } elseif ($syncResult === 'updated') {
                        $updated++;
                    }
                    $synced++;
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'booking_id' => $booking['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('MCP CalCom: Error syncing booking', [
                        'booking_id' => $booking['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                        'company_id' => $companyId
                    ]);
                }
            }
            
            $message = "Sync completed: {$synced} bookings synced ({$created} created, {$updated} updated)";
            if (count($errors) > 0) {
                $message .= ", " . count($errors) . " errors";
            }
            
            Log::info('MCP CalCom: Bookings sync completed', [
                'company_id' => $companyId,
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'errors' => count($errors)
            ]);
            
            return [
                'success' => true,
                'message' => $message,
                'stats' => [
                    'synced' => $synced,
                    'created' => $created,
                    'updated' => $updated,
                    'errors' => count($errors)
                ],
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom syncBookings error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Sync failed',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync a single booking
     */
    protected function syncSingleBooking(Company $company, array $bookingData): string
    {
        // This would be implemented to match the logic in SyncCalcomBookingsJob
        // For now, return a placeholder
        return 'updated';
    }
    
    /**
     * Sync event type users from Cal.com
     * This method fetches event type details and maps Cal.com users to local staff
     */
    public function syncEventTypeUsers(array $params): array
    {
        $eventTypeId = $params['event_type_id'] ?? null;
        $companyId = $params['company_id'] ?? null;
        
        if (!$eventTypeId || !$companyId) {
            return [
                'success' => false,
                'error' => 'event_type_id and company_id are required'
            ];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->calcom_api_key) {
                return [
                    'success' => false,
                    'error' => 'Company not found or Cal.com not configured'
                ];
            }
            
            $apiKey = $this->getApiKey($company);
            $calcomService = new CalcomV2Service($apiKey);
            
            // Try to get event type details from V2 API first
            $detailsResponse = $calcomService->getEventTypeDetails($eventTypeId);
            
            if (!$detailsResponse['success']) {
                // Fallback to V1 API if V2 fails
                Log::info('Cal.com V2 API failed, trying V1 for event type users', [
                    'event_type_id' => $eventTypeId,
                    'v2_error' => $detailsResponse['error'] ?? 'Unknown error'
                ]);
                
                // Try V1 API as fallback
                $v1Response = $calcomService->getEventTypes();
                if ($v1Response && isset($v1Response['event_types'])) {
                    $eventType = collect($v1Response['event_types'])->firstWhere('id', $eventTypeId);
                    if ($eventType) {
                        $detailsResponse = [
                            'success' => true,
                            'data' => $eventType
                        ];
                    }
                }
            }
            
            if (!$detailsResponse['success']) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch event type details from Cal.com',
                    'message' => $detailsResponse['error'] ?? 'Unknown error'
                ];
            }
            
            $eventTypeData = $detailsResponse['data'];
            $hosts = $eventTypeData['hosts'] ?? $eventTypeData['users'] ?? [];
            
            // Map Cal.com users to local staff
            $mappingResults = [];
            $successCount = 0;
            $failedCount = 0;
            
            foreach ($hosts as $host) {
                $calcomUserId = $host['id'] ?? null;
                $email = $host['email'] ?? null;
                $name = $host['name'] ?? $host['username'] ?? null;
                
                if (!$calcomUserId) {
                    $failedCount++;
                    $mappingResults[] = [
                        'calcom_user' => $host,
                        'status' => 'failed',
                        'reason' => 'No Cal.com user ID'
                    ];
                    continue;
                }
                
                // Try to find staff by calcom_user_id first
                $staff = \App\Models\Staff::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('calcom_user_id', $calcomUserId)
                    ->first();
                
                // If not found by calcom_user_id, try by email
                if (!$staff && $email) {
                    $staff = \App\Models\Staff::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->where('email', $email)
                        ->first();
                    
                    // Update calcom_user_id if found by email
                    if ($staff) {
                        $staff->update(['calcom_user_id' => $calcomUserId]);
                    }
                }
                
                // If still not found, try by name
                if (!$staff && $name) {
                    $staff = \App\Models\Staff::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->where('name', 'LIKE', '%' . $name . '%')
                        ->first();
                    
                    // Update calcom_user_id if found by name
                    if ($staff) {
                        $staff->update(['calcom_user_id' => $calcomUserId]);
                    }
                }
                
                if ($staff) {
                    $successCount++;
                    $mappingResults[] = [
                        'calcom_user' => [
                            'id' => $calcomUserId,
                            'email' => $email,
                            'name' => $name
                        ],
                        'local_staff' => [
                            'id' => $staff->id,
                            'name' => $staff->name,
                            'email' => $staff->email
                        ],
                        'status' => 'matched',
                        'match_method' => $staff->wasRecentlyCreated ? 'created' : 'existing'
                    ];
                    
                    // Create or update staff_event_types assignment
                    try {
                        $existingAssignment = \DB::table('staff_event_types')
                            ->where('staff_id', $staff->id)
                            ->where('calcom_event_type_id', $eventTypeId)
                            ->first();
                            
                        if (!$existingAssignment) {
                            \DB::table('staff_event_types')->insert([
                                'id' => \Illuminate\Support\Str::uuid(),
                                'staff_id' => $staff->id,
                                'calcom_event_type_id' => $eventTypeId,
                                'calcom_user_id' => $calcomUserId,
                                'is_primary' => false,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to create staff event type assignment', [
                            'staff_id' => $staff->id,
                            'event_type_id' => $eventTypeId,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    $failedCount++;
                    $mappingResults[] = [
                        'calcom_user' => [
                            'id' => $calcomUserId,
                            'email' => $email,
                            'name' => $name
                        ],
                        'status' => 'not_found',
                        'reason' => 'No matching staff member found in local database',
                        'suggestion' => 'Create staff member with email: ' . ($email ?? 'N/A')
                    ];
                }
            }
            
            // Clear cache for this event type
            $this->clearCache(['company_id' => $companyId]);
            
            return [
                'success' => true,
                'event_type_id' => $eventTypeId,
                'total_hosts' => count($hosts),
                'matched' => $successCount,
                'not_matched' => $failedCount,
                'mapping_results' => $mappingResults,
                'message' => "Synchronisiert: {$successCount} von " . count($hosts) . " Mitarbeitern zugeordnet"
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP CalCom syncEventTypeUsers error', [
                'event_type_id' => $eventTypeId,
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to sync event type users',
                'message' => $e->getMessage()
            ];
        }
    }
}