<?php

namespace App\Services\MCP;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Call;
use App\Services\CalcomV2Service;
use App\Services\CircuitBreaker\CircuitBreakerService;
use App\Services\Booking\AlternativeSlotFinder;
use App\Services\AppointmentService;
use App\Services\CustomerService;
use App\Services\Webhooks\RetellWebhookHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MCPBookingOrchestrator
{
    protected MCPContextResolver $contextResolver;
    protected CalcomV2Service $calcomService;
    protected CircuitBreakerService $circuitBreaker;
    protected AlternativeSlotFinder $slotFinder;
    protected AppointmentService $appointmentService;
    protected CustomerService $customerService;
    protected RetellWebhookHandler $webhookHandler;
    
    protected array $config;
    
    public function __construct(
        MCPContextResolver $contextResolver,
        CalcomV2Service $calcomService,
        CircuitBreakerService $circuitBreaker,
        AlternativeSlotFinder $slotFinder,
        AppointmentService $appointmentService,
        CustomerService $customerService,
        RetellWebhookHandler $webhookHandler
    ) {
        $this->contextResolver = $contextResolver;
        $this->calcomService = $calcomService;
        $this->circuitBreaker = $circuitBreaker;
        $this->slotFinder = $slotFinder;
        $this->appointmentService = $appointmentService;
        $this->customerService = $customerService;
        $this->webhookHandler = $webhookHandler;
        
        $this->config = [
            'circuit_breaker' => [
                'failure_threshold' => 5,
                'timeout' => 60, // seconds
                'success_threshold' => 2
            ],
            'booking' => [
                'lock_duration' => 300, // 5 minutes
                'retry_attempts' => 3,
                'alternative_slots' => 5
            ],
            'cache' => [
                'ttl' => 300,
                'prefix' => 'mcp:booking'
            ]
        ];
    }
    
    /**
     * Handle incoming webhook from Retell
     */
    public function handleWebhook(array $payload): array
    {
        $correlationId = $payload['correlation_id'] ?? uniqid('mcp-webhook-');
        
        Log::info('MCP: Processing webhook', [
            'correlation_id' => $correlationId,
            'event_type' => $payload['event_type'] ?? 'unknown',
            'call_id' => $payload['call_id'] ?? null
        ]);
        
        try {
            // Validate webhook structure
            if (!$this->validateWebhookPayload($payload)) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook payload',
                    'correlation_id' => $correlationId
                ];
            }
            
            // Handle different event types
            switch ($payload['event_type']) {
                case 'call_ended':
                    return $this->handleCallEnded($payload, $correlationId);
                    
                case 'call_analyzed':
                    return $this->handleCallAnalyzed($payload, $correlationId);
                    
                case 'booking_requested':
                    return $this->handleBookingRequest($payload, $correlationId);
                    
                default:
                    // Let the standard webhook handler process other events
                    return $this->webhookHandler->handle($payload);
            }
            
        } catch (\Exception $e) {
            Log::error('MCP: Webhook processing failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId
            ];
        }
    }
    
    /**
     * Handle call ended event with booking logic
     */
    protected function handleCallEnded(array $payload, string $correlationId): array
    {
        DB::beginTransaction();
        
        try {
            // Extract call data
            $callData = $payload['call'] ?? [];
            $phoneNumber = $callData['from_number'] ?? null;
            
            if (!$phoneNumber) {
                throw new \Exception('No phone number in call data');
            }
            
            // Resolve context from phone number
            $context = $this->contextResolver->resolveFromPhone($phoneNumber);
            
            if (!$context['success']) {
                throw new \Exception($context['error'] ?? 'Failed to resolve context');
            }
            
            // Set tenant context
            $this->contextResolver->setTenantContext($context['company']['id']);
            
            // Create or update call record
            $call = $this->createCallRecord($callData, $context, $correlationId);
            
            // Check if booking was requested during the call
            $bookingData = $this->extractBookingData($callData);
            
            if ($bookingData) {
                // Process the booking
                $bookingResult = $this->processBooking($bookingData, $context, $call, $correlationId);
                
                if (!$bookingResult['success']) {
                    // Try to find alternatives if booking failed
                    $alternatives = $this->findAlternatives($bookingData, $context, $correlationId);
                    
                    if (!empty($alternatives)) {
                        $bookingResult['alternatives'] = $alternatives;
                    }
                }
                
                DB::commit();
                
                return $bookingResult;
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'call_id' => $call->id,
                'message' => 'Call processed without booking request',
                'correlation_id' => $correlationId
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('MCP: Call ended processing failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Handle call analyzed event
     */
    protected function handleCallAnalyzed(array $payload, string $correlationId): array
    {
        // Update call record with analysis data
        $callId = $payload['call_id'] ?? null;
        
        if (!$callId) {
            return [
                'success' => false,
                'error' => 'No call ID in payload',
                'correlation_id' => $correlationId
            ];
        }
        
        $call = Call::where('retell_call_id', $callId)->first();
        
        if (!$call) {
            Log::warning('MCP: Call not found for analysis', [
                'call_id' => $callId,
                'correlation_id' => $correlationId
            ]);
            
            return [
                'success' => false,
                'error' => 'Call not found',
                'correlation_id' => $correlationId
            ];
        }
        
        // Update with analysis data
        $analysisData = $payload['analysis'] ?? [];
        
        $call->update([
            'transcript' => $analysisData['transcript'] ?? null,
            'summary' => $analysisData['summary'] ?? null,
            'sentiment' => $analysisData['sentiment'] ?? null,
            'tags' => $analysisData['tags'] ?? [],
            'analyzed_at' => now()
        ]);
        
        return [
            'success' => true,
            'call_id' => $call->id,
            'message' => 'Call analysis updated',
            'correlation_id' => $correlationId
        ];
    }
    
    /**
     * Handle direct booking request
     */
    protected function handleBookingRequest(array $payload, string $correlationId): array
    {
        $phoneNumber = $payload['phone_number'] ?? null;
        $bookingData = $payload['booking_data'] ?? [];
        
        if (!$phoneNumber) {
            return [
                'success' => false,
                'error' => 'No phone number provided',
                'correlation_id' => $correlationId
            ];
        }
        
        // Resolve context
        $context = $this->contextResolver->resolveFromPhone($phoneNumber);
        
        if (!$context['success']) {
            return [
                'success' => false,
                'error' => $context['error'] ?? 'Failed to resolve context',
                'correlation_id' => $correlationId
            ];
        }
        
        // Process booking
        return $this->processBooking($bookingData, $context, null, $correlationId);
    }
    
    /**
     * Check availability with circuit breaker
     */
    public function checkAvailability(array $params): array
    {
        $cacheKey = $this->getCacheKey('availability', $params);
        
        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Use circuit breaker for Cal.com API call
        $result = $this->circuitBreaker->call(
            'calcom_availability',
            function () use ($params) {
                return $this->calcomService->getAvailability(
                    $params['event_type_id'],
                    $params['date_from'],
                    $params['date_to'],
                    $params['timezone'] ?? 'Europe/Berlin'
                );
            },
            $this->config['circuit_breaker']
        );
        
        // Cache successful results
        if ($result['success'] ?? false) {
            Cache::put($cacheKey, $result, $this->config['cache']['ttl']);
        }
        
        return $result;
    }
    
    /**
     * Find alternative slots
     */
    public function findAlternatives(array $bookingData, array $context, string $correlationId): array
    {
        try {
            $requestedDate = Carbon::parse($bookingData['date']);
            $duration = $bookingData['duration'] ?? 30;
            $serviceId = $bookingData['service_id'] ?? null;
            
            // Use AlternativeSlotFinder
            $alternatives = $this->slotFinder->findAlternatives([
                'branch_id' => $context['branch']['id'],
                'service_id' => $serviceId,
                'requested_date' => $requestedDate,
                'duration' => $duration,
                'limit' => $this->config['booking']['alternative_slots']
            ]);
            
            Log::info('MCP: Found alternative slots', [
                'correlation_id' => $correlationId,
                'count' => count($alternatives),
                'requested_date' => $requestedDate->toDateTimeString()
            ]);
            
            return $alternatives;
            
        } catch (\Exception $e) {
            Log::error('MCP: Failed to find alternatives', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Process booking with all necessary steps
     */
    protected function processBooking(array $bookingData, array $context, ?Call $call, string $correlationId): array
    {
        DB::beginTransaction();
        
        try {
            // 1. Create or find customer
            $customer = $this->findOrCreateCustomer($bookingData, $context);
            
            // 2. Lock the time slot
            $lockKey = $this->lockTimeSlot($bookingData, $context);
            
            try {
                // 3. Check availability one more time
                $availabilityCheck = $this->checkAvailability([
                    'event_type_id' => $context['branch']['calcom_event_type_id'],
                    'date_from' => $bookingData['date'],
                    'date_to' => $bookingData['date'],
                    'timezone' => $context['branch']['timezone']
                ]);
                
                if (!$availabilityCheck['success'] || empty($availabilityCheck['slots'])) {
                    throw new \Exception('Time slot no longer available');
                }
                
                // 4. Create appointment in database
                $appointment = $this->appointmentService->create([
                    'company_id' => $context['company']['id'],
                    'branch_id' => $context['branch']['id'],
                    'customer_id' => $customer->id,
                    'service_id' => $bookingData['service_id'] ?? null,
                    'staff_id' => $bookingData['staff_id'] ?? null,
                    'appointment_date' => $bookingData['date'],
                    'duration' => $bookingData['duration'] ?? 30,
                    'status' => 'scheduled',
                    'call_id' => $call?->id,
                    'notes' => $bookingData['notes'] ?? null,
                    'source' => 'phone_ai',
                    'correlation_id' => $correlationId
                ]);
                
                // 5. Book in Cal.com
                $calcomBooking = $this->circuitBreaker->call(
                    'calcom_booking',
                    function () use ($appointment, $context, $customer) {
                        return $this->calcomService->createBooking([
                            'eventTypeId' => $context['branch']['calcom_event_type_id'],
                            'start' => $appointment->appointment_date->toIso8601String(),
                            'name' => $customer->name,
                            'email' => $customer->email,
                            'phone' => $customer->phone,
                            'notes' => $appointment->notes,
                            'metadata' => [
                                'appointment_id' => $appointment->id,
                                'branch_id' => $context['branch']['id'],
                                'source' => 'phone_ai'
                            ]
                        ]);
                    },
                    $this->config['circuit_breaker']
                );
                
                if ($calcomBooking['success'] ?? false) {
                    // Update appointment with Cal.com booking ID
                    $appointment->update([
                        'calcom_booking_id' => $calcomBooking['booking']['id'] ?? null,
                        'calcom_booking_uid' => $calcomBooking['booking']['uid'] ?? null
                    ]);
                }
                
                DB::commit();
                
                // Release lock
                $this->releaseTimeSlot($lockKey);
                
                Log::info('MCP: Booking created successfully', [
                    'correlation_id' => $correlationId,
                    'appointment_id' => $appointment->id,
                    'calcom_booking_id' => $appointment->calcom_booking_id
                ]);
                
                return [
                    'success' => true,
                    'appointment' => [
                        'id' => $appointment->id,
                        'date' => $appointment->appointment_date->toDateTimeString(),
                        'duration' => $appointment->duration,
                        'status' => $appointment->status,
                        'calcom_booking_id' => $appointment->calcom_booking_id
                    ],
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone
                    ],
                    'correlation_id' => $correlationId
                ];
                
            } finally {
                // Always release lock
                $this->releaseTimeSlot($lockKey);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('MCP: Booking failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'booking_data' => $bookingData
            ]);
            
            return [
                'success' => false,
                'error' => 'Booking failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId
            ];
        }
    }
    
    /**
     * Create call record
     */
    protected function createCallRecord(array $callData, array $context, string $correlationId): Call
    {
        return Call::create([
            'company_id' => $context['company']['id'],
            'branch_id' => $context['branch']['id'],
            'retell_call_id' => $callData['call_id'] ?? null,
            'phone_number' => $callData['from_number'],
            'direction' => $callData['direction'] ?? 'inbound',
            'status' => $callData['status'] ?? 'completed',
            'duration_seconds' => $callData['duration'] ?? 0,
            'started_at' => isset($callData['start_timestamp']) ? Carbon::parse($callData['start_timestamp']) : now(),
            'ended_at' => isset($callData['end_timestamp']) ? Carbon::parse($callData['end_timestamp']) : now(),
            'recording_url' => $callData['recording_url'] ?? null,
            'transcript' => $callData['transcript'] ?? null,
            'metadata' => $callData['metadata'] ?? [],
            'correlation_id' => $correlationId
        ]);
    }
    
    /**
     * Extract booking data from call data
     */
    protected function extractBookingData(array $callData): ?array
    {
        // Check if booking was requested in call metadata
        $metadata = $callData['metadata'] ?? [];
        
        if (!($metadata['booking_requested'] ?? false)) {
            return null;
        }
        
        return [
            'date' => $metadata['requested_date'] ?? null,
            'time' => $metadata['requested_time'] ?? null,
            'duration' => $metadata['duration'] ?? 30,
            'service_id' => $metadata['service_id'] ?? null,
            'staff_id' => $metadata['staff_id'] ?? null,
            'notes' => $metadata['notes'] ?? null,
            'customer_name' => $metadata['customer_name'] ?? null,
            'customer_phone' => $callData['from_number'] ?? null,
            'customer_email' => $metadata['customer_email'] ?? null
        ];
    }
    
    /**
     * Find or create customer
     */
    protected function findOrCreateCustomer(array $bookingData, array $context): Customer
    {
        $phone = $bookingData['customer_phone'] ?? null;
        $name = $bookingData['customer_name'] ?? 'Unknown Customer';
        $email = $bookingData['customer_email'] ?? null;
        
        if (!$phone) {
            throw new \Exception('Customer phone number required');
        }
        
        // Use CustomerService to handle duplicates
        $result = $this->customerService->findOrCreateByPhone(
            $phone,
            [
                'name' => $name,
                'email' => $email,
                'company_id' => $context['company']['id'],
                'source' => 'phone_ai'
            ]
        );
        
        return $result['customer'];
    }
    
    /**
     * Lock time slot to prevent double booking
     */
    protected function lockTimeSlot(array $bookingData, array $context): string
    {
        $lockKey = sprintf(
            'booking_lock:%s:%s:%s',
            $context['branch']['id'],
            $bookingData['date'],
            $bookingData['time'] ?? '00:00'
        );
        
        // Try to acquire lock
        $acquired = Cache::add($lockKey, [
            'locked_at' => now()->toIso8601String(),
            'booking_data' => $bookingData,
            'branch_id' => $context['branch']['id']
        ], $this->config['booking']['lock_duration']);
        
        if (!$acquired) {
            throw new \Exception('Time slot is currently being booked by another request');
        }
        
        return $lockKey;
    }
    
    /**
     * Release time slot lock
     */
    protected function releaseTimeSlot(string $lockKey): void
    {
        Cache::forget($lockKey);
    }
    
    /**
     * Validate webhook payload
     */
    protected function validateWebhookPayload(array $payload): bool
    {
        // Check required fields based on event type
        $requiredFields = [
            'event_type',
            'timestamp'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                Log::warning('MCP: Missing required webhook field', [
                    'field' => $field,
                    'payload' => $payload
                ]);
                return false;
            }
        }
        
        // Event-specific validation
        switch ($payload['event_type']) {
            case 'call_ended':
            case 'call_analyzed':
                return isset($payload['call']) || isset($payload['call_id']);
                
            case 'booking_requested':
                return isset($payload['phone_number']) && isset($payload['booking_data']);
                
            default:
                return true;
        }
    }
    
    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, array $params): string
    {
        return sprintf(
            '%s:%s:%s',
            $this->config['cache']['prefix'],
            $type,
            md5(json_encode($params))
        );
    }
}