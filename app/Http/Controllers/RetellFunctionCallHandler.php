<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\AppointmentAlternativeFinder;
use App\Services\CalcomService;
use App\Services\Retell\ServiceSelectionService;
use App\Services\Retell\ServiceNameExtractor;
use App\Services\Retell\WebhookResponseService;
use App\Services\Retell\CallLifecycleService;
use App\Services\Retell\CallTrackingService;
use App\Services\Retell\AppointmentCreationService;
use App\Services\Retell\CustomerDataValidator;
use App\Services\Retell\AppointmentCustomerResolver;
use App\Services\Retell\DateTimeParser;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Carbon\Carbon;
use App\Helpers\LogSanitizer;
use App\Http\Requests\CollectAppointmentRequest;

/**
 * Handles real-time function calls from Retell AI during active calls
 * Enables the AI to check availability and offer alternatives in real-time
 */
class RetellFunctionCallHandler extends Controller
{
    private AppointmentAlternativeFinder $alternativeFinder;
    private CalcomService $calcomService;
    private ServiceSelectionService $serviceSelector;
    private ServiceNameExtractor $serviceExtractor;
    private WebhookResponseService $responseFormatter;
    private CallLifecycleService $callLifecycle;
    private CallTrackingService $callTracking;
    private CustomerDataValidator $dataValidator;
    private AppointmentCustomerResolver $customerResolver;
    private DateTimeParser $dateTimeParser;
    private array $callContextCache = []; // DEPRECATED: Use CallLifecycleService caching instead

    public function __construct(
        ServiceSelectionService $serviceSelector,
        ServiceNameExtractor $serviceExtractor,
        WebhookResponseService $responseFormatter,
        CallLifecycleService $callLifecycle,
        CallTrackingService $callTracking,
        CustomerDataValidator $dataValidator,
        AppointmentCustomerResolver $customerResolver,
        DateTimeParser $dateTimeParser
    ) {
        $this->serviceSelector = $serviceSelector;
        $this->serviceExtractor = $serviceExtractor;
        $this->responseFormatter = $responseFormatter;
        $this->callLifecycle = $callLifecycle;
        $this->callTracking = $callTracking;
        $this->dataValidator = $dataValidator;
        $this->customerResolver = $customerResolver;
        $this->dateTimeParser = $dateTimeParser;
        $this->alternativeFinder = new AppointmentAlternativeFinder();
        $this->calcomService = new CalcomService();
    }

    /**
     * Get call context (company_id, branch_id) from call ID or phone number
     *
     * Loads the Call record with related PhoneNumber to determine
     * which company and branch this call belongs to for proper
     * multi-tenant isolation.
     *
     * Uses CallLifecycleService for request-scoped caching.
     *
     * 🔧 FIX 2025-10-19: Fallback to most recent call if callId is invalid
     * Bug: Retell sometimes sends "None" as call_id, breaking availability checks
     *
     * @param string|null $callId Retell call ID
     * @return array|null ['company_id' => int, 'branch_id' => int|null, 'phone_number_id' => int]
     */
    private function getCallContext(?string $callId): ?array
    {
        if (!$callId || $callId === 'None') {
            Log::warning('call_id is invalid, attempting fallback to most recent active call', [
                'call_id' => $callId
            ]);

            // Fallback: Get most recent active call (within last 5 minutes)
            $recentCall = \App\Models\Call::where('call_status', 'ongoing')
                ->where('start_timestamp', '>=', now()->subMinutes(5))
                ->orderBy('start_timestamp', 'desc')
                ->first();

            if ($recentCall) {
                Log::info('✅ Fallback successful: using most recent active call', [
                    'call_id' => $recentCall->retell_call_id,
                    'started_at' => $recentCall->start_timestamp
                ]);
                $callId = $recentCall->retell_call_id;
            } else {
                Log::error('❌ Fallback failed: no recent active calls found');
                return null;
            }
        }

        // 🔧 FIX: Race Condition - Retry with exponential backoff
        // The call session might not be committed yet when function is called
        $maxAttempts = 5;
        $call = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $call = $this->callLifecycle->getCallContext($callId);

            if ($call) {
                if ($attempt > 1) {
                    Log::info('✅ getCallContext succeeded on attempt ' . $attempt, [
                        'call_id' => $callId,
                        'total_attempts' => $attempt
                    ]);
                }
                break;
            }

            // Not found, wait and retry
            if ($attempt < $maxAttempts) {
                $delayMs = 50 * $attempt; // 50ms, 100ms, 150ms, 200ms, 250ms
                Log::info('⏳ getCallContext retry ' . $attempt . '/' . $maxAttempts, [
                    'call_id' => $callId,
                    'delay_ms' => $delayMs
                ]);
                usleep($delayMs * 1000); // Convert to microseconds
            }
        }

        if (!$call) {
            Log::error('❌ getCallContext failed after ' . $maxAttempts . ' attempts', [
                'call_id' => $callId
            ]);
            return null;
        }

        // 🔧 RACE CONDITION FIX (2025-10-24): Wait for company_id/branch_id enrichment
        // The Call record exists but may not yet have company_id/branch_id set
        // This happens when Retell webhook fires before enrichment completes
        if (!$call->company_id || !$call->branch_id) {
            Log::warning('⚠️ getCallContext: company_id/branch_id not set, waiting for enrichment...', [
                'call_id' => $call->id,
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'from_number' => $call->from_number
            ]);

            // Wait up to 1.5 seconds for enrichment to complete
            for ($waitAttempt = 1; $waitAttempt <= 3; $waitAttempt++) {
                usleep(500000); // 500ms between checks

                $call = $call->fresh(); // Reload from database

                if ($call->company_id && $call->branch_id) {
                    Log::info('✅ getCallContext: Enrichment completed after wait', [
                        'call_id' => $call->id,
                        'wait_attempt' => $waitAttempt,
                        'company_id' => $call->company_id,
                        'branch_id' => $call->branch_id
                    ]);
                    break;
                }

                Log::info('⏳ getCallContext enrichment wait ' . $waitAttempt . '/3', [
                    'call_id' => $call->id
                ]);
            }

            // If STILL NULL after waiting, we have a real problem
            if (!$call->company_id || !$call->branch_id) {
                Log::error('❌ getCallContext: Enrichment failed after waiting', [
                    'call_id' => $call->id,
                    'company_id' => $call->company_id,
                    'branch_id' => $call->branch_id,
                    'from_number' => $call->from_number,
                    'suggestion' => 'Check webhook processing order and database transactions'
                ]);
                return null;
            }
        }

        // 🔧 CRITICAL FIX (2025-10-24): Handle NULL phoneNumber (anonymous callers)
        // For anonymous callers or when phone not in database, use direct Call fields
        // instead of accessing potentially NULL phoneNumber relationship
        $phoneNumberId = null;
        $companyId = $call->company_id;      // Use direct field as fallback
        $branchId = $call->branch_id;        // Use direct field as fallback

        // Only use phoneNumber relationship if it exists (non-anonymous callers)
        if ($call->phoneNumber) {
            $phoneNumberId = $call->phoneNumber->id;
            $companyId = $call->phoneNumber->company_id;
            $branchId = $call->phoneNumber->branch_id;

            Log::debug('✅ getCallContext: Using phoneNumber relationship', [
                'call_id' => $call->id,
                'phone_number_id' => $phoneNumberId,
                'from_number' => $call->from_number
            ]);
        } else {
            Log::info('⚠️ getCallContext: NULL phoneNumber (anonymous caller) - trying to_number lookup', [
                'call_id' => $call->id,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'direct_company_id' => $companyId,
                'direct_branch_id' => $branchId
            ]);
        }

        // 🔧 CRITICAL FIX (2025-10-24): Anonymous caller fallback
        // If company_id still NULL, lookup from to_number (the number that was called)
        if (!$companyId || !$branchId) {
            if ($call->to_number) {
                $toPhoneNumber = \App\Models\PhoneNumber::where('number', $call->to_number)->first();

                if ($toPhoneNumber) {
                    $companyId = $toPhoneNumber->company_id;
                    $branchId = $toPhoneNumber->branch_id;

                    Log::info('✅ getCallContext: Resolved company from to_number', [
                        'call_id' => $call->id,
                        'to_number' => $call->to_number,
                        'company_id' => $companyId,
                        'branch_id' => $branchId
                    ]);
                } else {
                    Log::error('❌ getCallContext: to_number not found in database', [
                        'call_id' => $call->id,
                        'to_number' => $call->to_number
                    ]);
                }
            } else {
                Log::error('❌ getCallContext: No to_number available', [
                    'call_id' => $call->id
                ]);
            }
        }

        // Final validation: ensure we have valid company_id
        if (!$companyId || !$branchId) {
            Log::error('❌ getCallContext: Final validation failed - NULL company/branch', [
                'call_id' => $call->id,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number
            ]);
            return null;
        }

