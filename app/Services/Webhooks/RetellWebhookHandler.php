<?php

namespace App\Services\Webhooks;

use App\Models\WebhookEvent;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Services\RetellService;
use App\Services\CalcomV2Service;
use App\Services\AppointmentBookingService;
use App\Services\PhoneNumberResolver;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RetellWebhookHandler extends BaseWebhookHandler
{
    /**
     * Get supported event types
     *
     * @return array
     */
    public function getSupportedEvents(): array
    {
        return [
            'call_started',
            'call_ended',
            'call_analyzed',
            'call_inbound',
            'call_outbound'
        ];
    }
    
    /**
     * Handle call_started event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleCallStarted(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $payload = $webhookEvent->payload;
        $callData = $payload['call'] ?? [];
        
        $this->logInfo('Processing call_started event', [
            'call_id' => $callData['call_id'] ?? null
        ]);
        
        // Create or update call record (disable tenant scope for webhook processing)
        $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->updateOrCreate(
            ['retell_call_id' => $callData['call_id']],
            [
                'company_id' => $this->resolveCompanyId($callData),
                'from_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['direction'] ?? 'inbound',
                'status' => 'in_progress',
                'started_at' => isset($callData['start_timestamp']) 
                    ? Carbon::createFromTimestamp($callData['start_timestamp'] / 1000) 
                    : now(),
                'correlation_id' => $correlationId
            ]
        );
        
        $this->logInfo('Call record created/updated', [
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id
        ]);
        
        return [
            'success' => true,
            'call_id' => $call->id,
            'message' => 'Call started event processed'
        ];
    }
    
    /**
     * Handle call_ended event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleCallEnded(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $payload = $webhookEvent->payload;
        $callData = $payload['call'] ?? [];
        
        $this->logInfo('Processing call_ended event', [
            'call_id' => $callData['call_id'] ?? null
        ]);
        
        return $this->withCorrelationId($correlationId, function () use ($callData, $correlationId) {
            // Find or create call record (disable tenant scope for webhook processing)
            $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('retell_call_id', $callData['call_id'])
                ->first();
            
            if (!$call) {
                $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->create([
                    'retell_call_id' => $callData['call_id'],
                    'company_id' => $this->resolveCompanyId($callData),
                    'from_number' => $callData['from_number'] ?? null,
                    'to_number' => $callData['to_number'] ?? null,
                    'direction' => $callData['direction'] ?? 'inbound',
                    'correlation_id' => $correlationId
                ]);
            }
            
            // Update call with end data
            $call->update([
                'status' => $callData['disconnection_reason'] ?? 'completed',
                'ended_at' => isset($callData['end_timestamp']) 
                    ? Carbon::createFromTimestamp($callData['end_timestamp'] / 1000) 
                    : now(),
                'duration' => $callData['call_duration'] ?? 0,
                'recording_url' => $callData['recording_url'] ?? null,
                'transcript' => $callData['transcript'] ?? null,
                'transcript_object' => $callData['transcript_object'] ?? null,
                'call_analysis' => $callData['call_analysis'] ?? null,
                'answered_by' => $callData['answered_by'] ?? null,
                'metadata' => array_merge($call->metadata ?? [], [
                    'disconnection_reason' => $callData['disconnection_reason'] ?? null,
                    'public_log_url' => $callData['public_log_url'] ?? null
                ])
            ]);
            
            // Process appointment booking if needed
            $bookingResult = $this->processAppointmentBooking($call, $callData);
            
            // Refresh additional call data
            $this->refreshCallData($call, $callData);
            
            $this->logInfo('Call ended event processed', [
                'call_id' => $call->id,
                'booking_result' => $bookingResult
            ]);
            
            return [
                'success' => true,
                'call_id' => $call->id,
                'booking_result' => $bookingResult,
                'message' => 'Call ended event processed'
            ];
        });
    }
    
    /**
     * Handle call_analyzed event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleCallAnalyzed(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $payload = $webhookEvent->payload;
        $callData = $payload['call'] ?? [];
        
        $this->logInfo('Processing call_analyzed event', [
            'call_id' => $callData['call_id'] ?? null
        ]);
        
        // Update call with analysis data (disable tenant scope for webhook processing)
        $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('retell_call_id', $callData['call_id'])
            ->first();
        
        if ($call) {
            $call->update([
                'call_analysis' => $callData['call_analysis'] ?? null,
                'sentiment' => $callData['call_analysis']['sentiment'] ?? null,
                'summary' => $callData['call_analysis']['summary'] ?? null
            ]);
            
            $this->logInfo('Call analysis updated', [
                'call_id' => $call->id
            ]);
        }
        
        return [
            'success' => true,
            'call_id' => $call->id ?? null,
            'message' => 'Call analyzed event processed'
        ];
    }
    
    /**
     * Handle call_inbound event (real-time response)
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleCallInbound(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $payload = $webhookEvent->payload;
        $callData = $payload['call_inbound'] ?? [];
        
        $this->logInfo('Processing call_inbound event', [
            'from_number' => $callData['from_number'] ?? null,
            'to_number' => $callData['to_number'] ?? null
        ]);
        
        // Get company based on the to_number (disable tenant scope for webhook processing)
        $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('phone_number', $callData['to_number'])
            ->first();
        
        if (!$company) {
            $this->logWarning('No company found for phone number', [
                'to_number' => $callData['to_number']
            ]);
            $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->first(); // Fallback
        }
        
        // Build response with dynamic variables
        $response = [
            'agent_id' => $company->retell_agent_id ?? config('services.retell.default_agent_id'),
            'dynamic_variables' => [
                'company_name' => $company->name ?? 'AskProAI',
                'caller_number' => $callData['from_number'] ?? '',
                'caller_phone_number' => $callData['from_number'] ?? '', // Alternative variable name
                'current_time_berlin' => now()->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s'),
                'current_date' => now()->setTimezone('Europe/Berlin')->format('Y-m-d'),
                'current_time' => now()->setTimezone('Europe/Berlin')->format('H:i'),
                'weekday' => now()->setTimezone('Europe/Berlin')->locale('de')->dayName,
                'correlation_id' => $correlationId
            ]
        ];
        
        // Check for availability requests
        if ($this->isAvailabilityCheckRequest($payload)) {
            $availabilityData = $this->checkAvailability($company, $payload);
            $response['dynamic_variables'] = array_merge(
                $response['dynamic_variables'],
                $availabilityData
            );
        }
        
        return [
            'success' => true,
            'response' => $response,
            'message' => 'Inbound call handled'
        ];
    }
    
    /**
     * Process appointment booking from call data
     *
     * @param Call $call
     * @param array $callData
     * @return array|null
     */
    protected function processAppointmentBooking(Call $call, array $callData): ?array
    {
        try {
            // Check if call has appointment data
            $appointmentData = $this->extractAppointmentData($callData);
            
            if (!$appointmentData) {
                return null;
            }
            
            // Get or create customer
            $customer = $this->resolveCustomer($call, $callData);
            
            if (!$customer) {
                $this->logWarning('Could not resolve customer for appointment booking');
                return null;
            }
            
            // Book appointment
            $bookingService = app(AppointmentBookingService::class);
            $result = $bookingService->bookFromPhoneCall(
                $appointmentData,
                $call
            );
            
            // Update call with appointment reference
            if ($result['success'] && isset($result['appointment'])) {
                $call->update([
                    'appointment_id' => $result['appointment']->id,
                    'metadata' => array_merge($call->metadata ?? [], [
                        'appointment_booked' => true,
                        'booking_result' => $result
                    ])
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logError('Failed to process appointment booking', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract appointment data from call data
     *
     * @param array $callData
     * @return array|null
     */
    protected function extractAppointmentData(array $callData): ?array
    {
        // Enhanced logging for debugging
        $this->logInfo('Extracting appointment data from call', [
            'call_id' => $callData['call_id'] ?? null,
            'has_retell_llm_dynamic_variables' => isset($callData['retell_llm_dynamic_variables']),
            'has_custom_analysis_data' => isset($callData['call_analysis']['custom_analysis_data']),
            'custom_fields' => array_filter(array_keys($callData), fn($k) => strpos($k, '_') === 0)
        ]);
        
        // First check if we have cached appointment data from collect_appointment_data function
        $callId = $callData['call_id'] ?? null;
        if ($callId) {
            $cacheKey = "retell:appointment:{$callId}";
            $cachedData = Cache::get($cacheKey);
            
            if ($cachedData) {
                $this->logInfo('Using cached appointment data from collect_appointment_data', [
                    'call_id' => $callId,
                    'reference_id' => $cachedData['reference_id'] ?? null
                ]);
                
                // Map cached data to expected format
                return [
                    'date' => $cachedData['datum'] ?? null,
                    'time' => $cachedData['uhrzeit'] ?? null,
                    'service' => $cachedData['dienstleistung'] ?? null,
                    'customer_name' => $cachedData['name'] ?? null,
                    'customer_phone' => $cachedData['telefonnummer'] ?? null,
                    'customer_email' => $cachedData['email'] ?? null,
                    'staff_preference' => $cachedData['mitarbeiter_wunsch'] ?? null,
                    'notes' => $cachedData['kundenpraeferenzen'] ?? null,
                    'reference_id' => $cachedData['reference_id'] ?? null,
                    'appointment_id' => $cachedData['appointment_id'] ?? null
                ];
            }
        }
        
        // Check retell_llm_dynamic_variables for appointment_data
        $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? null;
        if ($dynamicVars) {
            $this->logInfo('Found retell_llm_dynamic_variables', [
                'keys' => array_keys($dynamicVars)
            ]);
            
            // Check if appointment data is nested
            if (isset($dynamicVars['appointment_data'])) {
                $this->logInfo('Found nested appointment_data');
                $appointmentData = $dynamicVars['appointment_data'];
            } else {
                // Check if the fields are directly in dynamic variables
                $appointmentFields = ['datum', 'uhrzeit', 'name', 'telefonnummer', 'dienstleistung'];
                $hasDirectFields = false;
                
                foreach ($appointmentFields as $field) {
                    if (isset($dynamicVars[$field])) {
                        $hasDirectFields = true;
                        break;
                    }
                }
                
                if ($hasDirectFields) {
                    $this->logInfo('Found appointment fields directly in dynamic variables');
                    $appointmentData = $dynamicVars;
                }
            }
            
            if (isset($appointmentData)) {
                return [
                    'date' => $appointmentData['datum'] ?? null,
                    'time' => $appointmentData['uhrzeit'] ?? null,
                    'service' => $appointmentData['dienstleistung'] ?? null,
                    'customer_name' => $appointmentData['name'] ?? null,
                    'customer_phone' => $appointmentData['telefonnummer'] ?? null,
                    'customer_email' => $appointmentData['email'] ?? null,
                    'staff_preference' => $appointmentData['mitarbeiter_wunsch'] ?? null,
                    'notes' => $appointmentData['kundenpraeferenzen'] ?? null
                ];
            }
        }
        
        // Check custom analysis data
        $customData = $callData['call_analysis']['custom_analysis_data'] ?? null;
        
        if ($customData && isset($customData['appointment_details'])) {
            return $customData['appointment_details'];
        }
        
        // Check for structured data in transcript
        $structuredData = $callData['transcript_object']['structured_data'] ?? null;
        
        if ($structuredData && isset($structuredData['appointment'])) {
            return $structuredData['appointment'];
        }
        
        // Try to extract from custom fields
        if (isset($callData['custom_fields']) && !empty($callData['custom_fields'])) {
            $fields = $callData['custom_fields'];
            
            if (isset($fields['appointment_date']) && isset($fields['appointment_time'])) {
                return [
                    'date' => $fields['appointment_date'],
                    'time' => $fields['appointment_time'],
                    'service' => $fields['service'] ?? null,
                    'staff_id' => $fields['staff_id'] ?? null,
                    'notes' => $fields['notes'] ?? null
                ];
            }
        }
        
        // Log what we tried and failed to find
        $this->logWarning('No appointment data found in any expected location', [
            'call_id' => $callId,
            'checked_locations' => [
                'cache' => 'not found',
                'retell_llm_dynamic_variables' => $dynamicVars ? 'exists but no appointment data' : 'not set',
                'custom_analysis_data' => isset($callData['call_analysis']['custom_analysis_data']) ? 'exists' : 'not set',
                'transcript_object' => isset($callData['transcript_object']) ? 'exists' : 'not set',
                'custom_fields' => !empty($callData['custom_fields']) ? 'exists' : 'not set'
            ]
        ]);
        
        return null;
    }
    
    /**
     * Resolve customer from call data
     *
     * @param Call $call
     * @param array $callData
     * @return Customer|null
     */
    protected function resolveCustomer(Call $call, array $callData): ?Customer
    {
        // Try to find by phone number
        $phoneNumber = $call->from_number;
        
        if (!$phoneNumber) {
            return null;
        }
        
        $phoneResolver = app(PhoneNumberResolver::class);
        $normalizedPhone = $phoneResolver->normalize($phoneNumber);
        
        // Find existing customer
        $customer = Customer::where('company_id', $call->company_id)
            ->where(function ($query) use ($phoneNumber, $normalizedPhone) {
                $query->where('phone', $phoneNumber)
                    ->orWhere('phone', $normalizedPhone);
            })
            ->first();
        
        if ($customer) {
            return $customer;
        }
        
        // Extract customer info from call data
        $customerInfo = $this->extractCustomerInfo($callData);
        
        if (!$customerInfo) {
            return null;
        }
        
        // Create new customer
        return Customer::create([
            'company_id' => $call->company_id,
            'name' => $customerInfo['name'] ?? 'Unknown',
            'phone' => $normalizedPhone,
            'email' => $customerInfo['email'] ?? null,
            'notes' => $customerInfo['notes'] ?? null,
            'source' => 'phone_call',
            'metadata' => [
                'created_from_call' => true,
                'call_id' => $call->id,
                'correlation_id' => $call->correlation_id
            ]
        ]);
    }
    
    /**
     * Extract customer info from call data
     *
     * @param array $callData
     * @return array|null
     */
    protected function extractCustomerInfo(array $callData): ?array
    {
        // Check custom analysis data
        $customData = $callData['call_analysis']['custom_analysis_data'] ?? null;
        
        if ($customData && isset($customData['customer_info'])) {
            return $customData['customer_info'];
        }
        
        // Check structured data
        $structuredData = $callData['transcript_object']['structured_data'] ?? null;
        
        if ($structuredData && isset($structuredData['customer'])) {
            return $structuredData['customer'];
        }
        
        // Check custom fields
        $fields = $callData['custom_fields'] ?? [];
        
        if (isset($fields['customer_name']) || isset($fields['customer_email'])) {
            return [
                'name' => $fields['customer_name'] ?? null,
                'email' => $fields['customer_email'] ?? null,
                'phone' => $fields['customer_phone'] ?? null
            ];
        }
        
        return null;
    }
    
    /**
     * Resolve company ID from call data
     *
     * @param array $callData
     * @return int|null
     */
    protected function resolveCompanyId(array $callData): ?int
    {
        // Use PhoneNumberResolver for comprehensive resolution
        $phoneResolver = app(PhoneNumberResolver::class);
        $context = $phoneResolver->resolveFromWebhook([
            'to' => $callData['to_number'] ?? null,
            'from' => $callData['from_number'] ?? null,
            'agent_id' => $callData['agent_id'] ?? null,
            'metadata' => $callData['metadata'] ?? []
        ]);
        
        if ($context['company_id']) {
            return $context['company_id'];
        }
        
        // Log warning if we can't resolve company
        Log::warning('Could not resolve company from call data', [
            'to_number' => $callData['to_number'] ?? null,
            'agent_id' => $callData['agent_id'] ?? null,
            'resolution_confidence' => $context['confidence'] ?? 0
        ]);
        
        // In production, we should not fallback to a random company
        // This should be handled by proper configuration
        if (app()->environment('production')) {
            throw new \Exception('Unable to determine company from call data');
        }
        
        // Only in development/testing
        return Company::first()->id ?? null;
    }
    
    /**
     * Refresh call data from Retell API
     *
     * @param Call $call
     * @param array $callData
     * @return void
     */
    protected function refreshCallData(Call $call, array $callData): void
    {
        try {
            $retellService = new RetellService($call->company->retell_api_key);
            $updatedData = $retellService->getCall($call->retell_call_id);
            
            if ($updatedData) {
                $call->update([
                    'transcript' => $updatedData['transcript'] ?? $call->transcript,
                    'transcript_object' => $updatedData['transcript_object'] ?? $call->transcript_object,
                    'recording_url' => $updatedData['recording_url'] ?? $call->recording_url,
                    'public_log_url' => $updatedData['public_log_url'] ?? $call->public_log_url
                ]);
            }
        } catch (\Exception $e) {
            $this->logWarning('Failed to refresh call data', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if request is for availability checking
     *
     * @param array $payload
     * @return bool
     */
    protected function isAvailabilityCheckRequest(array $payload): bool
    {
        return isset($payload['dynamic_variables']['check_availability']) &&
               $payload['dynamic_variables']['check_availability'] === true;
    }
    
    /**
     * Check availability for appointment
     *
     * @param Company $company
     * @param array $payload
     * @return array
     */
    protected function checkAvailability(Company $company, array $payload): array
    {
        $requestedDate = $payload['dynamic_variables']['requested_date'] ?? null;
        $eventTypeId = $payload['dynamic_variables']['event_type_id'] ?? null;
        
        if (!$requestedDate || !$eventTypeId) {
            return [
                'availability_checked' => false,
                'error' => 'Missing required parameters'
            ];
        }
        
        try {
            $calcomService = new CalcomV2Service($company->calcom_api_key);
            $availability = $calcomService->checkAvailability($eventTypeId, $requestedDate);
            
            if ($availability['success']) {
                return [
                    'availability_checked' => true,
                    'slots_available' => count($availability['data']['slots']) > 0,
                    'available_slots' => $this->formatSlotsForVoice($availability['data']['slots']),
                    'slots_count' => count($availability['data']['slots'])
                ];
            }
            
            return [
                'availability_checked' => true,
                'slots_available' => false,
                'error' => $availability['message'] ?? 'Failed to check availability'
            ];
            
        } catch (\Exception $e) {
            $this->logError('Failed to check availability', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'availability_checked' => false,
                'error' => 'Availability check failed'
            ];
        }
    }
    
    /**
     * Format time slots for voice response
     *
     * @param array $slots
     * @return string
     */
    protected function formatSlotsForVoice(array $slots): string
    {
        if (empty($slots)) {
            return 'keine verfügbaren Termine';
        }
        
        $formatted = [];
        
        foreach (array_slice($slots, 0, 3) as $slot) {
            $time = Carbon::parse($slot);
            $formatted[] = $time->format('H:i') . ' Uhr';
        }
        
        return implode(', ', $formatted);
    }
}