        return [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'phone_number_id' => $phoneNumberId,
            'call_id' => $call->id,
        ];
    }

    /**
     * Main handler for Retell function calls during active conversations
     */
    public function handleFunctionCall(Request $request)
    {
        $data = $request->all();

        // 🚨 CRITICAL DEBUG: Log EVERYTHING Retell sends us
        Log::warning('🚨 ===== RETELL FUNCTION CALL RECEIVED =====', [
            'timestamp' => now()->toIso8601String(),
            'method' => $request->method(),
            'path' => $request->path(),
            'all_data' => json_encode($data),
            'raw_body' => $request->getContent(),
        ]);

        // ENHANCED MONITORING FOR TEST CALL
        Log::info('📞 ===== RETELL WEBHOOK RECEIVED =====', [
            'timestamp' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'method' => $request->method(),
            'path' => $request->path(),
            'headers' => LogSanitizer::sanitizeHeaders($request->headers->all()),
            'raw_body' => LogSanitizer::sanitize($request->getContent()),
            'parsed_data' => LogSanitizer::sanitize($data),
            'function_name' => $data['name'] ?? $data['function_name'] ?? 'NONE',  // Bug #4 Fix: Retell sends 'name' not 'function_name'
            'parameters' => LogSanitizer::sanitize($data['args'] ?? $data['parameters'] ?? []),
            'call_id' => $data['call_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? null,
            'session_id' => $data['session_id'] ?? null
        ]);

        Log::info('🔧 Function call received from Retell', [
            'function' => $data['name'] ?? $data['function_name'] ?? 'unknown',  // Bug #4 Fix
            'parameters' => $data['args'] ?? $data['parameters'] ?? [],  // Bug #4 Fix
            'call_id' => $data['call_id'] ?? null
        ]);

        // Bug #4 Fix (Call 777): Retell sends 'name' and 'args', not 'function_name' and 'parameters'
        $functionName = $data['name'] ?? $data['function_name'] ?? '';
        $parameters = $data['arguments'] ?? $data['args'] ?? $data['parameters'] ?? [];
        // Bug #6 Fix (Call 778): call_id is inside parameters/args, not at top level - CHECK PARAMETERS FIRST!
        $callId = $parameters['call_id'] ?? $data['call_id'] ?? null;

        // 🔧 FIX 2025-10-24 15:35: Fallback if initialize_call doesn't have call_id parameter
        // Problem: Retell Agent Config may not pass call_id in function arguments
        // Evidence: Call 721 transcript shows initialize_call with "arguments": "{}"
        // Solution: For initialize_call specifically, always use top-level call_id from webhook
        if (str_contains($functionName, 'initialize_call') && (!$callId || $callId === 'None')) {
            $callId = $data['call_id'] ?? null;
            if ($callId && $callId !== 'None') {
                Log::info('⚠️ initialize_call: Using top-level call_id (not in function parameters)', [
                    'call_id' => $callId,
                    'function' => $functionName
                ]);
            }
        }

        // 🔧 FIX 2025-10-19: Agent sometimes sends "None" as string when call_id variable not injected
        // 🔧 FIX 2025-11-12: Also handle placeholder values like "call_1", "test", "example", "1"
        // 🔧 FIX 2025-11-12 22:59: Correct webhook path - call_id is at $data['call']['call_id'], not $data['call_id']
        // Fallback: Extract from root level webhook data
        $placeholders = ['None', 'null', '', 'call_1', 'test', 'example', 'call_test', '1'];
        if (is_null($callId) || in_array($callId, $placeholders, true) || (is_string($callId) && strlen($callId) < 10)) {
            $originalCallId = $callId;
            // CRITICAL: Real call_id is nested at $data['call']['call_id']
            $callId = $data['call']['call_id'] ?? $data['call_id'] ?? null;

            if ($callId && !in_array($callId, $placeholders, true)) {
                Log::warning('⚠️ call_id was invalid/placeholder in parameters, extracted from webhook root', [
                    'extracted_call_id' => $callId,
                    'original_param' => $originalCallId ?? 'missing',
                    'function' => $functionName,
                    'fix_applied' => 'placeholder_detection_2025-11-12_fixed',
                    'extraction_path' => 'data[call][call_id]'
                ]);
            } else {
                Log::error('❌ call_id is completely missing or invalid', [
                    'param_value' => $parameters['call_id'] ?? 'missing',
                    'root_value_nested' => $data['call']['call_id'] ?? 'missing',
                    'root_value_direct' => $data['call_id'] ?? 'missing',
                    'function' => $functionName
                ]);
            }
        }

        // 🔧 FIX 2025-10-23: Strip version suffix (_v17, _v18, etc.) to support versioned function names
        // ROOT CAUSE: Retell sends "book_appointment_v17" but match only has "book_appointment"
        // This caused ALL bookings to fail silently - agent said "booked" but nothing was created!
        // Reference: TESTCALL_ROOT_CAUSE_ANALYSIS_2025-10-23.md
        $baseFunctionName = preg_replace('/_v\d+$/', '', $functionName);

        Log::info('🔧 Function routing', [
            'original_name' => $functionName,
            'base_name' => $baseFunctionName,
            'version_stripped' => $functionName !== $baseFunctionName,
            'call_id' => $callId
        ]);

        // 🎯 TRACK FUNCTION CALL (USER'S #1 PRIORITY FEATURE)
        // Start tracking EVERY function call with input/output/duration/errors
        $trace = null;
        if ($callId && $callId !== 'None') {
            try {
                // Get or create call session (auto-creates on first function call)
                $existingSession = \App\Models\RetellCallSession::where('call_id', $callId)->first();

                if (!$existingSession) {
                    // 🔧 FIX (2025-10-23): Robuste Auto-Creation
                    // Problem: getCallContext() kann NULL zurückgeben wenn Call noch nicht in DB
                    // Lösung: Fallback auf default company_id wenn Context nicht verfügbar

                    $callContext = $this->getCallContext($callId);

                    $companyId = $callContext['company_id'] ?? null;
                    $customerId = $callContext['customer_id'] ?? null;

                    // Fallback: Get company_id from agent_id if context unavailable
                    if (!$companyId && isset($data['agent_id'])) {
                        $agent = \App\Models\RetellAgent::where('agent_id', $data['agent_id'])->first();
                        if ($agent) {
                            $companyId = $agent->company_id;
                            Log::info('📌 Using company_id from agent', [
                                'agent_id' => $data['agent_id'],
                                'company_id' => $companyId
                            ]);
                        }
                    }

                    // Auto-create session on first function call
                    $existingSession = $this->callTracking->startCallSession([
                        'call_id' => $callId,
                        'company_id' => $companyId,
                        'customer_id' => $customerId,
                        'agent_id' => $data['agent_id'] ?? null,
                    ]);

                    Log::info('📞 Auto-created call session on first function call', [
                        'call_id' => $callId,
                        'session_id' => $existingSession->id,
                        'first_function' => $functionName,
                        'company_id' => $companyId,
                        'context_available' => $callContext ? 'yes' : 'no (used fallback)'
                    ]);
                }

                // Track the function call
                $trace = $this->callTracking->trackFunctionCall(
                    callId: $callId,
                    functionName: $functionName, // Use original name with version
                    arguments: $parameters
                );
            } catch (\Exception $e) {
                // Don't fail the function call if tracking fails
                Log::error('⚠️ Function tracking failed (non-blocking)', [
                    'error' => $e->getMessage(),
                    'call_id' => $callId,
                    'function' => $functionName,
                    'stack_trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Route to appropriate function handler
        try {
            $result = match($baseFunctionName) {
            // 🔧 FIX 2025-10-22 V133: Add check_customer to enable customer recognition
            'check_customer' => $this->checkCustomer($parameters, $callId),
            // 🔧 FIX 2025-10-18: Add parse_date handler to prevent agent from calculating dates incorrectly
            'parse_date' => $this->handleParseDate($parameters, $callId),
            'check_availability' => $this->checkAvailability($parameters, $callId),
            'book_appointment' => $this->bookAppointment($parameters, $callId),
            // 🔧 FIX 2025-11-12: Agent V116 nutzt "start_booking" statt "book_appointment"
            'start_booking' => $this->bookAppointment($parameters, $callId),
            'query_appointment' => $this->queryAppointment($parameters, $callId),
            // 🔒 NEW V85: Query appointment by customer name (for anonymous/hidden number calls)
            'query_appointment_by_name' => $this->queryAppointmentByName($parameters, $callId),
            'get_alternatives' => $this->getAlternatives($parameters, $callId),
            'list_services' => $this->listServices($parameters, $callId),
            'cancel_appointment' => $this->handleCancellationAttempt($parameters, $callId),
            'reschedule_appointment' => $this->handleRescheduleAttempt($parameters, $callId),
            'request_callback' => $this->handleCallbackRequest($parameters, $callId),
            'find_next_available' => $this->handleFindNextAvailable($parameters, $callId),
            // ✅ Phase 3: New operational functions with policy enforcement
            'get_service_info' => $this->getServiceInformation($parameters, $callId),
            'get_opening_hours' => $this->getOpeningHours($parameters, $callId),
            // 🔧 FIX 2025-10-24: Add initialize_call to support V39 flow Function Node
            'initialize_call' => $this->initializeCall($parameters, $callId),
            default => $this->handleUnknownFunction($functionName, $parameters, $callId)
            };

            // 🎯 RECORD FUNCTION SUCCESS
            if ($trace) {
                try {
                    $responseData = $result instanceof \Illuminate\Http\JsonResponse
                        ? $result->getData(true)
                        : (is_array($result) ? $result : ['response' => $result]);

                    $this->callTracking->recordFunctionResponse(
                        traceId: $trace->id,
                        response: $responseData,
                        status: 'success'
                    );
                } catch (\Exception $e) {
                    Log::error('⚠️ Failed to record function success (non-blocking)', [
                        'error' => $e->getMessage(),
                        'trace_id' => $trace->id
                    ]);
                }
            }

            return $result;

        } catch (\Exception $e) {
            // 🎯 RECORD FUNCTION ERROR
            if ($trace) {
                try {
                    $this->callTracking->recordFunctionResponse(
                        traceId: $trace->id,
                        response: [],
                        status: 'error',
                        error: [
                            'code' => 'function_execution_failed',
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'booking_failed' => $baseFunctionName === 'book_appointment',
                        ]
                    );
                } catch (\Exception $trackingError) {
                    Log::error('⚠️ Failed to record function error (non-blocking)', [
                        'error' => $trackingError->getMessage(),
                        'trace_id' => $trace->id
                    ]);
                }
            }

            // Log and re-throw the original exception
            Log::error('❌ Function execution failed', [
                'function' => $functionName,
                'call_id' => $callId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Check if customer exists in database by phone number
     * 🔧 FIX 2025-10-22 V133: Implement check_customer in main function handler
     * Called at start of every call to recognize returning customers
     */
    private function checkCustomer(array $params, ?string $callId)
    {
        try {
            Log::warning('📞 check_customer START', [
                'call_id' => $callId,
                'params' => $params,
                'timestamp' => now()->toIso8601String()
            ]);

            // Get call record to extract phone number and company context
            $call = $this->callLifecycle->findCallByRetellId($callId);

            if (!$call) {
                Log::error('❌ check_customer failed: Call not found', [
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $phoneNumber = $call->from_number;
            $companyId = $call->company_id;

            if (!$phoneNumber || $phoneNumber === 'anonymous') {
                Log::info('🔍 check_customer: Anonymous call, no phone number', [
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->success([
                    'customer_id' => null,
                    'name' => null,
                    'found' => false,
                    'status' => 'new_customer'
                ], 'Dies ist ein neuer Kunde. Bitte fragen Sie nach dem Namen.');
            }

            // Normalize phone number
            $normalizedPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);

            // Search for customer with company isolation
            $customer = Customer::where(function($q) use ($normalizedPhone) {
                $q->where('phone', $normalizedPhone)
                  ->orWhere('phone', 'LIKE', '%' . substr($normalizedPhone, -8) . '%');
            })
            ->where('company_id', $companyId)
            ->first();

            if ($customer) {
                // Update call with customer_id
                $call->update(['customer_id' => $customer->id]);

                Log::info('✅ check_customer: Customer found', [
                    'call_id' => $callId,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name
                ]);

                // 🔥 NEW: Analyze customer preferences (service prediction + staff preference)
                $recognitionService = app(\App\Services\Retell\CustomerRecognitionService::class);
                $preferences = $recognitionService->analyzeCustomerPreferences($customer);
                $smartGreeting = $recognitionService->generateSmartGreeting($customer, $preferences);

                return $this->responseFormatter->success([
                    'customer_id' => $customer->id,
                    'name' => $customer->name,
                    'first_name' => explode(' ', $customer->name)[0] ?? $customer->name,
                    'last_name' => explode(' ', $customer->name, 2)[1] ?? '',
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'found' => true,
                    'status' => 'existing_customer',
                    'last_visit' => $customer->last_appointment_at?->format('d.m.Y'),
                    'total_appointments' => $customer->appointments()->count(),
                    // 🔥 NEW: Smart predictions based on appointment history
                    'predicted_service' => $preferences['predicted_service'],
                    'service_confidence' => $preferences['service_confidence'],
                    'preferred_staff' => $preferences['preferred_staff'],
                    'preferred_staff_id' => $preferences['preferred_staff_id'],
                    'appointment_history' => $preferences['appointment_history']
                ], $smartGreeting);
            }

            Log::info('🆕 check_customer: New customer', [
                'call_id' => $callId,
                'phone' => substr($normalizedPhone, -4)
            ]);

            return $this->responseFormatter->success([
                'customer_id' => null,
                'name' => null,
                'found' => false,
                'status' => 'new_customer'
            ], 'Dies ist ein neuer Kunde. Bitte fragen Sie nach dem Namen.');

        } catch (\Exception $e) {
            Log::error('❌ check_customer exception', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->responseFormatter->error('Customer check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check availability for a specific date/time
     * Called when customer asks: "Haben Sie am Freitag um 16 Uhr noch was frei?"
     */
    private function checkAvailability(array $params, ?string $callId)
    {
        try {
            $startTime = microtime(true);

            // 🔍 DETAILED MONITORING - Log all incoming parameters
            Log::info('🎯 checkAvailability ENTRY - Detailed Monitoring', [
                'call_id' => $callId,
                'call_id_type' => gettype($callId),
                'params' => $params,
                'params_keys' => array_keys($params),
                'timestamp' => now()->toDateTimeString(),
                'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ]);

            // FEATURE: Branch-aware service selection for availability checks
            // Get call context to ensure branch isolation
            $callContext = $this->getCallContext($callId);

            // 🔍 DETAILED MONITORING - Log call context retrieval result
            Log::info('🔍 Call context retrieved', [
                'call_id' => $callId,
                'context_found' => !is_null($callContext),
                'context_data' => $callContext,
                'context_keys' => $callContext ? array_keys($callContext) : null
            ]);

            if (!$callContext) {
                Log::error('❌ Cannot check availability: Call context not found', [
                    'call_id' => $callId,
                    'params' => $params
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            Log::info('✅ Call context validated', [
                'call_id' => $callId,
                'company_id' => $companyId,
                'branch_id' => $branchId
            ]);

            // 🔧 FIX 2025-10-22: Option 4 - Merge parse_date into check_availability
            // PROBLEM: Agent goes silent after parse_date success (V127, V128, V129 all failed)
            // SOLUTION: Parse date_string directly in check_availability, eliminate chaining

            // Check if date_string parameter exists (new unified approach)
            if (isset($params['date_string']) && !isset($params['datum'])) {
                Log::info('🔄 Parsing date_string in check_availability (Option 4)', [
                    'call_id' => $callId,
                    'date_string' => $params['date_string']
                ]);

                // Use DateTimeParser to parse the date string
                $parsedDate = (new \App\Services\Retell\DateTimeParser())->parseDateString($params['date_string']);

                if (!$parsedDate) {
                    Log::error('❌ Failed to parse date_string in check_availability', [
                        'call_id' => $callId,
                        'date_string' => $params['date_string']
                    ]);
                    return $this->responseFormatter->error(
                        'Das Datum konnte nicht verstanden werden. Bitte nennen Sie es im Format: "Montag", "heute", "morgen", oder "20.10.2025".'
                    );
                }

                // Convert parsed date (Y-m-d) to 'datum' parameter for parseDateTime
                $params['datum'] = $parsedDate;

                Log::info('✅ Successfully parsed date_string', [
                    'call_id' => $callId,
                    'date_string' => $params['date_string'],
                    'parsed_datum' => $parsedDate
                ]);
            }

            // Parse parameters (now with datum set from date_string if applicable)
            Log::info('🔍 Parsing datetime with DateTimeParser', [
                'call_id' => $callId,
                'params_datum' => $params['datum'] ?? null,
                'params_uhrzeit' => $params['uhrzeit'] ?? null,
                'params_time' => $params['time'] ?? null,
                'all_params' => $params
            ]);

            try {
                $requestedDate = $this->dateTimeParser->parseDateTime($params);

                Log::info('✅ DateTimeParser result', [
                    'call_id' => $callId,
                    'result_type' => gettype($requestedDate),
                    'is_carbon' => $requestedDate instanceof \Carbon\Carbon,
                    'formatted' => $requestedDate instanceof \Carbon\Carbon ? $requestedDate->format('Y-m-d H:i:s') : null
                ]);
            } catch (\Exception $e) {
                Log::error('❌ DateTimeParser threw exception', [
                    'call_id' => $callId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'params' => $params
                ]);
                return $this->responseFormatter->error('Fehler beim Parsen des Datums. Bitte versuchen Sie es später erneut.');
            }

            // 🔧 FIX 2025-10-18: Validate that parseDateTime returned a valid Carbon instance
            if (!$requestedDate || !($requestedDate instanceof \Carbon\Carbon)) {
                Log::error('⚠️ dateTimeParser returned invalid value', [
                    'call_id' => $callId,
                    'parsed_value_type' => gettype($requestedDate),
                    'params' => $params
                ]);
                return $this->responseFormatter->error('Fehler beim Parsen des Datums. Bitte versuchen Sie es später erneut.');
            }

            $duration = $params['duration'] ?? 60;
            $serviceId = $params['service_id'] ?? null;
            $serviceName = $params['service_name'] ?? $params['dienstleistung'] ?? null;

            Log::info('⏱️ checkAvailability START', [
                'call_id' => $callId,
                'requested_date' => $requestedDate->format('Y-m-d H:i'),
                'service_id' => $serviceId,
                'service_name' => $serviceName,
                'timestamp_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            // Get service with branch validation using ServiceSelectionService
            // Priority: service_id > service_name > default
            if ($serviceId) {
                $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
            } elseif ($serviceName) {
                $service = $this->serviceSelector->findServiceByName($serviceName, $companyId, $branchId);
                Log::info('🔍 Service selection by name', [
                    'input_name' => $serviceName,
                    'matched_service' => $service?->name,
                    'service_id' => $service?->id,
                    'call_id' => $callId
                ]);
            } else {
                $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
            }

            if (!$service || !$service->calcom_event_type_id) {
                Log::error('No active service with Cal.com event type found for branch', [
                    'service_id' => $serviceId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Service nicht verfügbar für diese Filiale');
            }

            Log::info('Using service for availability check', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $service->calcom_event_type_id,
                'call_id' => $callId
            ]);

            // 🔧 FIX 2025-10-25: Bug #11 - Validate minimum booking notice
            // PROBLEM: System says "available" for times < 15 min in advance, then Cal.com rejects with 400
            // SOLUTION: Validate booking notice upfront, provide helpful error with alternatives
            $bookingValidator = app(\App\Services\Booking\BookingNoticeValidator::class);
            $noticeValidation = $bookingValidator->validateBookingNotice($requestedDate, $service, $branchId);

            if (!$noticeValidation['valid']) {
                // Booking notice violation - suggest alternatives
                $alternatives = $bookingValidator->suggestAlternatives($requestedDate, $service, $branchId, 2);
                $errorMessage = $bookingValidator->formatErrorMessage($noticeValidation, $alternatives);

                Log::warning('⏰ Booking notice validation failed', [
                    'call_id' => $callId,
                    'requested_time' => $requestedDate->toDateTimeString(),
                    'minimum_notice_minutes' => $noticeValidation['minimum_notice_minutes'],
                    'earliest_bookable' => $noticeValidation['earliest_bookable']->toDateTimeString(),
                    'alternatives_count' => count($alternatives),
                ]);

                return [
                    'success' => false,
                    'available' => false,
                    'reason' => 'booking_notice_violation',
                    'message' => $errorMessage,
                    'minimum_notice_minutes' => $noticeValidation['minimum_notice_minutes'],
                    'earliest_bookable' => $noticeValidation['earliest_bookable']->format('Y-m-d H:i'),
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'alternatives' => array_map(function($alt) {
                        return [
                            'date' => $alt['date'],
                            'time' => $alt['time'],
                            'formatted' => $alt['formatted_de'],
                        ];
                    }, $alternatives),
                ];
            }

            Log::info('✅ Booking notice validation passed', [
                'call_id' => $callId,
                'requested_time' => $requestedDate->toDateTimeString(),
                'minimum_notice_minutes' => $noticeValidation['minimum_notice_minutes'],
            ]);

            // 🔧 FIX 2025-10-22: Pin selected service to call session
            // PROBLEM: collectAppointment was using different service, causing Event Type mismatch
            // SOLUTION: Cache service_id for entire call session (30 min TTL)
            if ($callId) {
                Cache::put("call:{$callId}:service_id", $service->id, now()->addMinutes(30));
                Cache::put("call:{$callId}:service_name", $service->name, now()->addMinutes(30));
                Cache::put("call:{$callId}:event_type_id", $service->calcom_event_type_id, now()->addMinutes(30));

                Log::info('📌 Service pinned to call session', [
                    'call_id' => $callId,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'cache_key' => "call:{$callId}:service_id",
                    'ttl_minutes' => 30
                ]);
            }

            // 🔧 REFACTORED 2025-11-13: Cal.com as SOURCE OF TRUTH for composite services
            // ⚠️ CRITICAL: Local DB only contains OUR bookings, Cal.com may have external bookings
            // Phase-aware availability checking for composite services
            // Composite services have segments with gaps where staff is available
            if ($service->composite && !empty($service->segments)) {
                Log::info('🎨 Composite service detected - using Cal.com + phase-aware availability check', [
                    'call_id' => $callId,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'segments_count' => count($service->segments),
                    'requested_time' => $requestedDate->format('Y-m-d H:i')
                ]);

                // Get staff for this branch (for now, check first available staff)
                // TODO: Allow customer to select specific staff
                $staff = \App\Models\Staff::where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->whereHas('services', function($q) use ($service) {
                        $q->where('service_id', $service->id);
                    })
                    ->first();

                if (!$staff) {
                    Log::error('❌ No staff found for composite service', [
                        'service_id' => $service->id,
                        'branch_id' => $branchId
                    ]);
                    return $this->responseFormatter->error('Kein Mitarbeiter für diesen Service verfügbar');
                }

                // STEP 1: Check Cal.com availability (SOURCE OF TRUTH) ✅
                $calcomAvailabilityService = app(\App\Services\Appointments\CalcomAvailabilityService::class);

                $calcomStartTime = microtime(true);
                $calcomAvailable = false;

                try {
                    $calcomAvailable = $calcomAvailabilityService->isTimeSlotAvailable(
                        $requestedDate,
                        $service->calcom_event_type_id,
                        $service->duration_minutes ?? $duration,
                        $staff->calcom_user_id,
                        $service->company->calcom_team_id
                    );

                    $calcomDuration = round((microtime(true) - $calcomStartTime) * 1000, 2);

                    Log::info('✅ Cal.com availability checked (composite service)', [
                        'call_id' => $callId,
                        'available' => $calcomAvailable,
                        'duration_ms' => $calcomDuration,
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'event_type_id' => $service->calcom_event_type_id,
                        'staff_id' => $staff->id
                    ]);

                    // 🔧 FIX 2025-11-14: Cache availability check timestamp for race condition prevention
                    Cache::put("call:{$callId}:last_availability_check", now(), now()->addMinutes(10));
                } catch (\Exception $e) {
                    $calcomDuration = round((microtime(true) - $calcomStartTime) * 1000, 2);

                    Log::error('❌ Cal.com availability check failed (composite service)', [
                        'call_id' => $callId,
                        'error' => $e->getMessage(),
                        'duration_ms' => $calcomDuration
                    ]);

                    // Conservative: if Cal.com check fails, assume not available
                    return $this->responseFormatter->error('Verfügbarkeitsprüfung fehlgeschlagen. Bitte versuchen Sie es später erneut.');
                }

                // STEP 2: If Cal.com says NO → find alternatives immediately
                if (!$calcomAvailable) {
                    Log::warning('⚠️ Cal.com says slot NOT available (composite service)', [
                        'call_id' => $callId,
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'service' => $service->name
                    ]);

                    // Find alternatives considering phase-aware availability
                    $alternatives = $this->findAlternativesForCompositeService(
                        $service,
                        $staff,
                        $requestedDate,
                        $branchId
                    );

                    return [
                        'success' => true,
                        'available' => false,
                        'service' => $service->name,
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'alternatives' => $alternatives,
                        'message' => $this->formatAlternativesMessage($requestedDate, $alternatives),
                    ];
                }

                // STEP 3: Cal.com says YES → verify phase-aware availability for gaps
                Log::info('✅ Cal.com says available - now checking phase-aware gaps', [
                    'call_id' => $callId,
                    'requested_time' => $requestedDate->format('Y-m-d H:i')
                ]);

                $availabilityService = app(\App\Services\ProcessingTimeAvailabilityService::class);

                $isPhaseAvailable = $availabilityService->isStaffAvailable(
                    $staff->id,
                    $requestedDate,
                    $service
                );

                if ($isPhaseAvailable) {
                    Log::info('✅ Phase-aware availability confirmed (composite service)', [
                        'call_id' => $callId,
                        'staff_id' => $staff->id,
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'service' => $service->name,
                        'checks_passed' => ['calcom' => true, 'phase_aware' => true]
                    ]);

                    return [
                        'success' => true,
                        'available' => true,
                        'service' => $service->name,
                        'staff' => $staff->name,
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'message' => sprintf(
                            'Ja, %s ist verfügbar am %s um %s Uhr.',
                            $service->name,
                            $requestedDate->locale('de')->isoFormat('dddd, [den] D. MMMM'),
                            $requestedDate->format('H:i')
                        ),
                    ];
                } else {
                    // Cal.com says available, but local phase check says no
                    // This could mean staff has internal conflicts during gaps
                    Log::warning('⚠️ Cal.com available but phase-aware check failed', [
                        'call_id' => $callId,
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'note' => 'Staff may have conflicts during processing time gaps'
                    ]);

                    $alternatives = $this->findAlternativesForCompositeService(
                        $service,
                        $staff,
                        $requestedDate,
                        $branchId
                    );

                    return [
                        'success' => true,
                        'available' => false,
                        'service' => $service->name,
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'alternatives' => $alternatives,
                        'message' => $this->formatAlternativesMessage($requestedDate, $alternatives),
                    ];
                }
            }

            // 🔧 REFACTORED 2025-11-13: Regular services now use Cal.com as SOURCE OF TRUTH
            // ⚠️ CRITICAL: Local DB only contains OUR bookings, Cal.com may have external bookings
            Log::info('📅 Regular service - using Cal.com API (SOURCE OF TRUTH)', [
                'call_id' => $callId,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'requested_time' => $requestedDate->format('Y-m-d H:i')
            ]);

            // Use new CalcomAvailabilityService for direct slot checking
            $calcomAvailabilityService = app(\App\Services\Appointments\CalcomAvailabilityService::class);

            $calcomStartTime = microtime(true);
            $isAvailable = false;

            try {
                // Check availability directly with Cal.com API
                $isAvailable = $calcomAvailabilityService->isTimeSlotAvailable(
                    $requestedDate,
                    $service->calcom_event_type_id,
                    $service->duration_minutes ?? $duration,
                    null, // staff_id - not specified for regular services yet
                    $service->company->calcom_team_id
                );

                $calcomDuration = round((microtime(true) - $calcomStartTime) * 1000, 2);

                Log::info('✅ Cal.com availability checked (regular service)', [
                    'call_id' => $callId,
                    'available' => $isAvailable,
                    'duration_ms' => $calcomDuration,
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'event_type_id' => $service->calcom_event_type_id,
                    'performance_target' => '< 1000ms',
                    'performance_status' => $calcomDuration < 1000 ? 'GOOD' : 'NEEDS_OPTIMIZATION'
                ]);

                // 🔧 FIX 2025-11-14: Cache availability check timestamp for race condition prevention
                Cache::put("call:{$callId}:last_availability_check", now(), now()->addMinutes(10));

                if ($calcomDuration > 2000) {
                    Log::warning('⚠️ Cal.com API slow response (regular service)', [
                        'call_id' => $callId,
                        'duration_ms' => $calcomDuration,
                        'threshold_ms' => 2000,
                        'recommendation' => 'Consider caching strategy or API optimization'
                    ]);
                }
            } catch (\Exception $e) {
                $calcomDuration = round((microtime(true) - $calcomStartTime) * 1000, 2);

                Log::error('❌ Cal.com availability check failed (regular service)', [
                    'call_id' => $callId,
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'duration_ms' => $calcomDuration
                ]);

                // Conservative: if Cal.com check fails, assume not available
                return $this->responseFormatter->error('Verfügbarkeitsprüfung fehlgeschlagen. Bitte versuchen Sie es später erneut.');
            }

            // 🔧 FIX 2025-10-11: Check if customer already has appointment at this time
            // Bug: Customer asked for Wednesday 9:00 but system didn't detect existing appointment
            if ($isAvailable) {
                // Get customer from call context to check for existing appointments
                $call = $this->callLifecycle->findCallByRetellId($callId);

                if ($call && $call->customer_id) {
                    // Check if customer already has an appointment at or around this time
                    $existingAppointment = Appointment::where('customer_id', $call->customer_id)
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->where(function($query) use ($requestedDate, $duration) {
                            // Check for overlapping appointments (within requested time window)
                            $query->whereBetween('starts_at', [
                                $requestedDate->copy()->subMinutes($duration),
                                $requestedDate->copy()->addMinutes($duration)
                            ])
                            ->orWhere(function($q) use ($requestedDate, $duration) {
                                // Or check if requested time falls within existing appointment
                                $q->where('starts_at', '<=', $requestedDate)
                                  ->where('ends_at', '>', $requestedDate);
                            });
                        })
                        ->first();

                    if ($existingAppointment) {
                        // Customer already has an appointment at this time!
                        $appointmentTime = $existingAppointment->starts_at;
                        $germanDate = $appointmentTime->locale('de')->isoFormat('dddd, [den] D. MMMM');

                        Log::info('🚨 Customer already has appointment at requested time', [
                            'call_id' => $callId,
                            'customer_id' => $call->customer_id,
                            'requested_time' => $requestedDate->format('Y-m-d H:i'),
                            'existing_appointment_id' => $existingAppointment->id,
                            'existing_appointment_time' => $appointmentTime->format('Y-m-d H:i')
                        ]);

                        return $this->responseFormatter->success([
                            'available' => false,
                            'has_existing_appointment' => true,
                            'existing_appointment_id' => $existingAppointment->id,
                            'message' => "Sie haben bereits einen Termin am {$germanDate} um {$appointmentTime->format('H:i')} Uhr. Möchten Sie diesen Termin umbuchen oder einen weiteren Termin vereinbaren?",
                            'requested_time' => $requestedDate->format('Y-m-d H:i'),
                            'existing_appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                            'alternatives' => []
                        ]);
                    }
                }

                // No existing appointment found - slot is truly available
                Log::info('✅ checkAvailability SUCCESS - Slot available', [
                    'call_id' => $callId,
                    'available' => true,
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'service' => $service->name ?? 'unknown',
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ]);

                return $this->responseFormatter->success([
                    'available' => true,
                    'message' => "Ja, {$requestedDate->format('H:i')} Uhr ist noch frei.",
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'alternatives' => []
                ]);
            }

            // LATENZ-OPTIMIERUNG: Alternative-Suche nur wenn Feature enabled
            // Voice-AI braucht <1s Response → Alternative-Suche (3s+) ist zu langsam!
            if (config('features.skip_alternatives_for_voice', true)) {
                Log::info('⚠️ checkAvailability - Slot NOT available (skip_alternatives enabled)', [
                    'call_id' => $callId,
                    'available' => false,
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'service' => $service->name ?? 'unknown',
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'skip_alternatives' => true
                ]);

                return $this->responseFormatter->success([
                    'available' => false,
                    'message' => "Dieser Termin ist leider nicht verfügbar. Welche Zeit würde Ihnen alternativ passen?",
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'alternatives' => [],
                    'suggest_user_alternative' => true
                ]);
            }

            // If not available, automatically find alternatives (SLOW - 3s+!)
            // SECURITY: Set tenant context for cache isolation
            // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
            $call = $call ?? $this->callLifecycle->findCallByRetellId($callId);
            $customerId = $call?->customer_id;

            $alternatives = $this->alternativeFinder
                ->setTenantContext($companyId, $branchId)
                ->findAlternatives(
                    $requestedDate,
                    $duration,
                    $service->calcom_event_type_id,
                    $customerId  // Pass customer ID to prevent offering conflicting times
                );

            // 🔧 FIX 2025-10-25: Bug #1 - Cache alternative dates for persistence across function calls
            // PROBLEM: Agent offers "08:30 am gleichen Tag" (actually 27.10), user says "ja", but
            //          book_appointment receives "morgen" → parsed as 26.10 (wrong!) → Cal.com rejects
            // SOLUTION: Cache each alternative's ACTUAL date with call_id + time as key
            if ($callId && isset($alternatives['alternatives'])) {
                foreach ($alternatives['alternatives'] as $alt) {
                    if (isset($alt['datetime']) && $alt['datetime'] instanceof \Carbon\Carbon) {
                        $altTime = $alt['datetime']->format('H:i');
                        $altDate = $alt['datetime']->format('Y-m-d');

                        $cacheKey = "call:{$callId}:alternative_date:{$altTime}";
                        Cache::put($cacheKey, $altDate, now()->addMinutes(30));

                        Log::info('📅 Alternative date cached for future booking', [
                            'call_id' => $callId,
                            'time' => $altTime,
                            'actual_date' => $altDate,
                            'cache_key' => $cacheKey,
                            'ttl_minutes' => 30
                        ]);
                    }
                }
            }

            Log::info('⚠️ checkAvailability - Slot NOT available (with alternatives)', [
                'call_id' => $callId,
                'available' => false,
                'requested_time' => $requestedDate->format('Y-m-d H:i'),
                'service' => $service->name ?? 'unknown',
                'alternatives_count' => count($alternatives['alternatives'] ?? []),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            return $this->responseFormatter->success([
                'available' => false,
                'message' => $alternatives['responseText'] ?? "Dieser Termin ist leider nicht verfügbar.",
                'requested_time' => $requestedDate->format('Y-m-d H:i'),
                'alternatives' => $this->formatAlternativesForRetell($alternatives['alternatives'] ?? [])
            ]);

        } catch (\Exception $e) {
            Log::error('❌ CRITICAL: Error checking availability', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString(),
                'call_id' => $callId,
                'params' => $params,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->responseFormatter->error('Fehler beim Prüfen der Verfügbarkeit');
        }
    }

    /**
     * Get alternative appointments when requested time is not available
     * Called when customer says: "Wann haben Sie denn Zeit?" or "Was wäre denn möglich?"
     */
    private function getAlternatives(array $params, ?string $callId)
    {
        try {
            $requestedDate = $this->dateTimeParser->parseDateTime($params);
            $duration = $params['duration'] ?? 60;
            $serviceId = $params['service_id'] ?? null;
            $serviceName = $params['service_name'] ?? $params['dienstleistung'] ?? null;
            $maxAlternatives = $params['max_alternatives'] ?? 3;

            // FEATURE: Branch-aware service selection for alternatives
            $callContext = $this->getCallContext($callId);

            if (!$callContext) {
                Log::error('Cannot get alternatives: Call context not found', [
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            // Get service with branch validation using ServiceSelectionService
            // Priority: service_id > service_name > default
            if ($serviceId) {
                $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
            } elseif ($serviceName) {
                $service = $this->serviceSelector->findServiceByName($serviceName, $companyId, $branchId);
            } else {
                $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
            }

            if (!$service || !$service->calcom_event_type_id) {
                Log::error('No active service with Cal.com event type found for alternatives', [
                    'service_id' => $serviceId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Service nicht verfügbar für diese Filiale');
            }

            Log::info('Using service for alternatives', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $service->calcom_event_type_id,
                'call_id' => $callId
            ]);

            // Find alternatives using our sophisticated finder
            // SECURITY: Set tenant context for cache isolation
            // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
            $call = $this->callLifecycle->findCallByRetellId($callId);
            $customerId = $call?->customer_id;

            $alternatives = $this->alternativeFinder
                ->setTenantContext($companyId, $branchId)
                ->findAlternatives(
                    $requestedDate,
                    $duration,
                    $service->calcom_event_type_id,
                    $customerId  // Pass customer ID to prevent offering conflicting times
                );

            // Format response for natural conversation
            $responseData = [
                'found' => !empty($alternatives['alternatives']),
                'message' => $alternatives['responseText'] ?? "Ich suche nach verfügbaren Terminen...",
                'alternatives' => $this->formatAlternativesForRetell($alternatives['alternatives'] ?? []),
                'original_request' => $requestedDate->format('Y-m-d H:i')
            ];

            // 🐛 FIX 2025-10-22: Corrected method name (was successResponse, should be responseFormatter->success)
            return $this->responseFormatter->success($responseData);

        } catch (\Exception $e) {
            Log::error('Error getting alternatives', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);
            return $this->responseFormatter->error('Fehler beim Suchen von Alternativen');
        }
    }

    /**
     * Book an appointment after customer confirms
     * Called when customer says: "Ja, 15 Uhr passt mir" or "Den nehme ich"
     */
    private function bookAppointment(array $params, ?string $callId)
    {
        // 🔍 DEBUG 2025-10-22: Enhanced logging to diagnose Call #634 issues
        Log::warning('🔷 bookAppointment START', [
            'call_id' => $callId,
            'params' => $params,
            'timestamp' => now()->toIso8601String()
        ]);

        try {
            // FEATURE: Branch-aware booking with strict validation
            // Get call context to ensure branch isolation
            $callContext = $this->getCallContext($callId);

            if (!$callContext) {
                Log::error('Cannot book appointment: Call context not found', [
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            // 🔧 FIX 2025-11-14: Get customer ID for alternatives filtering
            $call = $this->callLifecycle->findCallByRetellId($callId);
            $customerId = $call?->customer_id;

            // 🔧 FIX 2025-11-13: Map appointment_date/appointment_time to date/time for dateTimeParser
            // Different webhooks/agents may use different parameter names
            if (isset($params['appointment_date']) && !isset($params['date'])) {
                $params['date'] = $params['appointment_date'];
            }
            if (isset($params['appointment_time']) && !isset($params['time'])) {
                $params['time'] = $params['appointment_time'];
            }

            $appointmentTime = $this->dateTimeParser->parseDateTime($params);
            $duration = $params['duration'] ?? 60;
            $customerName = $params['customer_name'] ?? '';
            $customerEmail = $params['customer_email'] ?? '';
            $customerPhone = $params['customer_phone'] ?? '';
            $serviceId = $params['service_id'] ?? null;
            $serviceName = $params['service_name'] ?? $params['dienstleistung'] ?? null;
            $notes = $params['notes'] ?? '';

            // 🔥 NEW 2025-11-16: Customer Recognition - Support both preferred_staff_id and legacy mitarbeiter
            $preferredStaffId = $params['preferred_staff_id'] ?? null;
            $mitarbeiterName = $params['mitarbeiter'] ?? null;

            if ($preferredStaffId) {
                // Direct ID provided (new way from customer recognition)
                // Validate that staff belongs to same company
                $staffMember = \App\Models\Staff::where('id', $preferredStaffId)
                    ->where('company_id', $companyId)
                    ->first();

                if ($staffMember) {
                    Log::info('📌 Using preferred_staff_id from customer history', [
                        'staff_id' => $preferredStaffId,
                        'staff_name' => $staffMember->name,
                        'company_id' => $companyId,
                        'call_id' => $callId
                    ]);
                } else {
                    Log::warning('⚠️ preferred_staff_id invalid or not in company', [
                        'staff_id' => $preferredStaffId,
                        'company_id' => $companyId,
                        'call_id' => $callId
                    ]);
                    $preferredStaffId = null;  // Reset to null if invalid
                }
            } elseif ($mitarbeiterName) {
                // Legacy: Name-based mapping
                $preferredStaffId = $this->mapStaffNameToId($mitarbeiterName, $callId);
                Log::info('📌 Using mitarbeiter name mapping (legacy)', [
                    'mitarbeiter_name' => $mitarbeiterName,
                    'mapped_staff_id' => $preferredStaffId,
                    'call_id' => $callId
                ]);
            }

            // 🔧 FIX 2025-11-14: Convert voice transcription to valid email format
            // PROBLEM: Voice transcription sends spoken form like "Farbhandy at Gmail Punkt com"
            // SOLUTION: Convert voice patterns to proper email format before validation
            if ($customerEmail) {
                // Convert common voice transcription patterns
                $customerEmail = preg_replace('/ at /i', '@', $customerEmail);          // "at" → "@"
                $customerEmail = preg_replace('/ punkt /i', '.', $customerEmail);       // "punkt" → "."
                $customerEmail = preg_replace('/ dot /i', '.', $customerEmail);         // "dot" → "."
                $customerEmail = preg_replace('/ com$/i', '.com', $customerEmail);      // " com" → ".com"
                $customerEmail = preg_replace('/ de$/i', '.de', $customerEmail);        // " de" → ".de"
                $customerEmail = preg_replace('/\s+/', '', $customerEmail);             // Remove remaining spaces
                $customerEmail = strtolower(trim($customerEmail));                      // Normalize

                // Validate converted email
                if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('Invalid email format after voice-to-text conversion', [
                        'call_id' => $callId,
                        'raw_email' => $params['customer_email'] ?? '',
                        'converted_email' => $customerEmail,
                        'fallback' => 'booking@temp.de'
                    ]);
                    $customerEmail = ''; // Empty so fallback 'booking@temp.de' is used
                } else {
                    Log::info('Successfully converted voice email to valid format', [
                        'call_id' => $callId,
                        'raw_email' => $params['customer_email'] ?? '',
                        'converted_email' => $customerEmail
                    ]);
                }
            }

            // 🔧 FIX 2025-10-22 V131: Service Selection with Session Persistence
            // PROBLEM: bookAppointment was selecting service independently from check_availability
            // SOLUTION: Check cache first for pinned service_id, guarantees consistency
            // This ensures check_availability and book_appointment use the SAME service/event_type
            $service = null;
            $pinnedServiceId = $callId ? Cache::get("call:{$callId}:service_id") : null;

            if ($pinnedServiceId) {
                // Use pinned service from check_availability
                $service = $this->serviceSelector->findServiceById($pinnedServiceId, $companyId, $branchId);

                Log::info('📌 Using pinned service from call session', [
                    'call_id' => $callId,
                    'pinned_service_id' => $pinnedServiceId,
                    'service_id' => $service->id ?? null,
                    'service_name' => $service->name ?? null,
                    'event_type_id' => $service->calcom_event_type_id ?? null,
                    'source' => 'cache'
                ]);
            } else if ($serviceId) {
                // Explicit service_id provided in params (rare)
                $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
            } elseif ($serviceName) {
                // Service name provided - use intelligent matching
                $service = $this->serviceSelector->findServiceByName($serviceName, $companyId, $branchId);
            } else {
                // Fallback to default service selection with branch validation
                // SECURITY: No cross-branch bookings allowed
                $service = $this->serviceSelector->getDefaultService($companyId, $branchId);

                // Pin this service for subsequent calls in this session
                if ($service && $callId) {
                    Cache::put("call:{$callId}:service_id", $service->id, now()->addMinutes(30));
                    Cache::put("call:{$callId}:service_name", $service->name, now()->addMinutes(30));
                    Cache::put("call:{$callId}:event_type_id", $service->calcom_event_type_id, now()->addMinutes(30));

                    Log::info('📌 Service pinned to call session from bookAppointment', [
                        'call_id' => $callId,
                        'service_id' => $service->id,
                        'service_name' => $service->name,
                        'cache_key' => "call:{$callId}:service_id"
                    ]);
                }
            }

            if (!$service || !$service->calcom_event_type_id) {
                Log::error('No active service with Cal.com event type found for booking', [
                    'service_id' => $serviceId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Service nicht verfügbar für diese Filiale');
            }

            Log::info('Using service for booking', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $service->calcom_event_type_id,
                'call_id' => $callId
            ]);

            // 🔧 FIX 2025-11-14: Multi-Layer Race Condition Defense
            // LAYER 1: Prevention - Re-validate availability before booking
            // LAYER 2: Smart Recovery - Find new alternatives on race condition
            // LAYER 3: Quality Assurance - Comprehensive logging & metrics

            // LAYER 1: Check time since last availability check
            $lastCheckTime = Cache::get("call:{$callId}:last_availability_check");
            $timeSinceCheck = $lastCheckTime ? now()->diffInSeconds($lastCheckTime) : 999;

            if ($timeSinceCheck > 30) {
                Log::info('⏱️ Re-validating availability before booking (>30s since last check)', [
                    'call_id' => $callId,
                    'time_since_check' => $timeSinceCheck,
                    'requested_time' => $appointmentTime->format('Y-m-d H:i')
                ]);

                // Quick availability re-check for exact requested time
                try {
                    // 🔧 FIX 2025-11-16: Cal.com expects Y-m-d format, not ISO 8601
                    $reCheckResponse = $this->calcomService->getAvailableSlots(
                        $service->calcom_event_type_id,
                        $appointmentTime->copy()->startOfDay()->format('Y-m-d'),
                        $appointmentTime->copy()->endOfDay()->format('Y-m-d')
                    );

                    $reCheckSlots = $reCheckResponse['slots'] ?? [];
                    $requestedSlotAvailable = collect($reCheckSlots)->contains(function ($slot) use ($appointmentTime) {
                        return Carbon::parse($slot['time'])->equalTo($appointmentTime);
                    });

                    if (!$requestedSlotAvailable) {
                        Log::warning('⚠️ Slot no longer available - preventing booking attempt', [
                            'call_id' => $callId,
                            'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                            'time_since_check' => $timeSinceCheck
                        ]);

                        // Find new alternatives
                        // 🔧 FIX 2025-11-14: Correct parameter order for findAlternatives()
                        $alternatives = $this->alternativeFinder->findAlternatives(
                            $appointmentTime,                   // Carbon $desiredDateTime
                            $service->duration_minutes,         // int $durationMinutes
                            $service->calcom_event_type_id,    // int $eventTypeId
                            $customerId                         // ?int $customerId
                        );

                        return $this->responseFormatter->error(
                            'Dieser Termin wurde gerade vergeben. Ich habe neue Alternativen für Sie.',
                            [
                                'available' => false,
                                'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                                'reason' => 'slot_taken_during_conversation',
                                'alternatives' => $alternatives,
                                'message' => 'Leider wurde dieser Termin gerade vergeben. ' .
                                           $this->formatAlternatives($alternatives)
                            ]
                        );
                    }

                    Log::info('✅ Availability re-validated - proceeding with booking', [
                        'call_id' => $callId,
                        'requested_time' => $appointmentTime->format('Y-m-d H:i')
                    ]);

                } catch (\Exception $e) {
                    Log::warning('Re-check failed, proceeding with booking anyway', [
                        'call_id' => $callId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // LAYER 2: Booking attempt with smart retry
            $maxRetries = 1;
            $attempt = 0;
            $booking = null;
            $lastException = null;

            while ($attempt <= $maxRetries && !$booking) {
                $attempt++;

                try {
                    // Create booking via Cal.com
                    $booking = $this->calcomService->createBooking([
                        'eventTypeId' => $service->calcom_event_type_id,
                        'start' => $appointmentTime->toIso8601String(),
                        'name' => $customerName,
                        'email' => $customerEmail ?: 'booking@temp.de',
                        'phone' => $customerPhone,
                        'notes' => $notes,
                        'service_name' => $service->name,
                        'metadata' => [
                            'call_id' => $callId,
                            'booked_via' => 'retell_ai',
                            'attempt' => (string)$attempt,
                            'time_since_check' => (string)$timeSinceCheck
                        ]
                    ]);

                    break; // Success, exit retry loop

                } catch (\App\Exceptions\CalcomApiException $e) {
                    $lastException = $e;

                    // Check if this is a race condition error
                    if (str_contains($e->getMessage(), 'already has booking') ||
                        str_contains($e->getMessage(), 'not available')) {

                        Log::warning('🔄 Race condition detected - slot taken between check and booking', [
                            'call_id' => $callId,
                            'attempt' => $attempt,
                            'max_retries' => $maxRetries,
                            'time_since_check' => $timeSinceCheck,
                            'error' => $e->getMessage()
                        ]);

                        // LAYER 3: Smart recovery - find alternatives instead of just failing
                        if ($attempt > $maxRetries) {
                            Log::error('❌ Booking failed after retries - finding alternatives', [
                                'call_id' => $callId,
                                'attempts' => $attempt
                            ]);

                            // Find new alternatives instead of hard failure
                            try {
                                // 🔧 FIX 2025-11-14: Correct parameter order for findAlternatives()
                                $alternatives = $this->alternativeFinder->findAlternatives(
                                    $appointmentTime,                   // Carbon $desiredDateTime
                                    $service->duration_minutes,         // int $durationMinutes
                                    $service->calcom_event_type_id,    // int $eventTypeId
                                    $customerId                         // ?int $customerId
                                );

                                if (!empty($alternatives)) {
                                    // 🔧 FIX 2025-11-14: Return SUCCESS with alternatives, not ERROR
                                    // This allows agent to present alternatives instead of saying "technical problem"
                                    return $this->responseFormatter->success([
                                        'booked' => false,
                                        'available' => false,
                                        'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                                        'reason' => 'race_condition',
                                        'alternatives' => $alternatives,
                                        'message' => 'Leider ist dieser Termin nicht mehr verfügbar. ' .
                                                   $this->formatAlternatives($alternatives)
                                    ]);
                                }
                            } catch (\Exception $altException) {
                                Log::error('Failed to find alternatives after race condition', [
                                    'call_id' => $callId,
                                    'error' => $altException->getMessage()
                                ]);
                            }

                            throw $e;
                        }

                        // Simple retry on first attempt
                        Log::info('Retrying booking after race condition...');

                    } else {
                        // Different error, don't retry
                        throw $e;
                    }
                }
            }

            if ($booking->successful()) {
                $bookingData = $booking->json();
                $calcomBookingId = $bookingData['data']['id'] ?? $bookingData['id'] ?? null;

                // 🔥 PHASE 1 FIX: Create local appointment immediately after Cal.com success
                try {
                    // Get call record for customer resolution
                    $call = $this->callLifecycle->findCallByRetellId($callId);

                    if ($call) {
                        // Ensure customer exists or create from call context
                        $customer = $this->customerResolver->ensureCustomerFromCall($call, $customerName, $customerEmail);

                        // Create local appointment with full context
                        // FIX 2025-10-10: Use forceFill() because company_id/branch_id are guarded
                        $appointment = new Appointment();
                        $appointment->forceFill([
                            'calcom_v2_booking_id' => $calcomBookingId,
                            'external_id' => $calcomBookingId,
                            'customer_id' => $customer->id,
                            'company_id' => $customer->company_id,  // Use customer's company_id (guaranteed match!)
                            'branch_id' => $branchId,
                            'service_id' => $service->id,
                            'staff_id' => $preferredStaffId,  // 🔥 NEW: Customer Recognition - Preferred staff
                            'call_id' => $call->id,
                            'starts_at' => $appointmentTime,
                            'ends_at' => $appointmentTime->copy()->addMinutes($duration),
                            'status' => 'confirmed',
                            'source' => 'retell_phone',
                            'booking_type' => 'single',
                            'notes' => $notes,
                            'metadata' => json_encode([
                                'call_id' => $call->id,  // ✅ FIX 2025-10-11: For reschedule/cancel lookup
                                'retell_call_id' => $callId,  // ✅ FIX 2025-10-11: For Same-Call policy
                                'calcom_booking' => $bookingData,
                                'customer_name' => $customerName,
                                'customer_email' => $customerEmail,
                                'customer_phone' => $customerPhone,
                                'synced_at' => now()->toIso8601String(),
                                'sync_method' => 'immediate',
                                'created_at' => now()->toIso8601String()  // ✅ For Same-Call time validation
                            ])
                        ]);
                        $appointment->save();

                        Log::info('✅ Appointment created immediately after Cal.com booking', [
                            'appointment_id' => $appointment->id,
                            'calcom_booking_id' => $calcomBookingId,
                            'call_id' => $call->id,
                            'customer_id' => $customer->id,
                            'sync_method' => 'immediate'
                        ]);

                        return $this->responseFormatter->success([
                            'booked' => true,
                            'appointment_id' => $appointment->id,
                            'message' => "Perfekt! Ihr Termin am {$appointmentTime->format('d.m.')} um {$appointmentTime->format('H:i')} Uhr ist gebucht.",
                            'booking_id' => $calcomBookingId,
                            'appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                            'confirmation' => "Sie erhalten eine Bestätigung per SMS."
                        ]);
                    } else {
                        Log::warning('⚠️ Call not found for immediate appointment sync', [
                            'call_id' => $callId,
                            'calcom_booking_id' => $calcomBookingId
                        ]);

                        // Return partial success - Cal.com booking succeeded but no call context
                        return $this->responseFormatter->success([
                            'booked' => true,
                            'message' => "Ihr Termin am {$appointmentTime->format('d.m.')} um {$appointmentTime->format('H:i')} Uhr ist gebucht.",
                            'booking_id' => $calcomBookingId,
                            'appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                            'confirmation' => "Sie erhalten eine Bestätigung per E-Mail."
                        ]);
                    }
                } catch (\Exception $e) {
                    // 🔥 DEBUG 2025-11-13: Capture exception details
                    file_put_contents('/var/www/api-gateway/storage/logs/BOOKING_ERROR.txt',
                        "=== BOOKING ERROR at " . date('Y-m-d H:i:s') . " ===\n" .
                        "Cal.com Booking ID: " . $calcomBookingId . "\n" .
                        "Error: " . $e->getMessage() . "\n" .
                        "File: " . $e->getFile() . ":" . $e->getLine() . "\n" .
                        "Trace:\n" . $e->getTraceAsString() . "\n\n",
                        FILE_APPEND
                    );

                    Log::error('❌ CRITICAL: Failed to create local appointment after Cal.com success', [
                        'calcom_booking_id' => $calcomBookingId,
                        'call_id' => $callId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // 🚨 FIX: Return error instead of success to prevent silent failures
                    // Cal.com booking succeeded but local record creation failed
                    // This requires manual intervention or webhook sync
                    return $this->responseFormatter->error(
                        'Die Terminbuchung wurde im Kalender erstellt, aber es gab ein Problem beim Speichern. ' .
                        'Bitte kontaktieren Sie uns direkt zur Bestätigung. Booking-ID: ' . $calcomBookingId
                    );
                }
            }

            // 🔧 FIX 2025-10-22 V132: Automatic alternatives on booking failure (Backend Fallback)
            // PROBLEM: Agent verbalizes "Ich schaue nach Alternativen" but doesn't execute tool call (Call #634)
            // SOLUTION: Backend automatically provides alternatives when booking fails
            // This guarantees users ALWAYS get alternatives, even if agent forgets to call get_alternatives
            Log::warning('❌ Booking failed, automatically getting alternatives', [
                'call_id' => $callId,
                'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                'service_id' => $service->id ?? null,
                'service_name' => $service->name ?? null
            ]);

            try {
                // Automatically get alternatives (same parameters as agent would use)
                $alternativesParams = [
                    'date' => $params['date'] ?? null,
                    'time' => $params['time'] ?? null,
                    'duration' => $duration,
                    'service_id' => $service->id ?? null,
                    'max_alternatives' => 3
                ];

                $alternativesResult = $this->getAlternatives($alternativesParams, $callId);

                // getAlternatives returns WebhookResponseService response
                $resultData = $alternativesResult->getData(true);

                if (isset($resultData['success']) && $resultData['success'] === true) {
                    $alternativesData = $resultData['data'] ?? [];

                    if (!empty($alternativesData['found']) && !empty($alternativesData['alternatives'])) {
                        // Build natural language alternatives list
                        $alternativesList = array_map(function($alt) {
                            return $alt['description'] ?? $alt['readable_time'] ?? 'Termin';
                        }, $alternativesData['alternatives']);

                        Log::info('✅ Alternatives automatically provided', [
                            'call_id' => $callId,
                            'alternatives_count' => count($alternativesList),
                            'auto_fallback' => true
                        ]);

                        // Return error with embedded alternatives
                        return $this->responseFormatter->error(
                            'Der gewünschte Termin ist leider nicht verfügbar. ' .
                            'Ich habe folgende Alternativen gefunden: ' .
                            implode(', ', array_slice($alternativesList, 0, 3)) . '. ' .
                            'Welcher Termin passt Ihnen?'
                        );
                    }
                }

                // No alternatives found
                Log::warning('⚠️ No alternatives found for failed booking', [
                    'call_id' => $callId,
                    'requested_time' => $appointmentTime->format('Y-m-d H:i')
                ]);

                return $this->responseFormatter->error(
                    'Der gewünschte Termin ist nicht verfügbar und es wurden leider keine alternativen Zeiten gefunden. ' .
                    'Können Sie einen anderen Tag oder eine andere Uhrzeit versuchen?'
                );

            } catch (\Exception $altException) {
                // Even alternatives failed - return basic error
                Log::error('❌ Failed to get automatic alternatives', [
                    'call_id' => $callId,
                    'error' => $altException->getMessage()
                ]);

                return $this->responseFormatter->error('Buchung konnte nicht durchgeführt werden');
            }

        } catch (\Exception $e) {
            Log::error('Error booking appointment', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);
            return $this->responseFormatter->error('Fehler bei der Terminbuchung');
        }
    }

    /**
     * List available services
     * Called when customer asks: "Was bieten Sie an?" or "Welche Services haben Sie?"
     */
    private function listServices(array $params, ?string $callId)
    {
        try {
            // FEATURE: Branch-specific service selection (VULN-003 fix)
            // Get call context to determine company and branch
            $callContext = $this->getCallContext($callId);

            if (!$callContext) {
                Log::error('Cannot list services: Call context not found', [
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            // Get available services using ServiceSelectionService
            $services = $this->serviceSelector->getAvailableServices($companyId, $branchId);

            Log::info('Services filtered by branch context', [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'service_count' => $services->count(),
                'call_id' => $callId
            ]);

            if ($services->isEmpty()) {
                return $this->responseFormatter->error('Keine verfügbaren Services für diese Filiale');
            }

            // FEATURE: ASK-009 Auto-select when only one service available
            if (config('features.auto_service_select', false) && $services->count() === 1) {
                $service = $services->first();

                Log::info('Auto-selecting single available service', [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'call_id' => $callId
                ]);

                $message = "Ich buche Ihnen einen Termin für {$service->name}.";

                return $this->responseFormatter->success([
                    'auto_selected' => true,
                    'service' => [
                        'id' => $service->id,
                        'name' => $service->name,
                        'duration' => $service->duration,
                        'price' => $service->price,
                        'description' => $service->description
                    ],
                    'message' => $message,
                    'count' => 1
                ]);
            }

            // Standard behavior: List multiple services for manual selection
            $serviceList = $services->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'duration' => $service->duration,
                    'price' => $service->price,
                    'description' => $service->description
                ];
            });

            $message = "Wir bieten folgende Services an: ";
            $message .= $services->pluck('name')->join(', ');

            return $this->responseFormatter->success([
                'auto_selected' => false,
                'services' => $serviceList,
                'message' => $message,
                'count' => $services->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing services', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);
            return $this->responseFormatter->error('Fehler beim Abrufen der Services');
        }
    }

    /**
     * Parse date/time from various input formats
     *
     * @deprecated Use DateTimeParser service instead
     * @see App\Services\Retell\DateTimeParser::parseDateTime()
     */
    private function parseDateTime(array $params): Carbon
    {
        return $this->dateTimeParser->parseDateTime($params);
    }

    /**
     * Parse date string to MySQL DATE format (YYYY-MM-DD)
     * Handles: "heute", "morgen", "01.10.2025", "2025-10-01"
     *
     * @deprecated Use DateTimeParser service instead
     * @see App\Services\Retell\DateTimeParser::parseDateString()
     */
    private function parseDateString(?string $dateString): ?string
    {
        return $this->dateTimeParser->parseDateString($dateString);
    }

    /**
     * Check if a specific time is available in the slots
     * 🚨 CRITICAL FIX 2025-10-18: EXACT TIME ONLY - NO APPROXIMATIONS
     *
     * ISSUE: Previous 15-minute interval matching caused false-positive availability claims
     * when the exact requested time was NOT available, leading to overbooking.
     *
     * NEW RULE: Only accept EXACT time matches (13:00 == 13:00)
     * If user wants flexibility, they must explicitly say so.
     */
    private function isTimeAvailable(Carbon $requestedTime, array $slots): bool
    {
        $requestedDate = $requestedTime->format('Y-m-d');
        $requestedHourMin = $requestedTime->format('Y-m-d H:i');

        Log::info('🔍 VERBOSE: Checking exact time availability', [
            'requested_time' => $requestedHourMin,
            'requested_timezone' => $requestedTime->timezone->getName(),
            'requested_timestamp' => $requestedTime->timestamp,
            'total_slots' => count($slots),
            'slots_structure' => $this->debugSlotStructure($slots),
            'first_3_slots_raw' => array_slice($slots, 0, 3)
        ]);

        // FIX 2025-10-19: Handle Cal.com's flat array of slot objects
        // Cal.com returns: [{'time': '13:30', ...}, {'time': '14:30', ...}]
        // NOT: {'2025-10-20': ['13:30', '14:30']}

        foreach ($slots as $slot) {
            // Extract time from slot (could be string or array with 'time' key)
            if (is_array($slot) && isset($slot['time'])) {
                $slotTime = $slot['time'];
            } elseif (is_string($slot)) {
                $slotTime = $slot;
            } else {
                Log::debug('Skipping unrecognized slot format', ['slot' => $slot]);
                continue;
            }

            try {
                // Parse slot time - could be "13:15", "13:00 - 13:30", Unix timestamp, etc.
                $parsedSlotTime = Carbon::parse((string)$slotTime);

                // 🔧 CRITICAL FIX 2025-10-19: TIMEZONE CONVERSION!
                // Cal.com returns UTC timestamps (e.g., "2025-10-20T12:00:00.000Z")
                // User requests in Europe/Berlin timezone (e.g., "14:00")
                // 12:00 UTC == 14:00 Europe/Berlin (same moment!)
                // We MUST convert slots to Europe/Berlin before comparison
                $parsedSlotTime = $parsedSlotTime->setTimezone('Europe/Berlin');

                // 🔧 VERBOSE DEBUG: Log every slot parsing attempt
                Log::debug('🔬 SLOT PARSE ATTEMPT', [
                    'raw_slot_time' => $slotTime,
                    'parsed_datetime' => $parsedSlotTime->format('Y-m-d H:i:s'),
                    'parsed_timezone' => $parsedSlotTime->timezone->getName(),
                    'parsed_timestamp' => $parsedSlotTime->timestamp,
                ]);

                // FIX 2025-10-19: If slot is only time (not full datetime), use requested date
                // Check if the parsed time is on a default/epoch date (Carbon::parse adds epoch date for time-only strings)
                $slotStr = (string)$slotTime;

                // If slot is just a time string (no date separators like "-", "/", "."), apply requested date
                if (!preg_match('/[-\/\.]|\d{4}/', $slotStr)) {
                    // Only time provided, use requested date
                    $parsedSlotTime = $requestedTime->copy()->setTime(
                        $parsedSlotTime->hour,
                        $parsedSlotTime->minute,
                        $parsedSlotTime->second ?? 0
                    );

                    Log::debug('🔧 Applied requested date to time-only slot', [
                        'time_only_input' => $slotTime,
                        'combined_datetime' => $parsedSlotTime->format('Y-m-d H:i:s')
                    ]);
                }

                // 🔴 EXACT MATCH ONLY: 14:15 == 14:15
                $slotFormatted = $parsedSlotTime->format('Y-m-d H:i');

                // 🔧 VERBOSE DEBUG: Log every comparison
                Log::debug('🔬 SLOT COMPARISON', [
                    'requested' => $requestedHourMin,
                    'slot_formatted' => $slotFormatted,
                    'match' => $slotFormatted === $requestedHourMin,
                    'raw_slot' => $slotTime
                ]);

                if ($slotFormatted === $requestedHourMin) {
                    Log::info('✅ EXACT slot match FOUND', [
                        'requested' => $requestedHourMin,
                        'matched_slot' => $slotFormatted,
                        'raw_slot_time' => $slotTime,
                        'available' => true
                    ]);
                    return true;
                }
            } catch (\Exception $e) {
                Log::debug('Could not parse slot time', [
                    'slot_time' => $slotTime,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // 🔧 VERBOSE DEBUG: Show ALL slots that were checked but didn't match
        $allSlotTimes = array_map(function($s) {
            if (is_array($s) && isset($s['time'])) {
                try {
                    $parsed = Carbon::parse($s['time']);
                    return [
                        'raw' => $s['time'],
                        'parsed' => $parsed->format('Y-m-d H:i:s'),
                        'timezone' => $parsed->timezone->getName()
                    ];
                } catch (\Exception $e) {
                    return ['raw' => $s['time'], 'parse_error' => $e->getMessage()];
                }
            }
            return ['raw' => $s, 'type' => 'string'];
        }, $slots);

        Log::warning('❌ EXACT time NOT available - VERBOSE DEBUG', [
            'requested_time' => $requestedHourMin,
            'requested_timezone' => $requestedTime->timezone->getName(),
            'available_slots_count' => count($slots),
            'all_slot_times_parsed' => array_slice($allSlotTimes, 0, 15),  // First 15 with full details
            'issue' => 'Requested time not found in available slots - check timezone mismatch'
        ]);

        return false;
    }

    /**
     * Debug helper to understand slot structure
     */
    private function debugSlotStructure(array $slots): string
    {
        if (empty($slots)) {
            return 'empty';
        }

        $first = reset($slots);
        if (is_array($first)) {
            return count($first) . ' keys: ' . implode(', ', array_keys($first));
        } elseif (is_string($first)) {
            return 'flat strings, e.g.: ' . $first;
        } else {
            return 'unknown type: ' . gettype($first);
        }
    }

    /**
     * Format alternatives for Retell AI to speak naturally
     */
    private function formatAlternativesForRetell(array $alternatives): array
    {
        return array_map(function($alt) {
            return [
                'time' => $alt['datetime']->format('Y-m-d H:i'),
                'spoken' => $alt['description'],
                'available' => $alt['available'] ?? true,
                'type' => $alt['type'] ?? 'alternative'
            ];
        }, $alternatives);
    }

    /**
     * Handle unknown function calls
     */
    private function handleUnknownFunction(string $functionName, array $params, ?string $callId)
    {
        // 🔧 FIX 2025-10-23: Upgrade to CRITICAL logging - unknown functions = booking failures!
        Log::critical('🚨 UNKNOWN FUNCTION CALLED - THIS WILL FAIL!', [
            'function' => $functionName,
            'params' => $params,
            'call_id' => $callId,
            'registered_functions' => [
                'check_customer', 'parse_date', 'check_availability', 'book_appointment',
                'query_appointment', 'get_alternatives', 'list_services',
                'cancel_appointment', 'reschedule_appointment', 'request_callback', 'find_next_available'
            ],
            'hint' => 'Check if function name has version suffix (e.g., _v17) or typo',
            'impact' => 'If this is book_appointment, the booking WILL NOT BE CREATED!',
            'reference' => 'TESTCALL_ROOT_CAUSE_ANALYSIS_2025-10-23.md'
        ]);

        return $this->responseFormatter->error(
            "Function '$functionName' is not supported. " .
            "Supported functions: check_availability, book_appointment, query_appointment, etc. " .
            "Note: Version suffixes (e.g., _v17) are automatically stripped."
        );
    }

    // NOTE: Old helper methods removed - now using WebhookResponseService
    // for consistent response formatting across all Retell controllers

    /**
     * Handle the collect_appointment_data function call from Retell
     * This is the specific route that Retell AI calls
     *
     * Uses CollectAppointmentRequest for automatic validation and sanitization
     */
    public function collectAppointment(CollectAppointmentRequest $request)
    {
        $data = $request->all();

        // ENHANCED MONITORING FOR TEST CALL
        Log::info('📅 ===== COLLECT APPOINTMENT WEBHOOK =====', [
            'timestamp' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'method' => $request->method(),
            'path' => $request->path(),
            'raw_body' => LogSanitizer::sanitize($request->getContent()),
            'all_headers' => LogSanitizer::sanitizeHeaders($request->headers->all())
        ]);

        Log::info('📞 Collect appointment function called', [
            'data_structure' => array_keys($data),
            'has_args' => isset($data['args']),
            'args' => $data['args'] ?? null,
            'call_id_from_args' => isset($data['args']['call_id']) ? $data['args']['call_id'] : null
        ]);

        try {
            // Use validated and sanitized data from FormRequest
            // This provides XSS protection, email validation, and length limits
            $validatedData = $request->getAppointmentData();
            $args = $data['args'] ?? $data;

            // Extract validated data
            $datum = $validatedData['datum'];
            $uhrzeit = $validatedData['uhrzeit'];
            $name = $validatedData['name'];
            $dienstleistung = $validatedData['dienstleistung'];
            $email = $validatedData['email'];
            $mitarbeiter = $validatedData['mitarbeiter'] ?? null; // PHASE 2: Staff preference

            // 🔧 BUG FIX (Call 776): Auto-fill customer name if "Unbekannt" or empty
            // If agent didn't provide name but customer exists, use database name
            $callId = $args['call_id'] ?? null;
            if (($name === 'Unbekannt' || empty($name)) && $callId) {
                $call = $this->callLifecycle->findCallByRetellId($callId);
                if ($call && $call->customer_id) {
                    $customer = \App\Models\Customer::find($call->customer_id);
                    if ($customer && !empty($customer->name)) {
                        $originalName = $name;
                        $name = $customer->name;
                        Log::info('✅ Auto-filled customer name from database', [
                            'original_name' => $originalName,
                            'auto_filled_name' => $name,
                            'customer_id' => $customer->id,
                            'call_id' => $call->id
                        ]);
                    }
                }
            }

            // Fallback: Replace Retell placeholders if not resolved
            if ($datum === '{{current_date}}' || $datum === 'current_date') {
                $originalDatum = $datum;
                $datum = Carbon::today()->format('d.m.Y');
                Log::warning('🔧 Retell placeholder not replaced, using fallback', [
                    'original' => $originalDatum,
                    'replaced' => $datum,
                    'call_id' => $callId ?? 'unknown'
                ]);
            }

            // Extract additional fields not in FormRequest validation
            $callId = $args['call_id'] ?? null;
            $confirmBooking = $args['bestaetigung'] ?? $args['confirm_booking'] ?? null;

            // PHASE 2: Map mitarbeiter name to staff_id
            $preferredStaffId = null;
            if ($mitarbeiter) {
                $preferredStaffId = $this->mapStaffNameToId($mitarbeiter, $callId);

                if ($preferredStaffId) {
                    Log::info('📌 Staff preference detected and mapped', [
                        'mitarbeiter_name' => $mitarbeiter,
                        'staff_id' => $preferredStaffId,
                        'call_id' => $callId
                    ]);
                } else {
                    Log::warning('⚠️ Staff name provided but could not be mapped', [
                        'mitarbeiter_name' => $mitarbeiter,
                        'call_id' => $callId
                    ]);
                }
            }

            Log::info('📅 Collect appointment data extracted', [
                'datum' => $datum,
                'uhrzeit' => $uhrzeit,
                'name' => $name,
                'dienstleistung' => $dienstleistung,
                'mitarbeiter' => $mitarbeiter,
                'preferred_staff_id' => $preferredStaffId,
                'call_id' => $callId,
                'bestaetigung' => $confirmBooking
            ]);

            // Create or update call record to ensure it exists
            // OPTIMIZATION: Get call record ONCE and reuse throughout function
            $call = null;
            if ($callId) {
                // First check if we have a temporary call that needs upgrading
                $phoneNumber = $args['phone'] ?? $args['customer_phone'] ?? 'unknown';

                // Try to find existing call by call_id first
                $call = $this->callLifecycle->findCallByRetellId($callId);

                // If not found, look for most recent temporary call within last 10 minutes
                if (!$call) {
                    $tempCall = $this->callLifecycle->findRecentTemporaryCall();

                    if ($tempCall) {
                        // Upgrade temp call with real call_id and preserve company/phone data
                        $call = $this->callLifecycle->upgradeTemporaryCall($tempCall, $callId, [
                            'name' => $name ?: $tempCall->name,
                            'dienstleistung' => $dienstleistung ?: $tempCall->dienstleistung,
                            'datum_termin' => $this->parseDateString($datum) ?: $tempCall->datum_termin,
                            'uhrzeit_termin' => $uhrzeit ?: $tempCall->uhrzeit_termin,
                            'appointment_requested' => true,
                            'extracted_name' => $name,
                            'extracted_date' => $datum,
                            'extracted_time' => $uhrzeit,
                            'status' => 'in_progress',
                        ]);

                        Log::info('✅ Temporary call merged with real call_id', [
                            'old_id' => 'temp_*',
                            'new_call_id' => $callId,
                            'db_id' => $call->id,
                            'company_id' => $call->company_id,
                            'phone_number_id' => $call->phone_number_id
                        ]);
                    }
                }

                if ($call) {
                    // Update existing call with latest data
                    $call->update([
                        'name' => $name ?: $call->name,
                        'customer_name' => $name ?: $call->customer_name,  // 🔧 FIX: Set customer_name for reschedule
                        'dienstleistung' => $dienstleistung ?: $call->dienstleistung,
                        'datum_termin' => $this->parseDateString($datum) ?: $call->datum_termin,
                        'uhrzeit_termin' => $uhrzeit ?: $call->uhrzeit_termin,
                        'appointment_requested' => true,
                        'extracted_name' => $name,
                        'extracted_date' => $datum,
                        'extracted_time' => $uhrzeit,
                        'updated_at' => now()
                    ]);

                    Log::info('📞 Call record updated', [
                        'call_id' => $call->id,
                        'retell_call_id' => $callId,
                        'has_company' => $call->company_id ? 'yes' : 'no',
                        'has_phone_number' => $call->phone_number_id ? 'yes' : 'no'
                    ]);
                } else {
                    // Create new call if no existing or temp call found
                    // But try to get company_id from phone number or other context
                    $companyId = null;
                    $phoneNumberId = null;

                    // CRITICAL FIX: Get to_number from the call data to lookup phone number
                    $toNumber = null;
                    if (isset($data['call']['to_number'])) {
                        $toNumber = $data['call']['to_number'];
                    }

                    // Lookup phone number if we have to_number
                    if ($toNumber) {
                        // Clean the phone number - same logic as call_inbound
                        $cleanedNumber = preg_replace('/[^0-9+]/', '', $toNumber);

                        // Try exact match first
                        $phoneRecord = \App\Models\PhoneNumber::where('number', $cleanedNumber)->first();

                        // If no exact match, try partial match (last 10 digits)
                        if (!$phoneRecord) {
                            $phoneRecord = \App\Models\PhoneNumber::where('number', 'LIKE', '%' . substr($cleanedNumber, -10))
                                ->first();
                        }

                        if ($phoneRecord) {
                            $phoneNumberId = $phoneRecord->id;
                            $companyId = $phoneRecord->company_id;

                            Log::info('✅ Phone number found in collectAppointment', [
                                'to_number' => $toNumber,
                                'phone_number_id' => $phoneNumberId,
                                'company_id' => $companyId
                            ]);
                        } else {
                            Log::warning('⚠️ Phone number not found in collectAppointment', [
                                'to_number' => $toNumber,
                                'cleaned_number' => $cleanedNumber
                            ]);
                        }
                    }

                    // Fallback: Try to find phone number record from recent calls
                    if (!$companyId) {
                        $recentCall = $this->callLifecycle->findRecentCallWithCompany(30);

                        if ($recentCall) {
                            $companyId = $recentCall->company_id;
                            $phoneNumberId = $recentCall->phone_number_id;
                        }
                    }

                    $call = $this->callLifecycle->createCall([
                        'call_id' => $callId,
                        'from_number' => $phoneNumber,
                        'to_number' => $toNumber,  // WICHTIG: to_number speichern!
                        'status' => 'in_progress',
                    ], $companyId, $phoneNumberId);

                    // Update with appointment details
                    $call->update([
                        'name' => $name,
                        'dienstleistung' => $dienstleistung,
                        'datum_termin' => $this->parseDateString($datum),
                        'uhrzeit_termin' => $uhrzeit,
                        'appointment_requested' => true,
                        'extracted_name' => $name,
                        'extracted_date' => $datum,
                        'extracted_time' => $uhrzeit,
                    ]);

                    Log::info('📞 New call record created (no temp found)', [
                        'call_id' => $call->id,
                        'retell_call_id' => $callId,
                        'company_id' => $companyId,
                        'phone_number_id' => $phoneNumberId
                    ]);
                }

                Log::info('📞 Call record final state', [
                    'call_id' => $call->id,
                    'retell_call_id' => $callId,
                    'company_id' => $call->company_id,
                    'phone_number_id' => $call->phone_number_id
                ]);
            }

            // 🔧 FIX V84 (Call 872): Name Validation - Reject placeholder names
            // 🔧 FIX 2025-11-15: Only validate name when BOOKING (bestaetigung=true)
            // For availability checks (bestaetigung=false), name is NOT required
            if ($confirmBooking === true) {
                $placeholderNames = ['Unbekannt', 'Anonym', 'Anonymous', 'Unknown'];
                $isPlaceholder = empty($name) || in_array(trim($name), $placeholderNames);

                if ($isPlaceholder) {
                    Log::warning('⚠️ PROMPT-VIOLATION: Attempting to book without real customer name', [
                        'call_id' => $callId,
                        'name' => $name,
                        'violation_type' => 'missing_customer_name',
                        'datum' => $datum,
                        'uhrzeit' => $uhrzeit,
                        'bestaetigung' => $confirmBooking
                    ]);

                    return response()->json([
                        'success' => false,
                        'status' => 'missing_customer_name',
                        'message' => 'Bitte erfragen Sie zuerst den Namen des Kunden. Sagen Sie: "Darf ich Ihren Namen haben?"',
                        'prompt_violation' => true,
                        'bestaetigung_status' => 'error'
                    ], 200);
                }
            }

            // 🔧 FIX (Call 863): Required Fields Validation
            // Prevent agent from calling collect_appointment without date/time
            if (empty($datum) || empty($uhrzeit)) {
                Log::warning('⚠️ PROMPT-VIOLATION: Agent called collect_appointment without date/time', [
                    'call_id' => $callId,
                    'datum' => $datum,
                    'uhrzeit' => $uhrzeit,
                    'name' => $name
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'missing_required_fields',
                    'message' => 'Bitte fragen Sie nach Datum und Uhrzeit bevor Sie einen Termin prüfen. Sagen Sie: "Für welchen Tag und welche Uhrzeit möchten Sie den Termin?"',
                    'missing_fields' => [
                        'datum' => empty($datum),
                        'uhrzeit' => empty($uhrzeit)
                    ],
                    'bestaetigung_status' => 'error'
                ], 200);
            }

            // Parse the date and time using existing helper methods
            $appointmentDate = null;
            if ($datum && $uhrzeit) {
                // 🔧 FIX 2025-10-25: Bug #1 - Retrieve cached alternative date if available
                // PROBLEM: User selects alternative "08:30" from check_availability (actual date: 27.10)
                //          but datum is still "morgen" → parseDateString returns 26.10 (WRONG!)
                // SOLUTION: Check cache first for alternative dates from check_availability
                $cachedAlternativeDate = null;
                if ($callId) {
                    // Extract just the time (HH:MM format)
                    $timeOnly = strpos($uhrzeit, ':') !== false ? $uhrzeit : sprintf('%02d:00', intval($uhrzeit));
                    $cacheKey = "call:{$callId}:alternative_date:{$timeOnly}";
                    $cachedAlternativeDate = Cache::get($cacheKey);

                    if ($cachedAlternativeDate) {
                        Log::info('✅ Using cached alternative date instead of parsing datum', [
                            'call_id' => $callId,
                            'datum_input' => $datum,
                            'uhrzeit_input' => $uhrzeit,
                            'cached_date' => $cachedAlternativeDate,
                            'cache_key' => $cacheKey,
                            'reason' => 'Alternative date from check_availability preserved'
                        ]);
                    }
                }

                // Use cached alternative date OR fallback to parseDateString
                $parsedDateStr = $cachedAlternativeDate ?? $this->parseDateString($datum);

                if ($parsedDateStr) {
                    $appointmentDate = Carbon::parse($parsedDateStr);

                    // Parse time (14:00 or just 14)
                    if (strpos($uhrzeit, ':') !== false) {
                        list($hour, $minute) = explode(':', $uhrzeit);
                    } else {
                        $hour = intval($uhrzeit);
                        $minute = 0;
                    }
                    $appointmentDate->setTime($hour, $minute);

                    Log::info('✅ Date parsed successfully', [
                        'input_datum' => $datum,
                        'input_uhrzeit' => $uhrzeit,
                        'parsed_date' => $parsedDateStr,
                        'final_datetime' => $appointmentDate->format('Y-m-d H:i'),
                        'used_cached_alternative' => $cachedAlternativeDate !== null
                    ]);

                    // 🔧 FIX (Call 863): Past-Time-Validation
                    // Reject appointments in the past
                    $now = Carbon::now('Europe/Berlin');
                    if ($appointmentDate->isPast()) {
                        $diffHours = abs($appointmentDate->diffInHours($now, false));

                        Log::critical('🚨 PAST-TIME-BOOKING-ATTEMPT', [
                            'call_id' => $callId,
                            'requested' => $appointmentDate->format('Y-m-d H:i'),
                            'current_time' => $now->format('Y-m-d H:i'),
                            'diff_hours' => $diffHours,
                            'datum_input' => $datum,
                            'uhrzeit_input' => $uhrzeit
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'past_time',
                            'message' => 'Dieser Termin liegt in der Vergangenheit. Bitte wählen Sie einen zukünftigen Zeitpunkt. Sagen Sie: "Meinen Sie heute um ' . $appointmentDate->format('H:i') . ' Uhr oder morgen?"',
                            'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                            'current_time' => $now->format('Y-m-d H:i'),
                            'bestaetigung_status' => 'error'
                        ], 200);
                    }
                }
            }

            if (!$appointmentDate) {
                Log::error('❌ Date parsing failed', [
                    'datum' => $datum,
                    'uhrzeit' => $uhrzeit
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Entschuldigung, ich konnte das Datum nicht verstehen. Bitte nennen Sie es im Format "heute", "morgen" oder Tag.Monat.Jahr, zum Beispiel 01.10.2025',
                    'bestaetigung_status' => 'error'
                ], 200);
            }

            // Get company ID and branch ID from call - CRITICAL for consistent service selection
            // 🔧 FIX 2025-10-22: Extract BOTH company_id AND branch_id (was missing branch_id!)
            // PROBLEM: collectAppointment wasn't using branch filter, caused service mismatch
            $companyId = null;
            $branchId = null;

            if ($callId && $call) {
                // First try to get company_id directly from call (should now be set)
                if ($call && $call->company_id) {
                    $companyId = $call->company_id;
                    $branchId = $call->branch_id;  // ← FIX: Extract branch_id too!

                    Log::info('🎯 Got company and branch from call record', [
                        'call_id' => $call->id,
                        'company_id' => $companyId,
                        'branch_id' => $branchId  // ← FIX: Log branch_id
                    ]);
                }
                // Fallback to phone_number lookup if needed
                elseif ($call && $call->phone_number_id) {
                    $phoneNumber = \App\Models\PhoneNumber::find($call->phone_number_id);
                    if ($phoneNumber) {
                        $companyId = $phoneNumber->company_id;
                        $branchId = $phoneNumber->branch_id;  // ← FIX: Also get branch from phone
                    }

                    Log::info('🔍 Got company and branch from phone number', [
                        'phone_number_id' => $call->phone_number_id,
                        'company_id' => $companyId,
                        'branch_id' => $branchId  // ← FIX: Log branch_id
                    ]);
                }
            }

            // 🔧 FIX 2025-10-22: Service Selection with Session Persistence
            // STRATEGY: Check cache first, then fall back to default selection
            // This guarantees consistency: check_availability → collectAppointment use SAME service
            $service = null;
            $pinnedServiceId = $callId ? Cache::get("call:{$callId}:service_id") : null;

            if ($pinnedServiceId) {
                // Use pinned service from check_availability
                $service = $this->serviceSelector->findServiceById($pinnedServiceId, $companyId, $branchId);

                if ($service) {
                    Log::info('📌 Using pinned service from call session', [
                        'call_id' => $callId,
                        'pinned_service_id' => $pinnedServiceId,
                        'service_id' => $service->id,
                        'service_name' => $service->name,
                        'event_type_id' => $service->calcom_event_type_id,
                        'source' => 'cache'
                    ]);
                } else {
                    Log::warning('⚠️ Pinned service not accessible, using default', [
                        'call_id' => $callId,
                        'pinned_service_id' => $pinnedServiceId,
                        'company_id' => $companyId,
                        'branch_id' => $branchId
                    ]);
                }
            }

            // Fallback to default service selection if no pinned service
            if (!$service && $companyId) {
                // 🔧 BUG FIX #10 (2025-10-25): Use intelligent service matching when user provides service name
                // PROBLEM: getDefaultService() returned ID 41 (Damenhaarschnitt) alphabetically
                //          Even when user said "Herrenhaarschnitt" (ID 42)
                // SOLUTION: Use findServiceByName() with fuzzy matching when dienstleistung provided
                if ($dienstleistung) {
                    $service = $this->serviceSelector->findServiceByName($dienstleistung, $companyId, $branchId);

                    Log::info('🔍 Service matched by name (Bug #10 fix)', [
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'requested_service' => $dienstleistung,
                        'matched_service_id' => $service?->id,
                        'matched_service_name' => $service?->name,
                        'event_type_id' => $service?->calcom_event_type_id,
                        'source' => 'intelligent_matching'
                    ]);
                }

                // Fallback to default only if no service name provided OR matching failed
                if (!$service) {
                    $service = $this->serviceSelector->getDefaultService($companyId, $branchId);

                    Log::info('📋 Dynamic service selection for company (fallback)', [
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'service_id' => $service ? $service->id : null,
                        'service_name' => $service ? $service->name : null,
                        'event_type_id' => $service ? $service->calcom_event_type_id : null,
                        'is_default' => $service ? $service->is_default : false,
                        'priority' => $service ? $service->priority : null,
                        'source' => 'default_selection'
                    ]);
                }

                // Pin this service for subsequent calls in this session
                if ($service && $callId) {
                    Cache::put("call:{$callId}:service_id", $service->id, now()->addMinutes(30));
                    Cache::put("call:{$callId}:service_name", $service->name, now()->addMinutes(30));
                    Cache::put("call:{$callId}:event_type_id", $service->calcom_event_type_id, now()->addMinutes(30));

                    Log::info('📌 Service pinned for future calls in session', [
                        'call_id' => $callId,
                        'service_id' => $service->id,
                        'service_name' => $service->name,
                        'pinned_from' => $dienstleistung ? 'name_match' : 'default'
                    ]);
                }
            }

            // If no service found for company, use fallback logic
            if (!$service) {
                // Default to company 15 (AskProAI) if no company detected
                $fallbackCompanyId = $companyId ?: 15;
                $service = $this->serviceSelector->getDefaultService($fallbackCompanyId, null);

                Log::warning('⚠️ Using fallback service selection', [
                    'original_company_id' => $companyId,
                    'original_branch_id' => $branchId,
                    'fallback_company_id' => $fallbackCompanyId,
                    'service_id' => $service ? $service->id : null,
                    'service_name' => $service ? $service->name : null
                ]);
            }

            if (!$service) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service-Konfiguration fehlt'
                ], 200);
            }

            // 🔍 PRE-BOOKING DUPLICATE CHECK
            // OPTIMIZATION: Only check for duplicates when actually booking (not in "check only" mode)
            // This saves ~50-100ms per request when just checking availability
            $shouldCheckDuplicates = ($confirmBooking !== false); // Don't check if explicitly checking only

            if ($shouldCheckDuplicates && $call && $call->from_number) {
                $customer = \App\Models\Customer::where('phone', $call->from_number)
                    ->where('company_id', $companyId)
                    ->first();

                if ($customer) {
                    // Check for existing appointments at exact date/time
                    $existingAppointment = \App\Models\Appointment::where('customer_id', $customer->id)
                        ->whereDate('starts_at', $appointmentDate->format('Y-m-d'))
                        ->whereTime('starts_at', $appointmentDate->format('H:i:s'))
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->with(['service', 'staff'])
                        ->first();

                    if ($existingAppointment) {
                        Log::warning('⚠️ DUPLICATE BOOKING DETECTED - Customer already has appointment at this time', [
                            'call_id' => $call->id,
                            'customer_id' => $customer->id,
                            'customer_name' => $customer->name,
                            'existing_appointment_id' => $existingAppointment->id,
                            'requested_date' => $appointmentDate->format('Y-m-d'),
                            'requested_time' => $appointmentDate->format('H:i'),
                            'existing_starts_at' => $existingAppointment->starts_at->format('Y-m-d H:i')
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'duplicate_detected',
                            'message' => sprintf(
                                'Sie haben bereits einen Termin am %s um %s Uhr%s. Möchten Sie diesen Termin behalten, verschieben, oder einen zusätzlichen Termin buchen?',
                                $existingAppointment->starts_at->format('d.m.Y'),
                                $existingAppointment->starts_at->format('H:i'),
                                $existingAppointment->service ? ' für ' . $existingAppointment->service->name : ''
                            ),
                            'existing_appointment' => [
                                'id' => $existingAppointment->id,
                                'date' => $existingAppointment->starts_at->format('d.m.Y'),
                                'time' => $existingAppointment->starts_at->format('H:i'),
                                'datetime' => $existingAppointment->starts_at->toIso8601String(),
                                'service' => $existingAppointment->service?->name,
                                'staff' => $existingAppointment->staff?->name,
                                'status' => $existingAppointment->status
                            ],
                            'options' => [
                                'keep_existing' => 'Bestehenden Termin behalten',
                                'book_additional' => 'Zusätzlichen Termin buchen',
                                'reschedule' => 'Termin verschieben'
                            ],
                            'bestaetigung_status' => 'duplicate_confirmation_needed'
                        ], 200);
                    }

                    // Also check for appointments on same day (different time) - for context
                    $sameDayAppointments = \App\Models\Appointment::where('customer_id', $customer->id)
                        ->whereDate('starts_at', $appointmentDate->format('Y-m-d'))
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->count();

                    if ($sameDayAppointments > 0) {
                        Log::info('ℹ️ Customer has other appointments on same day', [
                            'customer_id' => $customer->id,
                            'requested_date' => $appointmentDate->format('Y-m-d'),
                            'requested_time' => $appointmentDate->format('H:i'),
                            'same_day_count' => $sameDayAppointments
                        ]);
                        // Continue with booking - just informational log
                    }
                }
            }

            // REAL Cal.com availability check
            try {
                // Use the actual date without year mapping
                $checkDate = $appointmentDate->copy();
                // Note: Removed year mapping - Cal.com should handle 2025 dates correctly

                // Get branchId from call if available
                $branchId = $call ? $call->branch_id : null;

                // STEP 1: First, check if the EXACT requested time is available in Cal.com
                // This is critical - we need to check the exact slot before searching for alternatives
                $calcomService = app(\App\Services\CalcomService::class);
                $exactTimeAvailable = false;

                try {
                    $slotsResponse = $calcomService->getAvailableSlots(
                        $service->calcom_event_type_id,
                        $appointmentDate->format('Y-m-d'),
                        $appointmentDate->format('Y-m-d'),
                        $service->company->calcom_team_id  // ← FIX 2025-10-15: teamId added
                    );

                    if ($slotsResponse->successful()) {
                        $slotsData = $slotsResponse->json();
                        $daySlots = $slotsData['data']['slots'][$appointmentDate->format('Y-m-d')] ?? [];

                        // Check if requested time exists in available slots
                        $requestedTimeStr = $appointmentDate->format('H:i');
                        foreach ($daySlots as $slot) {
                            $slotTime = Carbon::parse($slot['time']);
                            if ($slotTime->format('H:i') === $requestedTimeStr) {
                                $exactTimeAvailable = true;
                                Log::info('✅ Exact requested time IS available in Cal.com', [
                                    'requested' => $requestedTimeStr,
                                    'slot_found' => $slot['time']
                                ]);
                                break;
                            }
                        }

                        if (!$exactTimeAvailable) {
                            Log::info('❌ Exact requested time NOT available in Cal.com', [
                                'requested' => $requestedTimeStr,
                                'total_slots' => count($daySlots),
                                'available_times' => array_map(fn($s) => Carbon::parse($s['time'])->format('H:i'), $daySlots)
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to check exact time availability', [
                        'error' => $e->getMessage(),
                        'requested_time' => $appointmentDate->format('Y-m-d H:i')
                    ]);
                }

                // STEP 2: If exact time is NOT available, search for alternatives
                // OPTIMIZATION: Cache alternatives to avoid duplicate calls
                $alternatives = [];
                $alternativesChecked = false;
                if (!$exactTimeAvailable) {
                    Log::info('🔍 Exact time not available, searching for alternatives...');

                    // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
                    // Use customer if already loaded, otherwise try to get from call
                    $customerId = ($customer ?? null)?->id ?? $call?->customer_id;

                    // SECURITY: Set tenant context for cache isolation
                    $alternatives = $this->alternativeFinder
                        ->setTenantContext($companyId, $branchId)
                        ->findAlternatives(
                            $checkDate,
                            60, // duration in minutes
                            $service->calcom_event_type_id,
                            $customerId  // Pass customer ID to prevent offering conflicting times
                        );
                    $alternativesChecked = true;
                }

                // Track nearest alternative for potential later use
                $nearestAlternative = null;
                if (!empty($alternatives['alternatives'])) {
                    $requestedMinutes = $appointmentDate->hour * 60 + $appointmentDate->minute;
                    $minDifference = PHP_INT_MAX;

                    foreach ($alternatives['alternatives'] as $alt) {
                        $altDateTime = isset($alt['datetime']) && $alt['datetime'] instanceof Carbon
                            ? $alt['datetime']
                            : (isset($alt['datetime']) ? Carbon::parse($alt['datetime']) : null);

                        if ($altDateTime) {
                            $altMinutes = $altDateTime->hour * 60 + $altDateTime->minute;
                            $difference = abs($altMinutes - $requestedMinutes);
                            if ($difference < $minDifference) {
                                $minDifference = $difference;
                                $nearestAlternative = [
                                    'time' => $altDateTime,
                                    'description' => $alt['description'] ?? $altDateTime->format('H:i'),
                                    'difference_minutes' => $difference
                                ];
                            }
                        }
                    }
                }

                // Store booking details in database
                // OPTIMIZATION: Reuse $call from earlier
                if ($callId && $call) {
                    $call->booking_details = json_encode([
                        'date' => $datum,
                        'time' => $uhrzeit,
                        'customer_name' => $name,
                        'service' => $dienstleistung,
                        'exact_time_available' => $exactTimeAvailable,
                        'alternatives_found' => count($alternatives['alternatives'] ?? []),
                        'checked_at' => now()->toIso8601String()
                    ]);
                    $call->save();
                }

                // 🔧 V84 FIX: 2-STEP ENFORCEMENT - Default to CHECK-ONLY instead of AUTO-BOOK
                // This prevents direct booking without user confirmation
                // - confirmBooking = null/not set → CHECK-ONLY (default behavior - V84 change)
                // - confirmBooking = true → BOOK (explicit confirmation required)
                // - confirmBooking = false → CHECK-ONLY (explicit check only)
                $shouldBook = $exactTimeAvailable && ($confirmBooking === true);

                // 🐛 DEBUG: CRITICAL BUG INVESTIGATION (2025-10-23)
                // Tracking why book_appointment_v17 enters CHECK-ONLY branch instead of BOOKING
                Log::info('🎯 BOOKING DECISION DEBUG', [
                    'shouldBook' => $shouldBook,
                    'exactTimeAvailable' => $exactTimeAvailable,
                    'confirmBooking' => $confirmBooking,
                    'confirmBooking_type' => gettype($confirmBooking),
                    'confirmBooking_strict_true' => $confirmBooking === true,
                    'confirmBooking_loose_true' => $confirmBooking == true,
                    'confirmBooking_value_dump' => var_export($confirmBooking, true),
                    'call_id' => $callId,
                    'args_bestaetigung' => $args['bestaetigung'] ?? 'NOT_SET',
                    'request_bestaetigung' => $request->input('bestaetigung', 'NOT_SET'),
                ]);

                // Track prompt violations for monitoring
                if ($confirmBooking === null && $exactTimeAvailable) {
                    Log::warning('⚠️ PROMPT-VIOLATION: Missing bestaetigung parameter - defaulting to CHECK-ONLY', [
                        'call_id' => $callId,
                        'defaulting_to' => 'check_only',
                        'expected' => 'bestaetigung: false for STEP 1, bestaetigung: true for STEP 2',
                        'date' => $appointmentDate->format('Y-m-d'),
                        'time' => $appointmentDate->format('H:i')
                    ]);
                }

                if ($shouldBook) {
                    Log::info('✅ ENTERING BOOKING BLOCK - Will create appointment', [
                        'call_id' => $callId,
                        'requested_time' => $appointmentDate->format('Y-m-d H:i')
                    ]);
                    // Book the exact requested time (V84: ONLY with explicit confirmation)
                    Log::info('📅 Booking exact requested time (V84: 2-step confirmation)', [
                        'requested' => $appointmentDate->format('H:i'),
                        'exact_match' => true,
                        'confirmation_received' => $confirmBooking === true,
                        'workflow' => '2-step (bestaetigung: false → user confirms → bestaetigung: true)'
                    ]);

                    Log::info('🎯 Booking attempt', [
                        'exactTimeAvailable' => $exactTimeAvailable,
                        'confirmBooking' => $confirmBooking,
                        'appointmentDate' => $appointmentDate->format('Y-m-d H:i'),
                        'checkDate' => $checkDate->format('Y-m-d H:i'),
                        'usingAlternative' => (!$exactTimeAvailable && $nearestAlternative) ? true : false
                    ]);

                    // If time is available OR this is a confirmation to book an alternative
                    // Create booking in Cal.com
                    $calcomService = app(\App\Services\CalcomService::class);

                    try {
                        // 🔧 FIX V85 (Calls 874/875): DOUBLE-CHECK availability immediately before booking
                        // Problem: 14-second gap between initial check and booking allows slot to be taken
                        // Solution: Re-check availability right before createBooking() to prevent race condition
                        Log::info('🔍 V85: Double-checking availability before booking...', [
                            'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                            'reason' => 'Prevent race condition from initial check to booking'
                        ]);

                        $stillAvailable = false;
                        try {
                            $recheckResponse = $calcomService->getAvailableSlots(
                                $service->calcom_event_type_id,
                                $appointmentDate->format('Y-m-d'),
                                $appointmentDate->format('Y-m-d'),
                                $service->company->calcom_team_id  // ← FIX 2025-10-15: teamId added
                            );

                            if ($recheckResponse->successful()) {
                                $recheckData = $recheckResponse->json();
                                $recheckSlots = $recheckData['data']['slots'][$appointmentDate->format('Y-m-d')] ?? [];
                                $requestedTimeStr = $appointmentDate->format('H:i');

                                foreach ($recheckSlots as $slot) {
                                    $slotTime = Carbon::parse($slot['time']);
                                    if ($slotTime->format('H:i') === $requestedTimeStr) {
                                        $stillAvailable = true;
                                        Log::info('✅ V85: Slot STILL available - proceeding with booking', [
                                            'requested' => $requestedTimeStr,
                                            'verified_at' => now()->toIso8601String()
                                        ]);
                                        break;
                                    }
                                }

                                if (!$stillAvailable) {
                                    Log::warning('⚠️ V85: Slot NO LONGER available - offering alternatives', [
                                        'requested' => $requestedTimeStr,
                                        'reason' => 'Taken between initial check and booking attempt',
                                        'time_gap' => 'Race condition detected'
                                    ]);

                                    // Slot was taken in the meantime - find alternatives immediately
                                    if (!$alternativesChecked) {
                                        $customerId = $customer?->id ?? $call?->customer_id;
                                        $alternatives = $this->alternativeFinder
                                            ->setTenantContext($companyId, $branchId)
                                            ->findAlternatives(
                                                $appointmentDate,
                                                60,
                                                $service->calcom_event_type_id,
                                                $customerId
                                            );
                                        $alternativesChecked = true;
                                    }

                                    // Return alternatives instead of attempting booking
                                    $alternativesList = array_slice($alternatives['alternatives'] ?? [], 0, 2);
                                    if (!empty($alternativesList)) {
                                        return response()->json([
                                            'success' => false,
                                            'status' => 'slot_taken',
                                            'message' => "Der Termin um {$appointmentDate->format('H:i')} Uhr wurde gerade vergeben. Ich habe Alternativen gefunden:",
                                            'alternatives' => $alternativesList,
                                            'reason' => 'race_condition_detected'
                                        ], 200);
                                    } else {
                                        return response()->json([
                                            'success' => false,
                                            'status' => 'no_availability',
                                            'message' => "Der Termin um {$appointmentDate->format('H:i')} Uhr wurde gerade vergeben und leider sind keine Alternativen verfügbar.",
                                            'reason' => 'race_condition_detected'
                                        ], 200);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('V85: Double-check failed - proceeding with booking attempt', [
                                'error' => $e->getMessage(),
                                'fallback' => 'Will attempt booking and handle error if slot taken'
                            ]);
                            // Continue with booking attempt even if double-check fails
                        }

                        // Prepare booking data
                        // OPTIMIZATION: Reuse $call from earlier for email lookup
                        $currentCall = $call;

                        $args = $request->input('args', []);
                        $bookingData = [
                                'eventTypeId' => $service->calcom_event_type_id,
                                'start' => $appointmentDate->format('Y-m-d\TH:i:s'),
                                'name' => $name,  // Top-level for CalcomService
                                'email' => $this->dataValidator->getValidEmail($args, $currentCall),
                                'phone' => $this->dataValidator->getValidPhone($args, $currentCall),
                                'notes' => "Service: {$dienstleistung}. Gebucht über KI-Telefonassistent.",
                                'title' => "{$dienstleistung} - {$name}",  // 🔧 FIX: Title for bookingFieldsResponses
                                'service_name' => $dienstleistung,  // Fallback for title
                                'metadata' => [
                                    'call_id' => $callId,
                                    'service' => $dienstleistung
                                ],
                                'timeZone' => 'Europe/Berlin'
                            ];

                            $response = $calcomService->createBooking($bookingData);

                            if ($response->successful()) {
                                $booking = $response->json()['data'] ?? [];

                                // 🔧 PHASE 5.4 FIX: Create Appointment FIRST, then set booking_confirmed
                                // This ensures atomic transaction - booking_confirmed only after successful appointment creation
                                if ($callId && $call) {
                                    try {
                                        // Get email from booking data (same as Cal.com booking)
                                        $email = $bookingData['email'] ?? null;

                                        // Ensure customer exists
                                        $customer = $this->customerResolver->ensureCustomerFromCall($call, $name, $email);

                                        // Create appointment using AppointmentCreationService
                                        $appointmentService = app(AppointmentCreationService::class);

                                        $appointment = $appointmentService->createLocalRecord(
                                            customer: $customer,
                                            service: $service,
                                            bookingDetails: [
                                                'starts_at' => $appointmentDate->format('Y-m-d H:i:s'),
                                                'ends_at' => $appointmentDate->copy()->addMinutes($service->duration ?? 60)->format('Y-m-d H:i:s'),
                                                'service' => $dienstleistung,
                                                'customer_name' => $name,
                                                'date' => $datum,
                                                'time' => $uhrzeit,
                                                'duration_minutes' => $service->duration ?? 60,
                                                // PHASE 2: Staff preference for composite services
                                                'preferred_staff_id' => $preferredStaffId
                                            ],
                                            calcomBookingId: $booking['uid'] ?? null,
                                            call: $call,
                                            calcomBookingData: $booking  // Pass Cal.com booking data for staff assignment
                                        );

                                        // ✅ ATOMIC TRANSACTION: Only set booking_confirmed=true AFTER appointment created successfully
                                        $call->booking_confirmed = true;
                                        $call->booking_id = $booking['uid'] ?? null;
                                        $call->booking_details = json_encode([
                                            'confirmed_at' => now()->toIso8601String(),
                                            'calcom_booking' => $booking
                                        ]);
                                        $call->appointment_id = $appointment->id;
                                        $call->appointment_made = true;
                                        $call->save();

                                        Log::info('✅ Appointment record created from Cal.com booking', [
                                            'appointment_id' => $appointment->id,
                                            'call_id' => $call->id,
                                            'booking_id' => $booking['uid'] ?? null,
                                            'customer_id' => $customer->id,
                                            'customer' => $customer->name,
                                            'service' => $service->name,
                                            'starts_at' => $appointmentDate->format('Y-m-d H:i')
                                        ]);

                                    } catch (\Exception $e) {
                                        // ❌ CRITICAL: Appointment creation failed - Cal.com booking exists locally
                                        // Store booking details for manual recovery but keep booking_confirmed=false
                                        $call->booking_id = $booking['uid'] ?? null;
                                        $call->booking_details = json_encode([
                                            'confirmed_at' => now()->toIso8601String(),
                                            'calcom_booking' => $booking,
                                            'appointment_creation_failed' => true,
                                            'appointment_creation_error' => $e->getMessage()
                                        ]);
                                        $call->save();

                                        Log::error('❌ CRITICAL: Failed to create Appointment record after Cal.com booking', [
                                            'error' => $e->getMessage(),
                                            'call_id' => $call->id,
                                            'booking_id' => $booking['uid'] ?? null,
                                            'trace' => $e->getTraceAsString()
                                        ]);

                                        // 🚨 CREATE URGENT FAILSAFE CALLBACK
                                        // Customer's booking was created in Cal.com but we lost it locally
                                        // Staff must follow up immediately
                                        $this->createFailsafeCallback(
                                            $call,
                                            sprintf(
                                                'Cal.com Buchung erfolgreich (ID: %s), aber lokale Speicherung fehlgeschlagen. Termin: %s um %s',
                                                $booking['uid'] ?? 'unknown',
                                                $datum ?? 'unbekannt',
                                                $uhrzeit ?? 'unbekannt'
                                            ),
                                            'partial_booking',
                                            \App\Models\CallbackRequest::PRIORITY_URGENT,
                                            [
                                                'calcom_booking_id' => $booking['uid'] ?? null,
                                                'appointment_error' => $e->getMessage(),
                                                'requested_time' => sprintf('%s %s', $datum, $uhrzeit),
                                                'service_id' => $serviceId ?? null,
                                                'staff_id' => $staffId ?? null,
                                            ]
                                        );

                                        // Return error to user - Cal.com booking exists but local appointment failed
                                        return response()->json([
                                            'success' => false,
                                            'status' => 'partial_booking',
                                            'message' => "Die Buchung wurde erstellt, aber es gab ein Problem bei der Speicherung. Ein Mitarbeiter wird Sie in Kürze anrufen.",
                                            'error' => 'appointment_creation_failed'
                                        ], 500);
                                    }
                                }

                                // 📧 Get customer email for confirmation message
                                $customerEmail = $this->dataValidator->getValidEmail($args, $currentCall);
                                $emailConfirmationText = '';

                                if ($customerEmail && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                                    $emailConfirmationText = " Sie erhalten eine Bestätigungs-E-Mail an {$customerEmail}.";
                                } else {
                                    $emailConfirmationText = " Bitte beachten Sie, dass keine E-Mail-Bestätigung gesendet werden konnte.";
                                }

                                return response()->json([
                                    'success' => true,
                                    'status' => 'booked',
                                    'message' => "Perfekt! Ihr Termin am {$datum} um {$uhrzeit} wurde erfolgreich gebucht.{$emailConfirmationText}",
                                    'appointment_id' => $booking['uid'] ?? $booking['id'] ?? 'confirmed',
                                    'confirmation_email_sent' => !empty($customerEmail)
                                ], 200);
                            } else {
                                // Cal.com API returned an error - treat as unavailable and offer alternatives
                                $errorData = $response->json();
                                $errorCode = $errorData['error']['code'] ?? 'unknown';
                                $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
                                $errorDetails = $errorData['error']['details'] ?? '';

                                Log::error('Cal.com booking failed - searching for alternatives', [
                                    'status' => $response->status(),
                                    'error' => $errorData,
                                    'error_code' => $errorCode,
                                    'sent_data' => $bookingData,
                                    'call_id' => $callId
                                ]);

                                // Find alternative appointments
                                try {
                                    // OPTIMIZATION: Use cached alternatives if already checked
                                    if (!$alternativesChecked) {
                                        // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
                                        $customerId = $customer?->id ?? $call?->customer_id;

                                        // SECURITY: Set tenant context for cache isolation
                                        $alternatives = $this->alternativeFinder
                                            ->setTenantContext($companyId, $branchId)
                                            ->findAlternatives(
                                                $appointmentDate,
                                                60,
                                                $service->calcom_event_type_id,
                                                $customerId  // Pass customer ID to prevent offering conflicting times
                                            );
                                        $alternativesChecked = true;
                                    }

                                    $message = "Der Termin am {$datum} um {$uhrzeit} ist leider nicht verfügbar.";
                                    if (!empty($alternatives['responseText'])) {
                                        $message = $alternatives['responseText'];
                                    } elseif (!empty($alternatives['alternatives'])) {
                                        $message .= " Ich kann Ihnen folgende Alternativen anbieten: ";
                                        foreach ($alternatives['alternatives'] as $index => $alt) {
                                            $message .= ($index + 1) . ". " . $alt['description'] . " ";
                                        }
                                    }

                                    return response()->json([
                                        'success' => false,
                                        'status' => 'unavailable',
                                        'message' => $message,
                                        'alternatives' => array_map(function($alt) {
                                            return [
                                                'time' => $alt['datetime']->format('H:i'),
                                                'description' => $alt['description']
                                            ];
                                        }, $alternatives['alternatives'] ?? [])
                                    ], 200);
                                } catch (\Exception $e) {
                                    // Fallback if alternative search also fails
                                    Log::error('Failed to find alternatives after booking error', [
                                        'error' => $e->getMessage()
                                    ]);

                                    // 🚨 CREATE CALLBACK IF NO ALTERNATIVES AVAILABLE
                                    // Customer wanted a specific time, Cal.com failed, and alternatives failed too
                                    if ($call) {
                                        $this->createFailsafeCallback(
                                            $call,
                                            sprintf(
                                                'Verfügbarkeitsprüfung für %s um %s fehlgeschlagen. Weder Direktbuchung noch Alternativensuche möglich. Fehler: %s',
                                                $datum ?? 'unbekannt',
                                                $uhrzeit ?? 'unbekannt',
                                                $e->getMessage()
                                            ),
                                            'api_error',
                                            \App\Models\CallbackRequest::PRIORITY_HIGH,
                                            [
                                                'requested_time' => sprintf('%s %s', $datum ?? '', $uhrzeit ?? ''),
                                                'error_during' => 'alternative_search',
                                                'original_error' => $errorMessage ?? 'unknown',
                                                'service_id' => $serviceId ?? null,
                                            ]
                                        );
                                    }

                                    return response()->json([
                                        'success' => false,
                                        'status' => 'error',
                                        'message' => 'Entschuldigung, der Termin ist nicht verfügbar. Ein Mitarbeiter wird Sie anrufen und sich um Ihren Wunschtermin kümmern.'
                                    ], 200);
                                }
                            }
                    } catch (\Exception $e) {
                        Log::error('❌ Booking exception occurred', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'call_id' => $callId
                        ]);

                        // 🚨 CREATE CALLBACK FOR CRITICAL BOOKING ERRORS
                        // Unexpected exception during booking process
                        if ($call) {
                            $this->createFailsafeCallback(
                                $call,
                                sprintf(
                                    'Kritischer Fehler bei der Terminbuchung. Exception: %s',
                                    $e->getMessage()
                                ),
                                'exception',
                                \App\Models\CallbackRequest::PRIORITY_HIGH,
                                [
                                    'requested_time' => sprintf('%s %s', $datum ?? '', $uhrzeit ?? ''),
                                    'error_type' => 'booking_exception',
                                    'exception_message' => $e->getMessage(),
                                ]
                            );
                        }

                        return response()->json([
                            'success' => false,
                            'status' => 'error',
                            'message' => 'Es ist ein unerwarteter Fehler aufgetreten. Ein Mitarbeiter wird Sie bald anrufen um Ihnen zu helfen.'
                        ], 200);
                    }
                }

                // 🔧 V84 FIX: Handle CHECK-ONLY mode (STEP 1 of 2-step process)
                // If time IS available BUT no confirmation (STEP 1), ask user for confirmation
                elseif ($exactTimeAvailable && ($confirmBooking === false || $confirmBooking === null)) {
                    // 🐛 DEBUG: Detect if we're incorrectly entering CHECK-ONLY from book_appointment_v17
                    if ($confirmBooking === null) {
                        Log::error('⚠️ CRITICAL: ENTERING CHECK-ONLY BLOCK WITH confirmBooking=NULL', [
                            'call_id' => $callId,
                            'exactTimeAvailable' => $exactTimeAvailable,
                            'confirmBooking' => $confirmBooking,
                            'confirmBooking_type' => gettype($confirmBooking),
                            'reason' => 'This should NOT happen when book_appointment_v17 is called!',
                            'expected_bestaetigung' => 'true (boolean)',
                            'args_bestaetigung' => $args['bestaetigung'] ?? 'NOT_SET',
                            'request_bestaetigung' => $request->input('bestaetigung', 'NOT_SET'),
                        ]);
                    } else {
                        Log::info('✅ V84: STEP 1 - Time available, requesting user confirmation', [
                            'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                            'bestaetigung' => $confirmBooking,
                            'next_step' => 'Wait for user confirmation, then call with bestaetigung: true'
                        ]);
                    }

                    // Format German date/time for natural language
                    $germanDate = $appointmentDate->locale('de')->translatedFormat('l, d. F');
                    $germanTime = $appointmentDate->format('H:i');

                    return response()->json([
                        'success' => true,
                        'status' => 'available',
                        'message' => "Der Termin am {$germanDate} um {$germanTime} Uhr ist noch frei. Soll ich den Termin für Sie buchen?",
                        'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                        'awaiting_confirmation' => true,
                        'next_action' => 'Wait for user "Ja", then call collect_appointment_data with bestaetigung: true'
                    ], 200);
                }

                // If time is not available OR if explicitly checking only
                elseif (!$exactTimeAvailable || $confirmBooking === false) {
                    // AlternativeFinder now handles ALL fallback logic with Cal.com verification

                    // Check if we have verified alternatives
                    if (empty($alternatives['alternatives'])) {
                        Log::warning('❌ No alternatives available after Cal.com verification', [
                            'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                            'service_id' => $service->id,
                            'event_type_id' => $service->calcom_event_type_id,
                            'company_id' => $call->company_id ?? null,
                            'call_id' => $callId
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'no_availability',
                            'message' => "Ich habe die Verfügbarkeit erfolgreich geprüft. Leider sind für Ihren Wunschtermin und auch in den nächsten 14 Tagen keine freien Termine vorhanden. Das System funktioniert einwandfrei - es sind derzeit einfach alle Termine ausgebucht. Bitte rufen Sie zu einem späteren Zeitpunkt noch einmal an oder kontaktieren Sie uns direkt."
                        ], 200);
                    }

                    // Log successful alternative generation with verification status
                    Log::info('✅ Presenting Cal.com-verified alternatives to user', [
                        'count' => count($alternatives['alternatives']),
                        'times' => collect($alternatives['alternatives'])->pluck('datetime')->map->format('Y-m-d H:i')->toArray(),
                        'all_verified' => collect($alternatives['alternatives'])->every(fn($alt) => isset($alt['source']) && str_contains($alt['source'], 'calcom')),
                        'call_id' => $callId
                    ]);

                    // Build voice-optimized German message with natural conjunction
                    $alternativeDescriptions = collect($alternatives['alternatives'])
                        ->map(fn($alt) => $alt['description'])
                        ->join(' oder ');

                    $message = "Der Termin am {$datum} um {$uhrzeit} ist leider nicht verfügbar. " .
                              "Ich kann Ihnen folgende Alternativen anbieten: " .
                              $alternativeDescriptions . ". " .
                              "Welcher Termin würde Ihnen besser passen?";

                    return response()->json([
                        'success' => false,
                        'status' => 'unavailable',
                        'message' => $message,
                        'alternatives' => array_map(function($alt) {
                            return [
                                'time' => $alt['datetime']->format('H:i'),
                                'date' => $alt['datetime']->format('d.m.Y'),
                                'description' => $alt['description'],
                                'verified' => isset($alt['source']) && str_contains($alt['source'], 'calcom')
                            ];
                        }, $alternatives['alternatives'])
                    ], 200);
                }

            } catch (\Exception $e) {
                Log::error('🚨 ERROR checking Cal.com availability', [
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'date' => $appointmentDate->format('Y-m-d H:i'),
                    'service_id' => $service?->id,
                    'event_type_id' => $service?->calcom_event_type_id,
                    'trace' => $e->getTraceAsString()
                ]);

                // Fallback response
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ich kann die Verfügbarkeit momentan nicht prüfen. Bitte versuchen Sie es später noch einmal.',
                    'debug_error' => config('app.debug') ? $e->getMessage() : null
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('Error in collectAppointment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Ein Fehler ist aufgetreten'
            ], 200);
        }
    }

    /**
     * Check availability for a specific date
     * Called by Retell AI to verify available appointment slots
     */
    public function handleAvailabilityCheck(Request $request)
    {
        try {
            Log::info('🔍 CHECKPOINT A: handleAvailabilityCheck START');

            $data = $request->all();
            $args = $data['args'] ?? $data;

            Log::info('🔍 CHECKPOINT B: Data extracted', [
                'has_args' => isset($data['args']),
                'args' => $args
            ]);

            // ENHANCED MONITORING FOR TEST CALL
            Log::info('🔍 ===== AVAILABILITY CHECK WEBHOOK =====', [
                'timestamp' => now()->toIso8601String(),
                'ip' => $request->ip(),
                'forwarded_for' => $request->header('X-Forwarded-For'),
                'method' => $request->method(),
                'path' => $request->path(),
                'raw_body' => LogSanitizer::sanitize($request->getContent()),
                'parsed_data' => LogSanitizer::sanitize($data),
                'args' => LogSanitizer::sanitize($args),
                'service' => $args['service'] ?? $args['dienstleistung'] ?? null,
                'date' => $args['date'] ?? $args['datum'] ?? null,
                'time' => $args['time'] ?? $args['uhrzeit'] ?? null,
                'all_headers' => LogSanitizer::sanitizeHeaders($request->headers->all())
            ]);

            Log::info('📅 Checking availability', [
                'args' => $args
            ]);
        } catch (\Exception $e) {
            Log::error('🚨 Exception in handleAvailabilityCheck setup', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Fehler beim Prüfen der Verfügbarkeit',
                'debug' => $e->getMessage()
            ], 200);
        }

        try {
            // 🔧 FIX 2025-10-18: Support both 'date' (from Retell) and 'datum' (from internal)
            $datum = $args['datum'] ?? $args['date'] ?? null;
            $callId = $data['call']['call_id'] ?? $args['call_id'] ?? null;
            $serviceType = $args['service'] ?? $args['dienstleistung'] ?? null;

            // Parse German date format OR ISO 8601 format
            $checkDate = null;
            if ($datum) {
                // Check if it's ISO 8601 format (YYYY-MM-DD) first
                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $datum, $matches)) {
                    $year = intval($matches[1]);
                    $month = intval($matches[2]);
                    $day = intval($matches[3]);
                    $checkDate = Carbon::create($year, $month, $day);
                } elseif (preg_match('/(\d{1,2})\.(\d{1,2})\.?(\d{4})?/', $datum, $matches)) {
                    // German date format (DD.MM.YYYY or DD.MM)
                    $day = intval($matches[1]);
                    $month = intval($matches[2]);
                    $year = isset($matches[3]) ? intval($matches[3]) : Carbon::now()->year;

                    // If date is in the past, assume next year
                    $checkDate = Carbon::create($year, $month, $day);
                    if ($checkDate->isPast()) {
                        $checkDate->addYear();
                    }
                } else {
                    // Try to parse as relative date (e.g., "morgen", "übermorgen")
                    $checkDate = $this->parseRelativeDate($datum);
                }
            }

            if (!$checkDate) {
                $checkDate = Carbon::now()->addDay(); // Default to tomorrow
            }

            // Get company/service info
            $companyId = 1; // Default Friseur 1 (for testing with real Cal.com services)
            $branchId = null;
            if ($callId) {
                $call = $this->callLifecycle->findCallByRetellId($callId);
                if ($call) {
                    if ($call->company_id) {
                        $companyId = $call->company_id;
                        $branchId = $call->branch_id;
                    } elseif ($call->phone_number_id) {
                        $phoneNumber = \App\Models\PhoneNumber::find($call->phone_number_id);
                        $companyId = $phoneNumber ? $phoneNumber->company_id : $companyId;
                        $branchId = $phoneNumber ? $phoneNumber->branch_id : null;
                    }
                }
            }

            // Get appropriate service using ServiceSelectionService
            // Check if service name was provided in args
            $serviceName = $args['service'] ?? $args['dienstleistung'] ?? null;

            Log::info('🔍 Service Selection', [
                'service_name' => $serviceName,
                'company_id' => $companyId,
                'branch_id' => $branchId
            ]);

            if ($serviceName) {
                $service = $this->serviceSelector->findServiceByName($serviceName, $companyId, $branchId);
            } else {
                $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
            }

            Log::info('🔍 Service Found?', [
                'found' => $service ? 'YES' : 'NO',
                'service_id' => $service?->id ?? null,
                'service_name' => $service?->name ?? null
            ]);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Keine Dienste verfügbar',
                    'available_slots' => [],
                    'debug' => [
                        'requested_service' => $serviceName,
                        'company_id' => $companyId,
                        'branch_id' => $branchId
                    ]
                ], 200);
            }

            // Check Cal.com availability
            $calcom = app(\App\Services\CalcomService::class);
            // 🔧 FIX 2025-11-16: Cal.com expects Y-m-d format, not ISO 8601
            $startDateTime = $checkDate->copy()->startOfDay()->format('Y-m-d');
            $endDateTime = $checkDate->copy()->endOfDay()->format('Y-m-d');

            Log::info('🔍 Querying Cal.com for availability', [
                'event_type_id' => $service->calcom_event_type_id,
                'team_id' => $service->company->calcom_team_id,
                'start' => $startDateTime,
                'end' => $endDateTime
            ]);

            $response = $calcom->getAvailableSlots(
                $service->calcom_event_type_id,
                $startDateTime,
                $endDateTime,
                $service->company->calcom_team_id  // ← FIX 2025-10-15: teamId added
            );

            if ($response->successful()) {
                $data = $response->json();
                $slots = [];

                // Parse Cal.com response structure
                if (isset($data['data']['slots'])) {
                    foreach ($data['data']['slots'] as $date => $dateSlots) {
                        if (is_array($dateSlots)) {
                            foreach ($dateSlots as $slot) {
                                $slotTime = is_array($slot) && isset($slot['time']) ? $slot['time'] : $slot;
                                $time = Carbon::parse($slotTime)->setTimezone('Europe/Berlin');
                                $slots[] = $time->format('H:i');
                            }
                        }
                    }
                }

                // Sort slots
                sort($slots);

                if (count($slots) > 0) {
                    $slotsText = implode(' Uhr, ', array_slice($slots, 0, 5)) . ' Uhr';
                    return response()->json([
                        'success' => true,
                        'status' => 'available',
                        'message' => "Am {$checkDate->format('d.m.Y')} sind folgende Zeiten verfügbar: {$slotsText}",
                        'available_slots' => $slots,
                        'date' => $checkDate->format('Y-m-d'),
                        'formatted_date' => $checkDate->format('d.m.Y')
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'status' => 'unavailable',
                        'message' => "Am {$checkDate->format('d.m.Y')} sind leider keine Termine verfügbar. Möchten Sie einen anderen Tag probieren?",
                        'available_slots' => [],
                        'date' => $checkDate->format('Y-m-d'),
                        'formatted_date' => $checkDate->format('d.m.Y')
                    ], 200);
                }
            } else {
                Log::error('Cal.com API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Die Verfügbarkeit kann momentan nicht geprüft werden. Bitte versuchen Sie es später noch einmal.',
                    'available_slots' => []
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('❌ Error in checkAvailability', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'call_id' => $callId
            ]);

            // 🚨 CREATE CALLBACK FOR AVAILABILITY CHECK FAILURES
            // If we can't check availability, create a callback so staff can help
            $call = $callId ? $this->callLifecycle->findCallByRetellId($callId) : null;
            if ($call) {
                $this->createFailsafeCallback(
                    $call,
                    sprintf(
                        'Verfügbarkeitsprüfung fehlgeschlagen. Kunde möchte Termin am %s prüfen. Fehler: %s',
                        $checkDate?->format('d.m.Y') ?? 'unbekannt',
                        $e->getMessage()
                    ),
                    'api_error',
                    \App\Models\CallbackRequest::PRIORITY_NORMAL,
                    [
                        'requested_date' => $checkDate?->format('Y-m-d') ?? null,
                        'error_during' => 'availability_check',
                    ]
                );
            }

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Ein Fehler ist aufgetreten bei der Verfügbarkeitsprüfung.',
                'available_slots' => []
            ], 200);
        }
    }

    /**
     * Parse relative German date strings
     */
    private function parseRelativeDate($dateString)
    {
        $dateString = strtolower($dateString);

        $mappings = [
            'heute' => Carbon::today(),
            'morgen' => Carbon::tomorrow(),
            'übermorgen' => Carbon::today()->addDays(2),
            'montag' => Carbon::parse('next monday'),
            'dienstag' => Carbon::parse('next tuesday'),
            'mittwoch' => Carbon::parse('next wednesday'),
            'donnerstag' => Carbon::parse('next thursday'),
            'freitag' => Carbon::parse('next friday'),
            'samstag' => Carbon::parse('next saturday'),
            'sonntag' => Carbon::parse('next sunday'),
        ];

        return $mappings[$dateString] ?? null;
    }

    /**
     * Get valid email with fallback logic
     *
     * @deprecated Use CustomerDataValidator service instead
     * @see App\Services\Retell\CustomerDataValidator::getValidEmail()
     */
    private function getValidEmail($request, $call = null)
    {
        $args = $request->input('args', []);
        return $this->dataValidator->getValidEmail($args, $call);
    }

    /**
     * Get valid phone number with fallback logic
     *
     * @deprecated Use CustomerDataValidator service instead
     * @see App\Services\Retell\CustomerDataValidator::getValidPhone()
     */
    private function getValidPhone($request, $call = null)
    {
        $args = $request->input('args', []);
        return $this->dataValidator->getValidPhone($args, $call);
    }

    /**
     * Ensure customer exists for the call
     * Helper method to find or create customer before appointment creation
     *
     * @deprecated Use AppointmentCustomerResolver service instead
     * @see App\Services\Retell\AppointmentCustomerResolver::ensureCustomerFromCall()
     */
    private function ensureCustomerFromCall(Call $call, string $name, ?string $email): Customer
    {
        return $this->customerResolver->ensureCustomerFromCall($call, $name, $email);
    }

    /**
     * PHASE 2: Map staff name (from voice) to staff_id
     *
     * Maps natural language staff names to database staff IDs for Friseur 1 Agent
     * Supports partial matching and common variations (e.g., "Fabian" → "Fabian Spitzer")
     *
     * @param string $staffName The staff member name from voice (e.g., "Fabian", "bei Emma")
     * @param string|null $callId Optional call ID for logging context
     * @return string|null The staff_id if found, null otherwise
     */
    private function mapStaffNameToId(string $staffName, ?string $callId = null): ?string
    {
        // Clean the input - remove common prefixes from natural speech
        $cleaned = trim($staffName);
        $cleaned = preg_replace('/^(bei|mit|von|bei der|beim)\s+/i', '', $cleaned);
        $cleaned = strtolower(trim($cleaned));

        // Friseur 1 (Agent: agent_f1ce85d06a84afb989dfbb16a9) staff mapping
        $staffMapping = [
            'emma' => '010be4a7-3468-4243-bb0a-2223b8e5878c',
            'emma williams' => '010be4a7-3468-4243-bb0a-2223b8e5878c',

            'fabian' => '9f47fda1-977c-47aa-a87a-0e8cbeaeb119',
            'fabian spitzer' => '9f47fda1-977c-47aa-a87a-0e8cbeaeb119',

            'david' => 'c4a19739-4824-46b2-8a50-72b9ca23e013',
            'david martinez' => 'c4a19739-4824-46b2-8a50-72b9ca23e013',

            'michael' => 'ce3d932c-52d1-4c15-a7b9-686a29babf0a',
            'michael chen' => 'ce3d932c-52d1-4c15-a7b9-686a29babf0a',

            'sarah' => 'f9d4d054-1ccd-4b60-87b9-c9772d17c892',
            'sarah johnson' => 'f9d4d054-1ccd-4b60-87b9-c9772d17c892',
            'dr. sarah' => 'f9d4d054-1ccd-4b60-87b9-c9772d17c892',
            'dr sarah' => 'f9d4d054-1ccd-4b60-87b9-c9772d17c892',
        ];

        // Try exact match first
        if (isset($staffMapping[$cleaned])) {
            Log::info('✅ Staff name matched exactly', [
                'input' => $staffName,
                'cleaned' => $cleaned,
                'staff_id' => $staffMapping[$cleaned],
                'call_id' => $callId
            ]);
            return $staffMapping[$cleaned];
        }

        // Try partial match (for cases like "Fabian" when full name is "Fabian Spitzer")
        foreach ($staffMapping as $key => $staffId) {
            if (str_contains($key, $cleaned) || str_contains($cleaned, $key)) {
                Log::info('✅ Staff name matched partially', [
                    'input' => $staffName,
                    'cleaned' => $cleaned,
                    'matched_key' => $key,
                    'staff_id' => $staffId,
                    'call_id' => $callId
                ]);
                return $staffId;
            }
        }

        Log::warning('❌ Staff name could not be mapped', [
            'input' => $staffName,
            'cleaned' => $cleaned,
            'call_id' => $callId,
            'available_names' => array_keys($staffMapping)
        ]);

        return null;
    }

    /**
     * Handle cancellation attempt from Retell AI
     * Called when customer says: "Ich möchte stornieren" or "Cancel my appointment"
     *
     * Security: Anonymous callers → CallbackRequest instead of direct cancellation
     */
    private function handleCancellationAttempt(array $params, ?string $callId)
    {
        try {
            // 1. Get call context
            $callContext = $this->getCallContext($callId);
            if (!$callContext) {
                Log::error('Cannot cancel: Call context not found', ['call_id' => $callId]);
                return $this->responseFormatter->error('Anruf konnte nicht gefunden werden');
            }

            // Get call by internal ID from context
            $call = Call::find($callContext['call_id']);

            // 🔒 SECURITY: Anonymous callers → CallbackRequest for verification
            if ($call && ($call->from_number === 'anonymous' || in_array(strtolower($call->from_number ?? ''), ['anonymous', 'unknown', 'withheld', 'restricted', '']))) {
                return $this->createAnonymousCallbackRequest($call, $params, 'cancellation');
            }

            // 2. Find appointment
            $appointment = $this->findAppointmentFromCall($call, $params);

            if (!$appointment) {
                $dateStr = $params['appointment_date'] ?? $params['datum'] ?? 'dem gewünschten Datum';
                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => "Ich konnte keinen Termin am {$dateStr} finden. Könnten Sie das Datum noch einmal nennen?"
                ], 200);
            }

            // 3. Check policy
            $policyEngine = app(\App\Services\Policies\AppointmentPolicyEngine::class);
            $policyResult = $policyEngine->canCancel($appointment);

            // 4. If allowed: Cancel appointment
            if ($policyResult->allowed) {
                // Cancel in database
                $appointment->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $params['reason'] ?? 'Via Telefonassistent storniert'
                ]);

                // Track modification for quota/analytics
                \App\Models\AppointmentModification::create([
                    'appointment_id' => $appointment->id,
                    'customer_id' => $appointment->customer_id,
                    'company_id' => $appointment->company_id,
                    'modification_type' => 'cancel',
                    'within_policy' => true,
                    'fee_charged' => $policyResult->fee,
                    'reason' => $params['reason'] ?? null,
                    'modified_by_type' => 'System',
                    'metadata' => [
                        'call_id' => $callId,
                        'hours_notice' => $policyResult->details['hours_notice'] ?? null,
                        'policy_required' => $policyResult->details['required_hours'] ?? null,
                        'cancelled_via' => 'retell_ai'
                    ]
                ]);

                // Fire event for listeners (notifications, stats, etc.)
                event(new \App\Events\Appointments\AppointmentCancellationRequested(
                    appointment: $appointment->fresh(),
                    reason: $params['reason'] ?? 'Via Telefonassistent storniert',
                    customer: $appointment->customer,
                    fee: $policyResult->fee,
                    withinPolicy: true
                ));

                $feeMessage = $policyResult->fee > 0
                    ? " Es fällt eine Stornogebühr von {$policyResult->fee}€ an."
                    : "";

                Log::info('✅ Appointment cancelled via Retell AI', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $callId,
                    'fee' => $policyResult->fee,
                    'within_policy' => true
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'cancelled',
                    'message' => "Ihr Termin am {$appointment->starts_at->format('d.m.Y')} um {$appointment->starts_at->format('H:i')} Uhr wurde erfolgreich storniert.{$feeMessage}",
                    'fee' => $policyResult->fee,
                    'appointment_id' => $appointment->id
                ], 200);
            }

            // 5. If denied: Explain reason
            $details = $policyResult->details;

            // Build user-friendly German message based on reason
            if (str_contains($policyResult->reason, 'hours notice')) {
                $message = sprintf(
                    "Eine Stornierung ist leider nicht mehr möglich. Sie benötigen %d Stunden Vorlauf, aber Ihr Termin ist nur noch in %.0f Stunden.",
                    $details['required_hours'] ?? 24,
                    $details['hours_notice'] ?? 0
                );
                if (isset($details['fee_if_forced']) && $details['fee_if_forced'] > 0) {
                    $message .= sprintf(" Wenn Sie trotzdem stornieren möchten, fällt eine Gebühr von %.2f€ an.", $details['fee_if_forced']);
                }
                $reasonCode = 'deadline_missed';
            } elseif (str_contains($policyResult->reason, 'quota exceeded')) {
                $message = sprintf(
                    "Sie haben Ihr monatliches Storno-Limit bereits erreicht (%d/%d verwendet).",
                    $details['quota_used'] ?? 0,
                    $details['quota_max'] ?? 3
                );
                $reasonCode = 'quota_exceeded';
            } else {
                $message = $policyResult->reason ?? "Eine Stornierung ist derzeit nicht möglich.";
                $reasonCode = 'policy_violation';
            }

            // Fire policy violation event
            event(new \App\Events\Appointments\AppointmentPolicyViolation(
                appointment: $appointment,
                policyResult: $policyResult,
                attemptedAction: 'cancel',
                source: 'retell_ai'
            ));

            Log::warning('❌ Cancellation denied by policy', [
                'appointment_id' => $appointment->id,
                'call_id' => $callId,
                'reason' => $reasonCode,
                'details' => $details
            ]);

            return response()->json([
                'success' => false,
                'status' => 'denied',
                'message' => $message,
                'reason' => $reasonCode,
                'details' => $details
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error handling cancellation attempt', [
                'error' => $e->getMessage(),
                'call_id' => $callId,
                'trace' => $e->getTraceAsString()
            ]);

            // In testing, expose the actual error
            if (app()->environment('testing')) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut oder kontaktieren Sie uns direkt.',
                    'debug_error' => $e->getMessage(),
                    'debug_trace' => $e->getTraceAsString()
                ], 200);
            }

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut oder kontaktieren Sie uns direkt.'
            ], 200);
        }
    }

    /**
     * Handle reschedule attempt from Retell AI
     * Called when customer says: "Kann ich den Termin verschieben?" or "I need to reschedule"
     *
     * Security: Anonymous callers → CallbackRequest instead of direct reschedule
     */
    private function handleRescheduleAttempt(array $params, ?string $callId)
    {
        try {
            // 1. Get call context
            $callContext = $this->getCallContext($callId);
            if (!$callContext) {
                Log::error('Cannot reschedule: Call context not found', ['call_id' => $callId]);
                return $this->responseFormatter->error('Anruf konnte nicht gefunden werden');
            }

            $call = $this->callLifecycle->findCallByRetellId($callId);

            // 🔒 SECURITY: Anonymous callers → CallbackRequest for verification
            if ($call && ($call->from_number === 'anonymous' || in_array(strtolower($call->from_number ?? ''), ['anonymous', 'unknown', 'withheld', 'restricted', '']))) {
                return $this->createAnonymousCallbackRequest($call, $params, 'reschedule');
            }

            // 2. Find current appointment
            $oldDate = $params['old_date'] ?? $params['appointment_date'] ?? $params['datum'] ?? null;
            $appointment = $this->findAppointmentFromCall($call, ['appointment_date' => $oldDate]);

            if (!$appointment) {
                // Try listing all upcoming appointments for customer
                if ($call->customer_id) {
                    $upcomingAppointments = Appointment::where('customer_id', $call->customer_id)
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->where('starts_at', '>=', now())
                        ->orderBy('starts_at', 'asc')
                        ->limit(3)
                        ->get();

                    if ($upcomingAppointments->count() > 0) {
                        $appointments_list = $upcomingAppointments->map(function($apt) {
                            return $apt->starts_at->format('d.m.Y \u\m H:i \U\h\r');
                        })->join(', ');

                        return response()->json([
                            'success' => false,
                            'status' => 'multiple_found',
                            'message' => "Ich habe mehrere Termine für Sie gefunden: {$appointments_list}. Welchen möchten Sie verschieben?",
                            'appointments' => $upcomingAppointments->map(function($apt) {
                                return [
                                    'id' => $apt->id,
                                    'date' => $apt->starts_at->format('Y-m-d'),
                                    'time' => $apt->starts_at->format('H:i'),
                                    'formatted' => $apt->starts_at->format('d.m.Y H:i')
                                ];
                            })
                        ], 200);
                    }
                }

                $dateStr = $oldDate ?? 'dem gewünschten Datum';
                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => "Ich konnte keinen Termin am {$dateStr} finden. Könnten Sie das Datum noch einmal nennen?"
                ], 200);
            }

            // 3. Parse new date FIRST (before policy check)
            $newDate = $params['new_date'] ?? null;
            $newTime = $params['new_time'] ?? null;

            if (!$newDate || !$newTime) {
                return response()->json([
                    'success' => true,
                    'status' => 'ready_to_reschedule',
                    'message' => "Wann möchten Sie den Termin verschieben?",
                    'current_appointment' => [
                        'date' => $appointment->starts_at->format('d.m.Y'),
                        'time' => $appointment->starts_at->format('H:i')
                    ]
                ], 200);
            }

            // Parse new datetime
            $newDateParsed = $this->parseDateString($newDate);
            if (!$newDateParsed) {
                return response()->json([
                    'success' => false,
                    'status' => 'invalid_date',
                    'message' => "Das Datum konnte nicht verstanden werden. Bitte nennen Sie es im Format Tag.Monat.Jahr oder als 'heute', 'morgen'."
                ], 200);
            }

            // Add time to date
            if (strpos($newTime, ':') !== false) {
                list($hour, $minute) = explode(':', $newTime);
            } else {
                $hour = intval($newTime);
                $minute = 0;
            }
            $newDateTime = $newDateParsed->setTime($hour, $minute);

            // 6. Check availability for new slot
            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            // Try to use service name if provided, otherwise use default
            $serviceName = $params['service_name'] ?? $params['dienstleistung'] ?? null;
            if ($serviceName) {
                $service = $this->serviceSelector->findServiceByName($serviceName, $companyId, $branchId);
            } else {
                $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
            }

            if (!$service || !$service->calcom_event_type_id) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Service-Konfiguration fehlt. Bitte kontaktieren Sie uns direkt.'
                ], 200);
            }

            // Check if new time is available
            $calcomService = app(\App\Services\CalcomService::class);
            $slotsResponse = $calcomService->getAvailableSlots(
                $service->calcom_event_type_id,
                $newDateTime->format('Y-m-d'),
                $newDateTime->format('Y-m-d'),
                $service->company->calcom_team_id  // ← FIX 2025-10-15: teamId added
            );

            // 🔧 FIX 2025-10-18: Use isTimeAvailable() for consistent 15-minute matching
            // Previously only did exact time match, now uses same logic as collect_appointment_data
            $isAvailable = false;
            if ($slotsResponse->successful()) {
                $slots = $slotsResponse->json()['data']['slots'][$newDateTime->format('Y-m-d')] ?? [];
                $isAvailable = $this->isTimeAvailable($newDateTime, [$newDateTime->format('Y-m-d') => $slots]);
            }

            if (!$isAvailable) {
                // FIX 2025-11-16: Check if the conflict is with the customer's OWN appointment being rescheduled
                // If the customer wants to move Tuesday 9-10 to Tuesday 10-11, their own appointment blocks the check
                // We should allow this if there are no OTHER conflicts
                $customerId = $call?->customer_id ?? $appointment?->customer_id;

                if ($customerId && $appointment) {
                    // Check if customer has OTHER appointments at the requested time (excluding the one being moved)
                    $conflictingAppointments = Appointment::where('customer_id', $customerId)
                        ->where('id', '!=', $appointment->id)  // Exclude appointment being rescheduled
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->where(function($query) use ($newDateTime, $service) {
                            $endTime = $newDateTime->copy()->addMinutes($service->duration ?? 60);
                            $query->where(function($q) use ($newDateTime, $endTime) {
                                // Check for overlapping appointments
                                $q->whereBetween('starts_at', [$newDateTime, $endTime])
                                  ->orWhereBetween('ends_at', [$newDateTime, $endTime])
                                  ->orWhere(function($q2) use ($newDateTime, $endTime) {
                                      $q2->where('starts_at', '<=', $newDateTime)
                                         ->where('ends_at', '>=', $endTime);
                                  });
                            });
                        })
                        ->exists();

                    if (!$conflictingAppointments) {
                        Log::info('✅ Reschedule: No conflicts except own appointment, allowing reschedule', [
                            'appointment_id' => $appointment->id,
                            'new_time' => $newDateTime->format('Y-m-d H:i'),
                            'customer_id' => $customerId
                        ]);
                        // Set available to true - the "conflict" is just the customer's own appointment
                        $isAvailable = true;
                    }
                }

                // If still not available after checking conflicts, find alternatives
                if (!$isAvailable) {
                    $alternativeFinder = app(\App\Services\AppointmentAlternativeFinder::class);
                    $alternatives = $alternativeFinder
                        ->setTenantContext($companyId, $branchId)
                        ->findAlternatives($newDateTime, 60, $service->calcom_event_type_id, $customerId);

                    $message = "Der Termin am {$newDate} um {$newTime} Uhr ist leider nicht verfügbar.";
                    if (!empty($alternatives['responseText'])) {
                        $message = $alternatives['responseText'];
                    }

                    return response()->json([
                        'success' => false,
                        'status' => 'unavailable',
                    'message' => $message,
                    'alternatives' => array_map(function($alt) {
                        return [
                            'time' => $alt['datetime']->format('H:i'),
                            'description' => $alt['description']
                        ];
                    }, $alternatives['alternatives'] ?? [])
                ], 200);
            }

            // 🔧 FIX 2025-10-18: Add 2-STEP CONFIRMATION for reschedule (like collect_appointment_data)
            // STEP 1: If available but no confirmation yet → Ask for confirmation
            $confirmReschedule = $params['bestaetigung'] ?? $params['confirm_reschedule'] ?? null;

            if (!$confirmReschedule) {
                Log::info('✅ STEP 1 - Reschedule available, requesting user confirmation', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $callId,
                    'new_date' => $newDateTime->format('Y-m-d H:i'),
                    'old_date' => $appointment->starts_at->format('Y-m-d H:i')
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'ready_for_confirmation',
                    'message' => "Der Termin kann auf {$newDate} um {$newTime} Uhr verschoben werden. Ist das in Ordnung?",
                    'new_appointment' => [
                        'date' => $newDateTime->format('d.m.Y'),
                        'time' => $newDateTime->format('H:i')
                    ],
                    'next_action' => 'Wait for user "Ja", then call reschedule_appointment with bestaetigung: true'
                ], 200);
            }

            // STEP 2: User confirmed → Proceed with reschedule
            Log::info('✅ STEP 2 - Reschedule confirmed by user, executing now', [
                'appointment_id' => $appointment->id,
                'call_id' => $callId,
                'confirmation_received' => $confirmReschedule === true,
                'workflow' => '2-step (bestaetigung: false → user confirms → bestaetigung: true)'
            ]);

            // 5. ONLY NOW check policy (after we know slot is available)
            $policyEngine = app(\App\Services\Policies\AppointmentPolicyEngine::class);
            $policyResult = $policyEngine->canReschedule($appointment);

            if (!$policyResult->allowed) {
                $details = $policyResult->details;

                if (str_contains($policyResult->reason, 'hours notice')) {
                    $message = sprintf(
                        "Eine Umbuchung ist leider nicht mehr möglich. Sie benötigen %d Stunden Vorlauf, aber Ihr Termin ist nur noch in %.0f Stunden.",
                        $details['required_hours'] ?? 24,
                        $details['hours_notice'] ?? 0
                    );
                    $reasonCode = 'deadline_missed';
                } elseif (str_contains($policyResult->reason, 'rescheduled')) {
                    $message = sprintf(
                        "Dieser Termin wurde bereits %d Mal umgebucht (Maximum: %d). Eine weitere Umbuchung ist nicht möglich.",
                        $details['reschedule_count'] ?? 0,
                        $details['max_allowed'] ?? 2
                    );
                    $reasonCode = 'max_reschedules_reached';
                } else {
                    $message = $policyResult->reason ?? "Eine Umbuchung ist derzeit nicht möglich.";
                    $reasonCode = 'policy_violation';
                }

                // Fire policy violation event
                event(new \App\Events\Appointments\AppointmentPolicyViolation(
                    appointment: $appointment,
                    policyResult: $policyResult,
                    attemptedAction: 'reschedule',
                    source: 'retell_ai'
                ));

                Log::warning('❌ Reschedule denied by policy', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $callId,
                    'reason' => $reasonCode,
                    'details' => $details
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'denied',
                    'message' => $message,
                    'reason' => $reasonCode,
                    'details' => $details
                ], 200);
            }

            // 6. Perform reschedule
            $oldStartsAt = $appointment->starts_at->copy();

            // Update appointment
            $appointment->update([
                'starts_at' => $newDateTime,
                'ends_at' => $newDateTime->copy()->addMinutes($service->duration ?? 60),
                'updated_at' => now()
            ]);

            // Track modification
            \App\Models\AppointmentModification::create([
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'company_id' => $appointment->company_id,
                'modification_type' => 'reschedule',
                'within_policy' => true,
                'fee_charged' => $policyResult->fee,
                'reason' => $params['reason'] ?? null,
                'modified_by_type' => 'System',
                'metadata' => [
                    'call_id' => $callId,
                    'hours_notice' => $policyResult->details['hours_notice'] ?? null,
                    'original_time' => $oldStartsAt->toIso8601String(),
                    'new_time' => $newDateTime->toIso8601String(),
                    'rescheduled_via' => 'retell_ai'
                ]
            ]);

            // Fire event for listeners (notifications, stats, etc.)
            event(new \App\Events\Appointments\AppointmentRescheduled(
                appointment: $appointment->fresh(),
                oldStartTime: $oldStartsAt,
                newStartTime: $newDateTime,
                reason: $params['reason'] ?? null,
                fee: $policyResult->fee,
                withinPolicy: true
            ));

            $feeMessage = $policyResult->fee > 0
                ? " Es fällt eine Umbuchungsgebühr von {$policyResult->fee}€ an."
                : "";

            Log::info('✅ Appointment rescheduled via Retell AI', [
                'appointment_id' => $appointment->id,
                'call_id' => $callId,
                'old_time' => $oldStartsAt->toIso8601String(),
                'new_time' => $newDateTime->toIso8601String(),
                'fee' => $policyResult->fee
            ]);

            return response()->json([
                'success' => true,
                'status' => 'rescheduled',
                'message' => "Ihr Termin wurde erfolgreich umgebucht auf {$newDateTime->format('d.m.Y')} um {$newDateTime->format('H:i')} Uhr.{$feeMessage}",
                'fee' => $policyResult->fee,
                'appointment_id' => $appointment->id,
                'old_time' => $oldStartsAt->format('Y-m-d H:i'),
                'new_time' => $newDateTime->format('Y-m-d H:i')
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error handling reschedule attempt', [
                'error' => $e->getMessage(),
                'call_id' => $callId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut oder kontaktieren Sie uns direkt.'
            ], 200);
        }
    }

    /**
     * Find appointment from call and date information
     *
     * FIX 2025-10-10: Use DateTimeParser service instead of deprecated parseDateString()
     * Root cause: parseDateString() cannot parse German relative dates (heute, morgen)
     * Impact: reschedule_appointment and cancel_appointment failed to find appointments
     */
    private function findAppointmentFromCall(Call $call, array $data): ?Appointment
    {
        // Parse date
        $dateString = $data['appointment_date'] ?? $data['datum'] ?? null;

        // Strategy 0: SAME-CALL Detection (<5 minutes old)
        // If user just booked and wants to reschedule immediately without specifying date
        if (!$dateString || $dateString === 'heute' || $dateString === 'today') {
            $recentAppointment = Appointment::where('call_id', $call->id)
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->where('created_at', '>=', now()->subMinutes(5))  // Last 5 minutes
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recentAppointment) {
                Log::info('✅ Found SAME-CALL appointment (booked <5min ago)', [
                    'appointment_id' => $recentAppointment->id,
                    'created_at' => $recentAppointment->created_at->toIso8601String(),
                    'age_seconds' => $recentAppointment->created_at->diffInSeconds(now())
                ]);
                return $recentAppointment;
            }

            // FIX 2025-11-16: If no recent same-call appointment, find OLDEST upcoming appointment
            // This handles the case where user calls AGAIN to reschedule an existing appointment
            if ($call->customer_id) {
                $oldestUpcoming = Appointment::where('customer_id', $call->customer_id)
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                    ->where('starts_at', '>=', now())  // Only future appointments
                    ->orderBy('starts_at', 'asc')  // OLDEST first (not newest!)
                    ->first();

                if ($oldestUpcoming) {
                    Log::info('✅ Found OLDEST upcoming appointment (no date specified)', [
                        'appointment_id' => $oldestUpcoming->id,
                        'starts_at' => $oldestUpcoming->starts_at->toIso8601String(),
                        'customer_id' => $call->customer_id
                    ]);
                    return $oldestUpcoming;
                }
            }
        }

        if (!$dateString) {
            Log::warning('findAppointmentFromCall: No date provided', ['call_id' => $call->id]);
            return null;
        }

        // FIX: Use DateTimeParser service for German relative dates support
        $parsedDate = $this->dateTimeParser->parseDateString($dateString);
        if (!$parsedDate) {
            Log::warning('findAppointmentFromCall: Could not parse date', [
                'call_id' => $call->id,
                'date_string' => $dateString
            ]);
            return null;
        }

        $date = Carbon::parse($parsedDate);  // parseDateString returns YYYY-MM-DD format

        Log::info('🔍 Finding appointment', [
            'call_id' => $call->id,
            'customer_id' => $call->customer_id,
            'company_id' => $call->company_id,
            'date' => $date->toDateString(),
        ]);

        // Strategy 1: Try call_id first (same call booking)
        $appointment = Appointment::where('call_id', $call->id)
            ->whereDate('starts_at', $date)
            ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
            ->first();

        if ($appointment) {
            Log::info('✅ Found appointment via call_id', ['appointment_id' => $appointment->id]);
            return $appointment;
        }

        // Strategy 2: Try customer_id (cross-call, same customer)
        if ($call->customer_id) {
            $appointment = Appointment::where('customer_id', $call->customer_id)
                ->whereDate('starts_at', $date)
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($appointment) {
                Log::info('✅ Found appointment via customer_id', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $call->customer_id
                ]);
                return $appointment;
            }
        }

        // Strategy 3: Try phone number (if customer not linked yet)
        if ($call->from_number && !in_array($call->from_number, ['unknown', 'anonymous', null, ''])) {
            $customer = Customer::where('phone', $call->from_number)
                ->where('company_id', $call->company_id ?? 1)
                ->first();

            if ($customer) {
                $appointment = Appointment::where('customer_id', $customer->id)
                    ->whereDate('starts_at', $date)
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($appointment) {
                    Log::info('✅ Found appointment via phone number', [
                        'appointment_id' => $appointment->id,
                        'customer_id' => $customer->id,
                        'phone' => $call->from_number
                    ]);

                    // Auto-link customer to call for future lookups
                    $call->update(['customer_id' => $customer->id]);

                    return $appointment;
                }
            }
        }

        // 🔥 NEW Strategy 4: Try customer name (for anonymous callers)
        $customerName = $data['customer_name'] ?? $data['name'] ?? $data['kundename'] ?? $call->customer_name ?? null;
        if ($customerName && $call->company_id) {
            Log::info('🔍 Searching appointment by customer name (anonymous caller)', [
                'customer_name' => $customerName,
                'company_id' => $call->company_id,
                'date' => $date->toDateString()
            ]);

            // Find customer by name + company (fuzzy match)
            $customer = Customer::where('company_id', $call->company_id)
                ->where(function($query) use ($customerName) {
                    $query->where('name', 'LIKE', '%' . $customerName . '%')
                          ->orWhere('name', $customerName);
                })
                ->first();

            if ($customer) {
                $appointment = Appointment::where('customer_id', $customer->id)
                    ->whereDate('starts_at', $date)
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($appointment) {
                    Log::info('✅ Found appointment via customer name', [
                        'appointment_id' => $appointment->id,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'matched_with' => $customerName
                    ]);

                    // Auto-link customer to call for future lookups
                    if (!$call->customer_id) {
                        $call->update(['customer_id' => $customer->id]);
                    }

                    return $appointment;
                }
            }
        }

        // Strategy 5: FALLBACK - List ALL upcoming appointments for customer
        if ($call->customer_id) {
            $customerAppointments = Appointment::where('customer_id', $call->customer_id)
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->where('starts_at', '>=', now())  // Only future appointments
                ->orderBy('starts_at', 'asc')
                ->get();

            if ($customerAppointments->count() === 1) {
                // Only 1 appointment → automatically use it
                Log::info('✅ Found single upcoming appointment for customer (FALLBACK)', [
                    'appointment_id' => $customerAppointments->first()->id,
                    'customer_id' => $call->customer_id,
                    'starts_at' => $customerAppointments->first()->starts_at->toIso8601String()
                ]);
                return $customerAppointments->first();
            } elseif ($customerAppointments->count() > 1) {
                // Multiple appointments → need clarification (handled in handleRescheduleAttempt)
                Log::info('⚠️ Multiple appointments found, need clarification (FALLBACK)', [
                    'count' => $customerAppointments->count(),
                    'customer_id' => $call->customer_id,
                    'dates' => $customerAppointments->pluck('starts_at')->map(fn($dt) => $dt->format('Y-m-d H:i'))->toArray()
                ]);
                return null;  // Will be handled in handleRescheduleAttempt with appointment list
            }
        }

        Log::warning('❌ No appointment found', [
            'call_id' => $call->id,
            'customer_id' => $call->customer_id,
            'company_id' => $call->company_id,
            'from_number' => $call->from_number,
            'date' => $date->toDateString(),
        ]);

        return null;
    }

    /**
     * Create callback request for anonymous caller modifications
     *
     * Security: Anonymous callers cannot directly cancel/reschedule.
     * Instead, create a CallbackRequest for staff to handle within business hours.
     *
     * @param Call $call Current call
     * @param array $params Request parameters
     * @param string $action 'cancellation' or 'reschedule'
     * @return \Illuminate\Http\JsonResponse
     */
    private function createAnonymousCallbackRequest(Call $call, array $params, string $action): \Illuminate\Http\JsonResponse
    {
        try {
            $callbackRequest = \App\Models\CallbackRequest::create([
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'phone_number' => 'anonymous_' . time(),
                'customer_name' => $params['customer_name'] ?? $params['name'] ?? 'Anonymer Anrufer',
                'priority' => 'high',
                'status' => 'pending',
                'notes' => sprintf(
                    'Anonymer Anrufer möchte Termin %s. Datum: %s',
                    $action === 'cancellation' ? 'stornieren' : 'verschieben',
                    $params['old_date'] ?? $params['appointment_date'] ?? $params['datum'] ?? 'unbekannt'
                ),
                'metadata' => [
                    'call_id' => $call->retell_call_id,
                    'action_requested' => $action,
                    'appointment_date' => $params['old_date'] ?? $params['appointment_date'] ?? $params['datum'] ?? null,
                    'new_date' => $params['new_date'] ?? null,
                    'new_time' => $params['new_time'] ?? null,
                    'customer_name_provided' => $params['customer_name'] ?? $params['name'] ?? null,
                    'from_number' => 'anonymous',
                    'created_via' => 'retell_webhook_anonymous'
                ],
                'expires_at' => now()->addHours(24)
            ]);

            Log::info('📋 Anonymous caller callback request created', [
                'callback_request_id' => $callbackRequest->id,
                'action' => $action,
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id
            ]);

            $actionText = $action === 'cancellation' ? 'Stornierung' : 'Umbuchung';

            return response()->json([
                'success' => true,
                'status' => 'callback_queued',
                'message' => sprintf(
                    'Aus Sicherheitsgründen können wir %s nur mit übertragener Rufnummer durchführen. Wir haben Ihre Anfrage notiert und rufen Sie innerhalb der nächsten 2 Stunden zurück, um die %s zu bestätigen. Alternativ können Sie während unserer Geschäftszeiten direkt anrufen.',
                    $actionText,
                    $actionText
                ),
                'callback_request_id' => $callbackRequest->id,
                'estimated_callback_time' => '2 Stunden'
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Failed to create callback request for anonymous caller', [
                'error' => $e->getMessage(),
                'call_id' => $call->id ?? null,
                'action' => $action
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Es ist ein Fehler aufgetreten. Bitte rufen Sie direkt während unserer Geschäftszeiten an.'
            ], 200);
        }
    }

    /**
     * Handle callback request from customer
     *
     * Called when customer wants a callback instead of immediate booking
     *
     * @param array $params
     * @param string|null $callId
     * @return array
     */
    private function handleCallbackRequest(array $params, ?string $callId): array
    {
        try {
            Log::info('📞 Processing callback request', [
                'call_id' => $callId,
                'params' => $params,
            ]);

            // Get call context
            $call = $this->getCallRecord($callId);
            if (!$call) {
                return [
                    'success' => false,
                    'message' => 'Anrufkontext nicht gefunden'
                ];
            }

            // Prepare callback data
            $callbackData = [
                'customer_id' => $call->customer_id,
                'branch_id' => $call->company->branches()->first()?->id,
                'phone_number' => $params['phone_number'] ?? $call->customer?->phone ?? 'unknown',
                'customer_name' => $params['customer_name'] ?? $call->customer?->name ?? 'Unknown',
                'preferred_time_window' => $params['preferred_time_window'] ?? null,
                'priority' => $params['priority'] ?? 'normal',
                'notes' => $params['reason'] ?? $params['notes'] ?? null,
                'metadata' => [
                    'call_id' => $callId,
                    'retell_call_id' => $call->retell_call_id,
                    'requested_at' => now()->toIso8601String(),
                ],
            ];

            // Add service if specified
            if (!empty($params['service_name'])) {
                $service = Service::where('company_id', $call->company_id)
                    ->where('name', 'like', '%' . $params['service_name'] . '%')
                    ->first();

                if ($service) {
                    $callbackData['service_id'] = $service->id;
                }
            }

            // Create callback request via service
            $callbackService = app(\App\Services\Appointments\CallbackManagementService::class);
            $callback = $callbackService->createRequest($callbackData);

            Log::info('✅ Callback request created', [
                'callback_id' => $callback->id,
                'customer_name' => $callback->customer_name,
                'phone' => $callback->phone_number,
                'assigned_to' => $callback->assigned_to,
            ]);

            return [
                'success' => true,
                'callback_id' => $callback->id,
                'status' => $callback->status,
                'assigned_to' => $callback->assignedTo?->name ?? 'Wird zugewiesen',
                'priority' => $callback->priority,
                'message' => sprintf(
                    'Rückruf-Anfrage erfolgreich erstellt. %s',
                    $callback->assignedTo
                        ? "Zugewiesen an {$callback->assignedTo->name}."
                        : 'Wird automatisch zugewiesen.'
                ),
            ];

        } catch (\Exception $e) {
            Log::error('❌ Failed to create callback request', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'callback_creation_failed',
                'message' => 'Entschuldigung, Rückruf-Anfrage konnte nicht erstellt werden.',
            ];
        }
    }

    /**
     * Create failsafe callback for error scenarios
     *
     * Automatically creates a callback request when:
     * - Booking fails (Cal.com API error)
     * - Partial booking (Cal.com success but local appointment failed)
     * - Exception during call processing
     * - No availability found
     *
     * This ensures no customer request is lost due to technical issues.
     *
     * @param Call $call Current call record
     * @param string $reason Error description (for notes)
     * @param string $errorType 'partial_booking'|'api_error'|'exception'|'no_availability'
     * @param string $priority 'urgent'|'high'|'normal'
     * @param array $errorContext Additional error details
     * @return \App\Models\CallbackRequest|null Created callback or null if failed
     */
    private function createFailsafeCallback(
        Call $call,
        string $reason,
        string $errorType = 'exception',
        string $priority = 'high',
        array $errorContext = []
    ): ?\App\Models\CallbackRequest {
        try {
            Log::warning("⚠️ Creating failsafe callback for error scenario", [
                'error_type' => $errorType,
                'reason' => $reason,
                'call_id' => $call->id,
                'priority' => $priority,
            ]);

            $callbackService = app(\App\Services\Appointments\CallbackManagementService::class);

            // Prepare callback data
            $callbackData = [
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'phone_number' => $call->customer?->phone_number ?? $call->from_number ?? 'unknown',
                'customer_name' => $call->customer?->name ?? 'Telefonanruf',
                'priority' => $priority,
                'status' => \App\Models\CallbackRequest::STATUS_PENDING,
                'notes' => sprintf(
                    '[%s] %s\n\nKall-ID: %s\nZeit: %s',
                    strtoupper($errorType),
                    $reason,
                    $call->id,
                    now()->format('d.m.Y H:i')
                ),
                'metadata' => array_merge([
                    'error_type' => $errorType,
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'from_number' => $call->from_number,
                    'created_from' => 'failsafe_callback',
                    'created_at_iso' => now()->toIso8601String(),
                ], $errorContext),
            ];

            // Create callback request
            $callback = $callbackService->createRequest($callbackData);

            Log::info("✅ Failsafe callback created", [
                'callback_id' => $callback->id,
                'error_type' => $errorType,
                'priority' => $priority,
                'call_id' => $call->id,
                'assigned_to' => $callback->assigned_to,
            ]);

            return $callback;

        } catch (\Exception $e) {
            Log::error("❌ Failed to create failsafe callback", [
                'error' => $e->getMessage(),
                'error_type' => $errorType,
                'call_id' => $call->id,
                'trace' => $e->getTraceAsString(),
            ]);

            // Silently fail - don't throw, to prevent cascade errors
            return null;
        }
    }

    /**
     * Find next available appointment slot
     *
     * Called when customer asks for next available time
     *
     * @param array $params
     * @param string|null $callId
     * @return array
     */
    private function handleFindNextAvailable(array $params, ?string $callId): array
    {
        try {
            Log::info('🔍 Finding next available slot', [
                'call_id' => $callId,
                'params' => $params,
            ]);

            // Get call context
            $call = $this->getCallRecord($callId);
            if (!$call) {
                return [
                    'success' => false,
                    'message' => 'Anrufkontext nicht gefunden'
                ];
            }

            // Find service
            $service = null;
            if (!empty($params['service_name'])) {
                $service = Service::where('company_id', $call->company_id)
                    ->where('name', 'like', '%' . $params['service_name'] . '%')
                    ->first();
            } elseif (!empty($params['service_id'])) {
                $service = Service::where('company_id', $call->company_id)
                    ->where('id', $params['service_id'])
                    ->first();
            }

            if (!$service) {
                return [
                    'success' => false,
                    'message' => 'Service nicht gefunden'
                ];
            }

            // Parse start time
            $after = null;
            if (!empty($params['after'])) {
                try {
                    $after = Carbon::parse($params['after']);
                } catch (\Exception $e) {
                    Log::warning('⚠️ Invalid after date', [
                        'after' => $params['after'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Search for next available slot
            $finder = new \App\Services\Appointments\SmartAppointmentFinder($call->company);
            $searchDays = $params['search_days'] ?? 14;

            $nextSlot = $finder->findNextAvailable($service, $after, $searchDays);

            if (!$nextSlot) {
                Log::info('📅 No available slots found', [
                    'service_id' => $service->id,
                    'search_days' => $searchDays,
                ]);

                return [
                    'success' => false,
                    'message' => sprintf(
                        'Keine freien Termine für %s in den nächsten %d Tagen gefunden.',
                        $service->name,
                        $searchDays
                    ),
                ];
            }

            Log::info('✅ Found next available slot', [
                'service_id' => $service->id,
                'next_slot' => $nextSlot->toIso8601String(),
            ]);

            return [
                'success' => true,
                'service' => $service->name,
                'next_available' => $nextSlot->toIso8601String(),
                'formatted_date' => $nextSlot->locale('de')->isoFormat('dddd, D. MMMM YYYY'),
                'formatted_time' => $nextSlot->format('H:i'),
                'message' => sprintf(
                    'Der nächste freie Termin für %s ist am %s um %s Uhr.',
                    $service->name,
                    $nextSlot->locale('de')->isoFormat('dddd, D. MMMM'),
                    $nextSlot->format('H:i')
                ),
            ];

        } catch (\Exception $e) {
            Log::error('❌ Failed to find next available slot', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'finder_error',
                'message' => 'Entschuldigung, Verfügbarkeitssuche fehlgeschlagen.',
            ];
        }
    }

    /**
     * Query existing appointments for caller
     *
     * Security: Requires phone number verification
     * Anonymous callers are rejected for security reasons
     *
     * @param array $params Query parameters (date, service, etc.)
     * @param string|null $callId Retell call ID
     * @return array Response with appointment info or error
     */
    private function queryAppointment(array $params, ?string $callId)
    {
        try {
            Log::info('🔍 Query appointment function called', [
                'call_id' => $callId,
                'parameters' => $params
            ]);

            // Get call context
            $call = $this->callLifecycle->findCallByRetellId($callId);

            if (!$call) {
                Log::error('❌ Call not found for query', [
                    'retell_call_id' => $callId
                ]);

                return [
                    'success' => false,
                    'error' => 'call_not_found',
                    'message' => 'Anruf konnte nicht gefunden werden.'
                ];
            }

            // 🔒 SECURITY FIX 2025-10-20: REJECT anonymous callers
            // Anonymous callers cannot query existing appointments (no verification possible)
            if (!$call->from_number || strtolower($call->from_number) === 'anonymous') {
                Log::warning('🚨 SECURITY: Anonymous caller attempted to query appointments', [
                    'call_id' => $callId,
                    'from_number' => $call->from_number ?? 'NULL'
                ]);

                return [
                    'success' => false,
                    'error' => 'anonymous_caller',
                    'requires_phone_number' => true,
                    'message' => 'Ich kann Ihre Termine leider nicht abfragen, da Ihre Nummer unterdrückt ist. Bitte rufen Sie mit Ihrer normalen Nummer an oder geben Sie Ihren Namen an.'
                ];
            }

            // Use query service for secure appointment lookup
            $queryService = app(\App\Services\Retell\AppointmentQueryService::class);

            $criteria = [
                'appointment_date' => $params['appointment_date'] ?? $params['datum'] ?? null,
                'service_name' => $params['service_name'] ?? $params['dienstleistung'] ?? null
            ];

            $result = $queryService->findAppointments($call, $criteria);

            Log::info('✅ Query appointment completed', [
                'call_id' => $callId,
                'success' => $result['success'],
                'appointment_count' => $result['appointment_count'] ?? 0
            ]);

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('❌ Query appointment failed', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'query_error',
                'message' => 'Entschuldigung, ich konnte Ihren Termin nicht finden. Bitte versuchen Sie es erneut.'
            ], 200);
        }
    }

    /**
     * 🔒 NEW V85: Query appointment by customer name (for anonymous callers with hidden numbers)
     *
     * When a customer has a hidden/suppressed phone number (00000000), they cannot be
     * looked up via phone-based query_appointment(). Instead, they provide their name
     * and we look up appointments by name.
     *
     * This is the fallback function for anonymous callers.
     *
     * @param array $params ['customer_name', 'appointment_date' (optional), 'call_id']
     * @param string|null $callId Retell call ID
     * @return \Illuminate\Http\JsonResponse
     */
    private function queryAppointmentByName(array $params, ?string $callId)
    {
        try {
            Log::info('🔍 Query appointment by name function called (ANONYMOUS)', [
                'call_id' => $callId,
                'customer_name' => $params['customer_name'] ?? 'missing',
                'appointment_date' => $params['appointment_date'] ?? 'not specified'
            ]);

            // Get call context
            $call = $this->callLifecycle->findCallByRetellId($callId);

            if (!$call) {
                Log::error('❌ Call not found for query_appointment_by_name', [
                    'retell_call_id' => $callId
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'call_not_found',
                    'message' => 'Anruf konnte nicht gefunden werden.'
                ], 200);
            }

            // Validate customer_name parameter
            $customerName = $params['customer_name'] ?? $params['name'] ?? null;
            if (empty($customerName)) {
                Log::warning('⚠️ customer_name not provided to query_appointment_by_name', [
                    'call_id' => $callId,
                    'params' => array_keys($params)
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'invalid_params',
                    'message' => 'Der Name des Kunden ist erforderlich.'
                ], 200);
            }

            // Query appointments by customer name
            $query = Appointment::where('company_id', $call->company_id);

            // Add branch filter if available
            if ($call->branch_id) {
                $query->where('branch_id', $call->branch_id);
            }

            // Filter by customer name (case-insensitive)
            $query->whereRaw('LOWER(customer_name) = ?', [strtolower($customerName)])
                  ->where('status', '!=', 'cancelled')
                  ->orderBy('appointment_date', 'desc')
                  ->orderBy('appointment_time', 'desc');

            // Optional: filter by date
            if (!empty($params['appointment_date'])) {
                $date = $this->parseDateString($params['appointment_date']);
                if ($date) {
                    $query->whereDate('appointment_date', $date);
                }
            }

            $appointments = $query->get();

            // No appointments found
            if ($appointments->isEmpty()) {
                Log::info('ℹ️ No appointments found for anonymous caller', [
                    'call_id' => $callId,
                    'customer_name' => $customerName,
                    'company_id' => $call->company_id
                ]);

                return response()->json([
                    'success' => true,
                    'appointments' => [],
                    'message' => "Unter dem Namen {$customerName} wurde kein Termin gefunden."
                ], 200);
            }

            // Format appointments for response
            $formattedAppointments = $appointments->map(function ($appt) {
                return [
                    'id' => $appt->id,
                    'customer_name' => $appt->customer_name,
                    'appointment_date' => $appt->appointment_date->format('Y-m-d'),
                    'appointment_date_display' => $appt->appointment_date->format('d.m.Y'),
                    'appointment_date_day' => $appt->appointment_date->translatedFormat('l'), // Day name (Montag, etc)
                    'appointment_time' => $appt->appointment_time,
                    'service_name' => $appt->service?->name ?? 'Unbekannt',
                    'status' => $appt->status,
                    'duration_minutes' => $appt->duration_minutes ?? 60,
                    'notes' => $appt->notes ?? ''
                ];
            })->toArray();

            Log::info('✅ Query appointment by name completed', [
                'call_id' => $callId,
                'customer_name' => $customerName,
                'appointment_count' => count($formattedAppointments)
            ]);

            return response()->json([
                'success' => true,
                'appointments' => $formattedAppointments,
                'appointment_count' => count($formattedAppointments),
                'message' => count($formattedAppointments) === 1
                    ? "Ich habe einen Termin für {$customerName} gefunden."
                    : "Ich habe " . count($formattedAppointments) . " Termine für {$customerName} gefunden."
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Query appointment by name failed', [
                'call_id' => $callId,
                'customer_name' => $params['customer_name'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'query_error',
                'message' => 'Entschuldigung, ich konnte die Termine nicht finden. Bitte versuchen Sie es erneut.'
            ], 200);
        }
    }

    /**
     * 🔧 FIX 2025-10-18: Parse German dates using our DateTimeParser
     *
     * The Retell AI Agent was calculating dates INCORRECTLY (e.g., "nächste Woche Montag" → "27. Mai" instead of "20. Oktober")
     *
     * Solution: Create a backend function that the Agent MUST CALL to parse dates correctly
     * Instead of the Agent calculating dates with LLM logic, it now calls this function
     * which uses our proven DateTimeParser with correct Carbon date logic
     *
     * @param array $params Parameters: ['date_string' => 'nächste Woche Montag']
     * @param string|null $callId Call ID for logging
     * @return \Illuminate\Http\JsonResponse with parsed date in Y-m-d format
     */
    private function handleParseDate(array $params, ?string $callId): \Illuminate\Http\JsonResponse
    {
        try {
            $dateString = $params['date_string'] ?? $params['datum'] ?? null;

            if (!$dateString) {
                return response()->json([
                    'success' => false,
                    'error' => 'missing_date_string',
                    'message' => 'Bitte ein Datum angeben (z.B. "nächste Woche Montag", "heute", "morgen", "20.10.2025")'
                ], 200);
            }

            // Use our proven DateTimeParser
            $parser = new DateTimeParser();
            $parsedDate = $parser->parseDateString($dateString);

            if (!$parsedDate) {
                // Try parsing as simple day (montag, dienstag, etc.)
                $simpleParse = $parser->parseDateString(trim($dateString));
                if (!$simpleParse) {
                    return response()->json([
                        'success' => false,
                        'error' => 'invalid_date_format',
                        'message' => "Das Datum '{$dateString}' konnte nicht verstanden werden. Bitte nennen Sie es im Format: 'nächste Woche Montag', 'heute', 'morgen', oder '20.10.2025'."
                    ], 200);
                }
                $parsedDate = $simpleParse;
            }

            // Format for display
            $displayDate = Carbon::parse($parsedDate)->format('d.m.Y');
            $dayName = Carbon::parse($parsedDate)->format('l');

            Log::info('✅ Date parsed successfully via parse_date handler', [
                'input' => $dateString,
                'parsed_date' => $parsedDate,
                'display' => $displayDate,
                'day' => $dayName,
                'call_id' => $callId
            ]);

            // 🔧 FIX 2025-10-21: Add explicit instruction to trigger check_availability
            // PROBLEM: After parse_date success, agent goes silent instead of checking availability
            // SOLUTION: Include next_action instruction in response to guide LLM workflow
            return response()->json([
                'success' => true,
                'date' => $parsedDate,  // Y-m-d format for backend use
                'display_date' => $displayDate,  // For user confirmation
                'day_name' => $dayName,  // Day of week
                'next_action' => 'check_availability',  // Guide LLM to next step
                'instruction' => 'Sagen Sie dem Kunden: "Einen Moment, ich prüfe die Verfügbarkeit..." und rufen Sie SOFORT check_availability() auf mit dem Datum ' . $displayDate . ' und der genannten Uhrzeit.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Date parsing failed', [
                'input' => $params['date_string'] ?? null,
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'parsing_error',
                'message' => 'Entschuldigung, es gab einen Fehler beim Parsen des Datums.'
            ], 200);
        }
    }

    /**
     * 🚀 V17: Check Availability Wrapper (bestaetigung=false)
     *
     * Wrapper for collectAppointment that forces bestaetigung=false
     * Used by explicit function nodes to ensure reliable tool calling
     *
     * POST /api/retell/v17/check-availability
     *
     * 🐛 FIX (2025-10-23): Properly inject bestaetigung into args array
     * Matching fix for bookAppointmentV17 - ensures consistency
     */
    public function checkAvailabilityV17(CollectAppointmentRequest $request)
    {
        Log::info('🔍 V17: Check Availability (bestaetigung=false)', [
            'call_id' => $request->input('call.call_id'),
            'params' => $request->except(['call']),
            'original_args_bestaetigung' => $request->input('args.bestaetigung', 'NOT_SET')
        ]);

        // 🔧 FIX 2025-10-25: Inject both call_id AND bestaetigung into args
        // Retell doesn't provide call_id as dynamic variable, so we extract from call.call_id
        $data = $request->all();
        $args = $data['args'] ?? [];
        $args['bestaetigung'] = false;  // Type-safe boolean false
        $args['call_id'] = $request->input('call.call_id');  // Extract from call object
        $data['args'] = $args;

        // Replace request data with modified args
        $request->replace($data);

        Log::info('🔧 V17: Injected bestaetigung=false and call_id into args', [
            'args_bestaetigung' => $request->input('args.bestaetigung'),
            'args_bestaetigung_type' => gettype($request->input('args.bestaetigung')),
            'args_call_id' => $request->input('args.call_id'),
            'verification' => $request->input('args.bestaetigung') === false ? 'CORRECT' : 'FAILED'
        ]);

        // Call the main collectAppointment method
        return $this->collectAppointment($request);
    }

    /**
     * 🚀 V17: Book Appointment Wrapper (bestaetigung=true)
     *
     * Wrapper for collectAppointment that forces bestaetigung=true
     * Used by explicit function nodes to ensure reliable tool calling
     *
     * POST /api/retell/v17/book-appointment
     *
     * 🐛 FIX (2025-10-23): Properly inject bestaetigung into args array
     * Previous bug: merge(['bestaetigung' => true]) only set top-level, but
     * collectAppointment extracts from $args['bestaetigung'], causing NULL value
     */
    public function bookAppointmentV17(CollectAppointmentRequest $request)
    {
        Log::info('✅ V17: Book Appointment (bestaetigung=true)', [
            'call_id' => $request->input('call.call_id'),
            'params' => $request->except(['call']),
            'original_args_bestaetigung' => $request->input('args.bestaetigung', 'NOT_SET')
        ]);

        // 🔧 FIX 2025-10-25: Inject both call_id AND bestaetigung into args
        // Retell doesn't provide call_id as dynamic variable, so we extract from call.call_id
        // collectAppointment extracts: $confirmBooking = $args['bestaetigung'] ?? null;
        $data = $request->all();
        $args = $data['args'] ?? [];
        $args['bestaetigung'] = true;  // Type-safe boolean true
        $args['call_id'] = $request->input('call.call_id');  // Extract from call object
        $data['args'] = $args;

        // Replace request data with modified args
        $request->replace($data);

        Log::info('🔧 V17: Injected bestaetigung=true and call_id into args', [
            'args_bestaetigung' => $request->input('args.bestaetigung'),
            'args_bestaetigung_type' => gettype($request->input('args.bestaetigung')),
            'args_call_id' => $request->input('args.call_id'),
            'verification' => $request->input('args.bestaetigung') === true ? 'CORRECT' : 'FAILED'
        ]);

        // Call the main collectAppointment method
        return $this->collectAppointment($request);
    }

    /**
     * 🚀 V4: Initialize Call Wrapper
     *
     * Wrapper that injects call_id for initialize_call function
     * Used by Conversation Flow V4 for customer identification
     *
     * POST /api/retell/initialize-call-v4
     */
    public function initializeCallV4(Request $request)
    {
        $callId = $request->input('call.call_id');

        Log::info('🔍 V4: Initialize Call', [
            'call_id' => $callId,
            'params' => $request->except(['call'])
        ]);

        // 🔧 V4: Inject call_id into args
        $data = $request->all();
        $args = $data['args'] ?? [];
        $args['call_id'] = $callId;
        $data['args'] = $args;
        $request->replace($data);

        Log::info('🔧 V4: Injected call_id into args', [
            'args_call_id' => $request->input('args.call_id')
        ]);

        // Call private initializeCall method
        return $this->initializeCall($args, $callId);
    }

    /**
     * 🚀 V4: Get Customer Appointments Wrapper
     *
     * Wrapper that injects call_id for get_customer_appointments function
     * Used by Conversation Flow V4 for listing customer appointments
     *
     * POST /api/retell/get-appointments-v4
     */
    public function getCustomerAppointmentsV4(Request $request)
    {
        $callId = $request->input('call.call_id');
        $customerName = $request->input('args.customer_name');

        Log::info('📋 V4: Get Customer Appointments', [
            'call_id' => $callId,
            'customer_name' => $customerName,
            'params' => $request->except(['call'])
        ]);

        // 🔧 V4: Inject call_id into args
        $data = $request->all();
        $args = $data['args'] ?? [];
        $args['call_id'] = $callId;
        $data['args'] = $args;
        $request->replace($data);

        Log::info('🔧 V4: Injected call_id into args', [
            'args_call_id' => $request->input('args.call_id')
        ]);

        try {
            // Get call context
            $context = $this->getCallContext($callId);

            if (!$context) {
                Log::error('❌ Failed to get call context', ['call_id' => $callId]);
                return $this->responseFormatter->success([
                    'success' => false,
                    'error' => 'context_not_found',
                    'message' => 'Ich konnte Ihre Daten nicht laden. Bitte versuchen Sie es erneut.'
                ]);
            }

            // Get customer appointments
            $customerId = $context['customer_id'] ?? null;
            $companyId = $context['company_id'];

            if (!$customerId) {
                return $this->responseFormatter->success([
                    'success' => true,
                    'appointments' => [],
                    'message' => 'Sie haben noch keine Termine.'
                ]);
            }

            $appointments = \App\Models\Appointment::where('customer_id', $customerId)
                ->where('company_id', $companyId)
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at', 'asc')
                ->get();

            $formatted = $appointments->map(function ($apt) {
                return [
                    'id' => $apt->id,
                    'date' => $apt->starts_at->format('d.m.Y'),
                    'time' => $apt->starts_at->format('H:i'),
                    'service' => $apt->service->name ?? 'Unbekannt',
                    'staff' => $apt->staff->name ?? 'Unbekannt'
                ];
            });

            Log::info('✅ V4: Retrieved customer appointments', [
                'call_id' => $callId,
                'customer_id' => $customerId,
                'count' => $formatted->count()
            ]);

            return $this->responseFormatter->success([
                'success' => true,
                'appointments' => $formatted->toArray(),
                'message' => $formatted->isEmpty()
                    ? 'Sie haben keine bevorstehenden Termine.'
                    : "Sie haben {$formatted->count()} Termin(e)."
            ]);

        } catch (\Exception $e) {
            Log::error('❌ V4: Get appointments failed', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);

            return $this->responseFormatter->success([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Es gab ein Problem beim Laden Ihrer Termine.'
            ]);
        }
    }

    /**
     * 🚀 V4: Cancel Appointment Wrapper
     *
     * Wrapper that injects call_id for cancel_appointment function
     * Used by Conversation Flow V4 for cancelling appointments
     *
     * POST /api/retell/cancel-appointment-v4
     */
    public function cancelAppointmentV4(Request $request)
    {
        $callId = $request->input('call.call_id');
        $appointmentId = $request->input('args.appointment_id');
        $datum = $request->input('args.datum');
        $uhrzeit = $request->input('args.uhrzeit');

        Log::info('❌ V4: Cancel Appointment', [
            'call_id' => $callId,
            'appointment_id' => $appointmentId,
            'datum' => $datum,
            'uhrzeit' => $uhrzeit,
            'params' => $request->except(['call'])
        ]);

        // 🔧 V4: Inject call_id into args
        $data = $request->all();
        $args = $data['args'] ?? [];
        $args['call_id'] = $callId;
        $data['args'] = $args;
        $request->replace($data);

        Log::info('🔧 V4: Injected call_id into args', [
            'args_call_id' => $request->input('args.call_id')
        ]);

        try {
            // Get call context
            $context = $this->getCallContext($callId);

            if (!$context) {
                Log::error('❌ Failed to get call context', ['call_id' => $callId]);
                return $this->responseFormatter->success([
                    'success' => false,
                    'error' => 'context_not_found',
                    'message' => 'Ich konnte Ihre Daten nicht laden.'
                ]);
            }

            $customerId = $context['customer_id'] ?? null;
            $companyId = $context['company_id'];

            // Find appointment
            $query = \App\Models\Appointment::where('company_id', $companyId);

            if ($appointmentId) {
                $query->where('id', $appointmentId);
            } else if ($datum && $uhrzeit) {
                // Parse date/time
                $startDateTime = \Carbon\Carbon::createFromFormat('d.m.Y H:i', "$datum $uhrzeit");
                $query->where('starts_at', $startDateTime);
            }

            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            $appointment = $query->first();

            if (!$appointment) {
                return $this->responseFormatter->success([
                    'success' => false,
                    'error' => 'appointment_not_found',
                    'message' => 'Ich konnte den Termin nicht finden.'
                ]);
            }

            // Cancel in Cal.com
            $calcomService = app(\App\Services\CalcomService::class);
            $calcomService->cancelBooking($appointment->calcom_booking_id);

            // Update local record
            $appointment->status = 'cancelled';
            $appointment->save();

            Log::info('✅ V4: Appointment cancelled successfully', [
                'call_id' => $callId,
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_booking_id
            ]);

            return $this->responseFormatter->success([
                'success' => true,
                'appointment_id' => $appointment->id,
                'message' => "Ihr Termin am {$appointment->starts_at->format('d.m.Y')} um {$appointment->starts_at->format('H:i')} Uhr wurde storniert."
            ]);

        } catch (\Exception $e) {
            Log::error('❌ V4: Cancel appointment failed', [
                'error' => $e->getMessage(),
                'call_id' => $callId,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->responseFormatter->success([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Die Stornierung konnte nicht durchgeführt werden.'
            ]);
        }
    }

    /**
     * 🚀 V4: Reschedule Appointment Wrapper
     *
     * Wrapper that injects call_id for reschedule_appointment function
     * Used by Conversation Flow V4 for rescheduling appointments
     *
     * POST /api/retell/reschedule-appointment-v4
     */
    public function rescheduleAppointmentV4(Request $request)
    {
        $callId = $request->input('call.call_id');
        $appointmentId = $request->input('args.appointment_id');
        $oldDatum = $request->input('args.old_datum');
        $oldUhrzeit = $request->input('args.old_uhrzeit');
        $newDatum = $request->input('args.new_datum');
        $newUhrzeit = $request->input('args.new_uhrzeit');

        Log::info('🔄 V4: Reschedule Appointment', [
            'call_id' => $callId,
            'appointment_id' => $appointmentId,
            'old_datum' => $oldDatum,
            'old_uhrzeit' => $oldUhrzeit,
            'new_datum' => $newDatum,
            'new_uhrzeit' => $newUhrzeit,
            'params' => $request->except(['call'])
        ]);

        // 🔧 V4: Inject call_id into args
        $data = $request->all();
        $args = $data['args'] ?? [];
        $args['call_id'] = $callId;
        $data['args'] = $args;
        $request->replace($data);

        Log::info('🔧 V4: Injected call_id into args', [
            'args_call_id' => $request->input('args.call_id')
        ]);

        try {
            // Get call context
            $context = $this->getCallContext($callId);

            if (!$context) {
                Log::error('❌ Failed to get call context', ['call_id' => $callId]);
                return $this->responseFormatter->success([
                    'success' => false,
                    'error' => 'context_not_found',
                    'message' => 'Ich konnte Ihre Daten nicht laden.'
                ]);
            }

            $customerId = $context['customer_id'] ?? null;
            $companyId = $context['company_id'];

            // Find appointment
            $query = \App\Models\Appointment::where('company_id', $companyId);

            if ($appointmentId) {
                $query->where('id', $appointmentId);
            } else if ($oldDatum && $oldUhrzeit) {
                // 🔧 FIX 2025-10-25: Bug #2 - Parse date using DateTimeParser to support German weekdays
                // Old: Carbon::createFromFormat('d.m.Y H:i', "$oldDatum $oldUhrzeit") - fails for "Montag 08:30"
                // New: Use DateTimeParser service to handle "Montag", "Dienstag", etc.
                $parsedDate = $this->dateTimeParser->parseDateString($oldDatum);

                if (!$parsedDate) {
                    Log::error('❌ V10: Failed to parse old appointment date', [
                        'call_id' => $callId,
                        'old_datum' => $oldDatum,
                        'old_uhrzeit' => $oldUhrzeit,
                    ]);

                    return $this->responseFormatter->success([
                        'success' => false,
                        'error' => 'invalid_date_format',
                        'message' => 'Ich konnte das Datum nicht verstehen. Bitte nennen Sie mir das Datum im Format "01.10.2025" oder als Wochentag wie "Montag".'
                    ]);
                }

                // Parse time: Remove "Uhr" and extract HH:MM
                $cleanTime = trim(preg_replace('/\s*uhr\s*$/i', '', $oldUhrzeit));
                if (!str_contains($cleanTime, ':')) {
                    $cleanTime .= ':00'; // Add :00 if only hour provided
                }

                $oldDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "$parsedDate $cleanTime", 'Europe/Berlin');

                Log::info('✅ V10: Parsed old appointment datetime using DateTimeParser', [
                    'call_id' => $callId,
                    'input_datum' => $oldDatum,
                    'input_uhrzeit' => $oldUhrzeit,
                    'parsed_date' => $parsedDate,
                    'clean_time' => $cleanTime,
                    'final_datetime' => $oldDateTime->toDateTimeString(),
                ]);

                $query->where('starts_at', $oldDateTime);
            }

            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            $appointment = $query->first();

            if (!$appointment) {
                return $this->responseFormatter->success([
                    'success' => false,
                    'error' => 'appointment_not_found',
                    'message' => 'Ich konnte den Termin nicht finden.'
                ]);
            }

            // 🔧 FIX 2025-10-25: Bug #2 - Parse new date using DateTimeParser to support German weekdays
            $parsedNewDate = $this->dateTimeParser->parseDateString($newDatum);

            if (!$parsedNewDate) {
                Log::error('❌ V10: Failed to parse new appointment date', [
                    'call_id' => $callId,
                    'new_datum' => $newDatum,
                    'new_uhrzeit' => $newUhrzeit,
                ]);

                return $this->responseFormatter->success([
                    'success' => false,
                    'error' => 'invalid_date_format',
                    'message' => 'Ich konnte das neue Datum nicht verstehen. Bitte nennen Sie mir das Datum im Format "01.10.2025" oder als Wochentag wie "Montag".'
                ]);
            }

            // Parse time: Remove "Uhr" and extract HH:MM
            $cleanNewTime = trim(preg_replace('/\s*uhr\s*$/i', '', $newUhrzeit));
            if (!str_contains($cleanNewTime, ':')) {
                $cleanNewTime .= ':00'; // Add :00 if only hour provided
            }

            $newDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "$parsedNewDate $cleanNewTime", 'Europe/Berlin');

            Log::info('✅ V10: Parsed new appointment datetime using DateTimeParser', [
                'call_id' => $callId,
                'input_datum' => $newDatum,
                'input_uhrzeit' => $newUhrzeit,
                'parsed_date' => $parsedNewDate,
                'clean_time' => $cleanNewTime,
                'final_datetime' => $newDateTime->toDateTimeString(),
            ]);

            // Transaction-safe: Cancel old + Book new
            \DB::beginTransaction();
            try {
                $calcomService = app(\App\Services\CalcomService::class);

                // Cancel old booking
                $calcomService->cancelBooking($appointment->calcom_booking_id);

                // Create new booking
                $newBooking = $calcomService->createBooking([
                    'event_type_id' => $appointment->calcom_event_type_id,
                    'start_time' => $newDateTime->toIso8601String(),
                    'attendee' => [
                        'name' => $appointment->customer->name,
                        'email' => $appointment->customer->email ?? 'noreply@askproai.de',
                        'phone' => $appointment->customer->phone
                    ],
                    'metadata' => [
                        'service' => $appointment->service->name,
                        'reschedule_from' => $appointment->starts_at->toIso8601String()
                    ]
                ]);

                // Update appointment
                $appointment->starts_at = $newDateTime;
                $appointment->calcom_booking_id = $newBooking['id'];
                $appointment->save();

                \DB::commit();

                Log::info('✅ V4: Appointment rescheduled successfully', [
                    'call_id' => $callId,
                    'appointment_id' => $appointment->id,
                    'old_time' => $oldDatum . ' ' . $oldUhrzeit,
                    'new_time' => $newDatum . ' ' . $newUhrzeit
                ]);

                return $this->responseFormatter->success([
                    'success' => true,
                    'appointment_id' => $appointment->id,
                    'message' => "Ihr Termin wurde erfolgreich verschoben auf {$newDatum} um {$newUhrzeit} Uhr."
                ]);

            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('❌ V4: Reschedule appointment failed', [
                'error' => $e->getMessage(),
                'call_id' => $callId,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->responseFormatter->success([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Die Verschiebung konnte nicht durchgeführt werden.'
            ]);
        }
    }

    /**
     * 🚀 V4: Get Available Services Wrapper
     *
     * Wrapper that injects call_id for get_available_services function
     * Used by Conversation Flow V4 for listing services
     *
     * POST /api/retell/get-services-v4
     */
    public function getAvailableServicesV4(Request $request)
    {
        $callId = $request->input('call.call_id');

        Log::info('📋 V4: Get Available Services', [
            'call_id' => $callId,
            'params' => $request->except(['call'])
        ]);

        // 🔧 V4: Inject call_id into args
        $data = $request->all();
        $args = $data['args'] ?? [];
        $args['call_id'] = $callId;
        $data['args'] = $args;
        $request->replace($data);

        Log::info('🔧 V4: Injected call_id into args', [
            'args_call_id' => $request->input('args.call_id')
        ]);

        // Call existing getAvailableServices method
        return $this->getAvailableServices($request);
    }

    /**
     * 🎯 Get Available Services (Public Endpoint)
     *
     * Returns all active services for the company/branch
     * Used by Retell AI to present service options to user
     *
     * POST /api/retell/get-available-services
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableServices(Request $request)
    {
        $callId = $request->input('call.call_id');

        Log::info('📋 List Services Request', [
            'call_id' => $callId,
            'raw_request' => $request->all()
        ]);

        try {
            // Get call context (company_id, branch_id)
            $context = $this->getCallContext($callId);

            if (!$context) {
                Log::error('❌ Failed to get call context', [
                    'call_id' => $callId
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'context_not_found',
                    'message' => 'Entschuldigung, ich konnte Ihre Anfrage nicht verarbeiten. Bitte versuchen Sie es erneut.'
                ], 200);
            }

            // Get service list
            $services = $this->serviceExtractor->getServiceList(
                $context['company_id'],
                $context['branch_id']
            );

            if (empty($services)) {
                Log::warning('⚠️ No services found for company', [
                    'company_id' => $context['company_id'],
                    'branch_id' => $context['branch_id']
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'no_services',
                    'message' => 'Entschuldigung, derzeit sind keine Dienstleistungen verfügbar.'
                ], 200);
            }

            Log::info('✅ Services retrieved successfully', [
                'call_id' => $callId,
                'service_count' => count($services),
                'services' => array_column($services, 'name')
            ]);

            // Format response for Retell
            return response()->json([
                'success' => true,
                'services' => $services,
                'count' => count($services),
                'message' => $this->formatServiceListMessage($services)
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ List services failed', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'internal_error',
                'message' => 'Entschuldigung, es gab einen technischen Fehler. Bitte versuchen Sie es erneut.'
            ], 200);
        }
    }

    /**
     * Format service list into natural language message for AI
     *
     * @param array $services
     * @return string
     */
    private function formatServiceListMessage(array $services): string
    {
        if (count($services) === 1) {
            return "Wir bieten folgende Dienstleistung an: {$services[0]['name']}.";
        }

        $serviceNames = array_column($services, 'name');
        $lastService = array_pop($serviceNames);

        if (empty($serviceNames)) {
            return "Wir bieten folgende Dienstleistungen an: {$lastService}.";
        }

        $serviceList = implode(', ', $serviceNames);
        return "Wir bieten folgende Dienstleistungen an: {$serviceList} und {$lastService}.";
    }

    /**
     * Initialize call - Get customer info + current time + policies
     *
     * 🔧 FIX 2025-10-24: Added to support V39 flow Function Node
     * Previously this was NOT a callable function, but V39 has it as Function Node
     *
     * This function is called at the very start of each call to:
     * - Recognize returning customers by phone number
     * - Provide current date/time in Berlin timezone
     * - Load company-specific policies
     * - Set up initial greeting message
     *
     * @param array $parameters Empty array (no parameters needed)
     * @param string|null $callId Retell call ID (nullable like all other function handlers)
     * @return \Illuminate\Http\JsonResponse
     */
    private function initializeCall(array $parameters, ?string $callId): \Illuminate\Http\JsonResponse
    {
        try {
            Log::info('🚀 initialize_call called', [
                'call_id' => $callId,
                'parameters' => $parameters
            ]);

            // 🔧 RACE CONDITION FIX 2025-10-24: Create Call record if it doesn't exist
            // ROOT CAUSE: Retell calls initialize_call BEFORE webhook creates Call record
            // Evidence: Call 720 created 33 seconds AFTER initialize_call ran
            // Solution: Use firstOrCreate to ensure Call exists before getCallContext()
            // This guarantees to_number lookup in CallLifecycleService can execute

            if ($callId && $callId !== 'None') {
                // 🔧 FIX 2025-11-16: Add company_id and branch_id for test calls
                $createData = [
                    'from_number' => $parameters['from_number'] ?? $parameters['caller_number'] ?? null,
                    'to_number' => $parameters['to_number'] ?? $parameters['called_number'] ?? null,
                    'call_status' => 'ongoing',
                    'start_timestamp' => now(),
                    'direction' => 'inbound'
                ];

                $call = \App\Models\Call::firstOrCreate(
                    ['retell_call_id' => $callId],
                    $createData
                );

                // For test calls, set company and branch AFTER creation (they're guarded fields)
                if ($call->wasRecentlyCreated && (str_starts_with($callId, 'flow_test_') || str_starts_with($callId, 'test_'))) {
                    $call->company_id = 1; // Friseur 1 for testing
                    $call->branch_id = '34c4d48e-4753-4715-9c30-c55843a943e8'; // Main branch
                    $call->save();

                    Log::info('🔧 Test call detected - set company_id and branch_id', [
                        'call_id' => $callId,
                        'call_db_id' => $call->id,
                        'company_id' => $call->company_id,
                        'branch_id' => $call->branch_id
                    ]);
                }

                Log::info('✅ initialize_call: Call record ensured', [
                    'call_id' => $callId,
                    'call_db_id' => $call->id,
                    'was_created' => $call->wasRecentlyCreated,
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number
                ]);
            }

            // Get call context (company_id, branch_id)
            $context = $this->getCallContext($callId);

            // 🔧 FINAL FIX 2025-10-24 16:50: ALLOW call to proceed even without company_id
            // ROOT CAUSE: initialize_call runs BEFORE call_started webhook can create Call record
            // SOLUTION: Return success and let webhook set company_id asynchronously
            // Subsequent functions will work because Call record will exist by then
            if (!$context || !$context['company_id']) {
                Log::warning('⚠️ initialize_call: Company not yet resolved, proceeding anyway', [
                    'call_id' => $callId,
                    'context' => $context,
                    'race_condition' => 'initialize_call runs before call_started webhook',
                    'resolution' => 'call_started webhook will set company_id within milliseconds',
                    'next_functions_will_work' => true
                ]);

                // 🎯 ALLOW THE CALL TO PROCEED - AI speaks immediately!
                return $this->responseFormatter->success([
                    'success' => true,
                    'message' => 'Guten Tag! Wie kann ich Ihnen helfen?',
                    'note' => 'Company context will be resolved by webhook momentarily'
                ]);
            }

            // Get customer info (if phone number available)
            $customerData = null;
            $call = \App\Models\Call::where('retell_call_id', $callId)->first();

            if ($call && $call->from_number && $call->from_number !== 'anonymous') {
                $customer = \App\Models\Customer::where('company_id', $context['company_id'])
                    ->where('phone', $call->from_number)
                    ->first();

                if ($customer) {
                    $customerData = [
                        'customer_id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'is_known' => true,
                        'message' => "Willkommen zurück, " . $customer->name . "!"
                    ];

                    Log::info('✅ initialize_call: Customer recognized', [
                        'call_id' => $callId,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name
                    ]);
                }
            }

            // Get current time in Berlin timezone
            $berlinTime = \Carbon\Carbon::now('Europe/Berlin');

            // Get policies (if any)
            $policies = \App\Models\PolicyConfiguration::where('company_id', $context['company_id'])
                ->where('branch_id', $context['branch_id'])
                ->where('is_active', true)
                ->get()
                ->map(function($policy) {
                    return [
                        'type' => $policy->policy_type,
                        'value' => $policy->policy_value,
                        'description' => $policy->description
                    ];
                });

            Log::info('✅ initialize_call: Success', [
                'call_id' => $callId,
                'customer_known' => $customerData !== null,
                'policies_loaded' => $policies->count(),
                'current_time' => $berlinTime->format('H:i')
            ]);

            return $this->responseFormatter->success([
                'success' => true,
                'current_time' => $berlinTime->toIso8601String(),
                'current_date' => $berlinTime->format('d.m.Y'),
                'current_weekday' => $berlinTime->locale('de')->dayName,
                'customer' => $customerData,
                'policies' => $policies->toArray(),
                'message' => $customerData ? $customerData['message'] : 'Guten Tag! Wie kann ich Ihnen helfen?'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ initialize_call failed', [
                'error' => $e->getMessage(),
                'call_id' => $callId,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->responseFormatter->success([
                'success' => false,
                'error' => 'Initialization failed: ' . $e->getMessage(),
                'message' => 'Guten Tag! Wie kann ich Ihnen helfen?'
            ]);
        }
    }

    /**
     * Find alternative times for composite services considering phase-aware availability
     *
     * @param \App\Models\Service $service
     * @param \App\Models\Staff $staff
     * @param \Carbon\Carbon $requestedDate
     * @param string $branchId
     * @return array
     */
    private function findAlternativesForCompositeService($service, $staff, $requestedDate, $branchId)
    {
        $availabilityService = app(\App\Services\ProcessingTimeAvailabilityService::class);
        $alternatives = [];

        // Check same day first (every 15 minutes)
        $sameDayStart = $requestedDate->copy()->startOfDay()->setTime(8, 0);
        $sameDayEnd = $requestedDate->copy()->endOfDay()->setTime(20, 0);

        for ($time = $sameDayStart; $time <= $sameDayEnd; $time->addMinutes(15)) {
            if ($time->equalTo($requestedDate)) {
                continue; // Skip requested time (we know it's not available)
            }

            if ($availabilityService->isStaffAvailable($staff->id, $time, $service)) {
                $alternatives[] = [
                    'time' => $time->format('Y-m-d H:i'),
                    'spoken' => $time->locale('de')->isoFormat('dddd, [den] D. MMMM [um] H:mm [Uhr]'),
                    'available' => true,
                    'type' => 'same_day'
                ];

                if (count($alternatives) >= 3) {
                    break; // Found enough alternatives
                }
            }
        }

        // If not enough same-day alternatives, check next few days
        if (count($alternatives) < 3) {
            for ($dayOffset = 1; $dayOffset <= 7; $dayOffset++) {
                $nextDay = $requestedDate->copy()->addDays($dayOffset)->setTime(9, 0);
                $dayEnd = $nextDay->copy()->setTime(18, 0);

                for ($time = $nextDay; $time <= $dayEnd; $time->addMinutes(30)) {
                    if ($availabilityService->isStaffAvailable($staff->id, $time, $service)) {
                        $alternatives[] = [
                            'time' => $time->format('Y-m-d H:i'),
                            'spoken' => $time->locale('de')->isoFormat('dddd, [den] D. MMMM [um] H:mm [Uhr]'),
                            'available' => true,
                            'type' => 'next_day'
                        ];

                        if (count($alternatives) >= 5) {
                            break 2; // Found enough alternatives
                        }
                    }
                }
            }
        }

        return $alternatives;
    }

    /**
     * Format alternatives message for German language
     *
     * @param \Carbon\Carbon $requestedDate
     * @param array $alternatives
     * @return string
     */
    private function formatAlternativesMessage($requestedDate, $alternatives)
    {
        if (empty($alternatives)) {
            return sprintf(
                'Leider ist zur gewünschten Zeit %s nichts frei. Möchten Sie einen anderen Termin?',
                $requestedDate->locale('de')->isoFormat('dddd, [den] D. MMMM [um] H:mm [Uhr]')
            );
        }

        $message = sprintf(
            'Zur gewünschten Zeit %s ist leider nichts frei. ',
            $requestedDate->locale('de')->isoFormat('H:mm [Uhr]')
        );

        $sameDayAlts = array_filter($alternatives, fn($alt) => $alt['type'] === 'same_day');
        if (!empty($sameDayAlts)) {
            $message .= 'Aber am gleichen Tag habe ich noch: ';
            $times = array_map(fn($alt) => $alt['spoken'], array_slice($sameDayAlts, 0, 3));
            $message .= implode(', ', $times) . '. ';
        }

        $nextDayAlts = array_filter($alternatives, fn($alt) => $alt['type'] === 'next_day');
        if (!empty($nextDayAlts) && count($sameDayAlts) < 2) {
            $message .= 'An anderen Tagen: ';
            $times = array_map(fn($alt) => $alt['spoken'], array_slice($nextDayAlts, 0, 2));
            $message .= implode(', ', $times) . '. ';
        }

        $message .= 'Was würde Ihnen passen?';

        return $message;
    }

    /**
     * Format alternatives array into human-readable spoken text
     * Used for race condition recovery
     *
     * @param array $alternatives Array of alternative time slots
     * @return string Formatted message
     */
    private function formatAlternatives(array $alternatives): string
    {
        if (empty($alternatives)) {
            return 'Leider habe ich momentan keine freien Termine gefunden.';
        }

        // 🔧 FIX 2025-11-14: AlternativeFinder returns 'description' not 'spoken' or 'time'
        // Caused "Undefined array key 'time'" crashes during race condition recovery
        $times = array_map(fn($alt) => $alt['description'] ?? $alt['spoken'] ??
            ($alt['datetime'] ?? Carbon::parse($alt['time'] ?? 'now'))->format('H:i'),
            array_slice($alternatives, 0, 3)
        );
        return 'Verfügbar sind: ' . implode(', ', $times) . '. Welcher Termin würde Ihnen passen?';
    }

    /**
     * ✅ Phase 3: Get service information
     *
     * Provides details about services offered by branch
     * Policy-enforced via BranchPolicyEnforcer
     *
     * @param array $parameters Function parameters
     * @param string $callId Retell call ID
     * @return array Retell response
     */
    private function getServiceInformation(array $parameters, string $callId): array
    {
        try {
            $context = $this->getCallContext($callId);
            $branch = Branch::find($context['branch_id']);
            $call = Call::where('retell_call_id', $callId)->first();

            if (!$branch || !$call) {
                return $this->responseFormatter->error('Branch oder Call nicht gefunden');
            }

            $service = app(\App\Services\Retell\ServiceInformationService::class);
            return $service->getServiceInformation($branch, $call, $parameters);

        } catch (\Exception $e) {
            Log::error('❌ Failed to get service information', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
            ]);

            return $this->responseFormatter->error(
                'Service-Informationen konnten nicht abgerufen werden.'
            );
        }
    }

    /**
     * ✅ Phase 3: Get opening hours
     *
     * Provides branch opening hours (today, specific day, or weekly schedule)
     * Policy-enforced via BranchPolicyEnforcer
     *
     * @param array $parameters Function parameters
     * @param string $callId Retell call ID
     * @return array Retell response
     */
    private function getOpeningHours(array $parameters, string $callId): array
    {
        try {
            $context = $this->getCallContext($callId);
            $branch = Branch::find($context['branch_id']);
            $call = Call::where('retell_call_id', $callId)->first();

            if (!$branch || !$call) {
                return $this->responseFormatter->error('Branch oder Call nicht gefunden');
            }

            $service = app(\App\Services\Retell\OpeningHoursService::class);
            return $service->getOpeningHours($branch, $call, $parameters);

        } catch (\Exception $e) {
            Log::error('❌ Failed to get opening hours', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
            ]);

            return $this->responseFormatter->error(
                'Öffnungszeiten konnten nicht abgerufen werden.'
            );
        }
    }
}