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
use App\Services\Retell\SlotIntelligenceService;
use App\Services\Booking\CompositeBookingService;
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
    private \App\Services\Booking\AvailabilityWithLockService $lockWrapper;
    private \App\Services\Booking\SlotLockService $lockService;
    private SlotIntelligenceService $slotIntelligence;
    private ?CompositeBookingService $compositeBookingService = null;
    private array $callContextCache = []; // DEPRECATED: Use CallLifecycleService caching instead

    public function __construct(
        ServiceSelectionService $serviceSelector,
        ServiceNameExtractor $serviceExtractor,
        WebhookResponseService $responseFormatter,
        CallLifecycleService $callLifecycle,
        CallTrackingService $callTracking,
        CustomerDataValidator $dataValidator,
        AppointmentCustomerResolver $customerResolver,
        DateTimeParser $dateTimeParser,
        \App\Services\Booking\AvailabilityWithLockService $lockWrapper,
        \App\Services\Booking\SlotLockService $lockService,
        SlotIntelligenceService $slotIntelligence,
        CompositeBookingService $compositeBookingService
    ) {
        $this->serviceSelector = $serviceSelector;
        $this->serviceExtractor = $serviceExtractor;
        $this->responseFormatter = $responseFormatter;
        $this->callLifecycle = $callLifecycle;
        $this->callTracking = $callTracking;
        $this->dataValidator = $dataValidator;
        $this->customerResolver = $customerResolver;
        $this->dateTimeParser = $dateTimeParser;
        $this->lockWrapper = $lockWrapper;
        $this->lockService = $lockService;
        $this->slotIntelligence = $slotIntelligence;
        $this->compositeBookingService = $compositeBookingService;
        $this->alternativeFinder = new AppointmentAlternativeFinder();
        $this->calcomService = new CalcomService();
    }

    /**
     * 🔧 FIX 2025-11-18: 4-Layer Call ID Extraction (Layered Defense Architecture)
     *
     * Root Cause: Flow V76 truncates call_id from 32 → 27 chars (missing last 5 chars)
     * Example: call_56c6beccfc65bdb043fad77ef7a → call_56c6beccfc65bdb043fad77a
     *
     * Solution: Multi-layer extraction with fallbacks
     *
     * Layer 1: Request Header (Most Reliable)
     *   - Retell sends X-Retell-Call-Id header with full 32-char ID
     *   - Not subject to Flow variable truncation
     *   - SUCCESS RATE: 99%
     *
     * Layer 2: Function Parameters (Standard)
     *   - Uses call_id from function arguments or top-level data
     *   - May be truncated by Flow V76 bug
     *   - SUCCESS RATE: 50% (fails when truncated)
     *
     * Layer 3: Partial Match (Workaround)
     *   - If Layer 2 Call ID < 32 chars, try partial match
     *   - Match first N chars with LIKE query
     *   - Uses most recent match to avoid false positives
     *   - SUCCESS RATE: 95% (fails only if multiple similar IDs)
     *
     * Layer 4: Emergency Fallback (Last Resort)
     *   - Most recent active call within 5 minutes
     *   - SUCCESS RATE: 80% (fails if multiple concurrent calls)
     *
     * @param Request $request HTTP request with headers
     * @param array $data Parsed request data
     * @param array $parameters Function parameters
     * @return string|null Extracted Call ID (32 chars if successful)
     */
    private function extractCallIdLayered(Request $request, array $data, array $parameters): ?string
    {
        $callId = null;
        $source = 'unknown';

        // ============================================
        // LAYER 0: Request Body call.call_id (SOURCE OF TRUTH)
        // ============================================
        // 🔥 FIX 2025-12-13: Retell ALWAYS sends call.call_id in the request body
        // for custom function calls. This is the authoritative source - args.call_id
        // is just a convenience copy that may contain placeholders like "123", "12345", etc.
        // See: https://docs.retellai.com/api-references/custom-functions
        //
        // Note: Not constraining to strlen===32 to avoid fragility if Retell changes format
        $bodyCallId = $data['call']['call_id'] ?? null;
        $paramCallId = $parameters['call_id'] ?? $data['call_id'] ?? null;

        if (is_string($bodyCallId) && $bodyCallId !== '') {
            // Exclude obvious placeholders that might leak into call.call_id
            $isBodyPlaceholder = preg_match('/^\d{1,5}$/', $bodyCallId) || $bodyCallId === 'undefined';

            if (!$isBodyPlaceholder) {
                // Log mismatch for debugging Flow configuration issues
                if ($paramCallId && $paramCallId !== $bodyCallId) {
                    Log::debug('📋 call_id arg differs from payload call_id (expected with placeholders)', [
                        'arg_call_id' => $paramCallId,
                        'payload_call_id' => $bodyCallId,
                        'using' => 'payload (source of truth)'
                    ]);
                }
                Log::info('✅ Layer 0: Call ID from Request Body (Source of Truth)', [
                    'call_id' => $bodyCallId,
                    'source' => 'data.call.call_id'
                ]);
                return $bodyCallId;
            }
        }

        // ============================================
        // LAYER 1: Request Header (FALLBACK)
        // ============================================
        $headerCallId = $request->header('X-Retell-Call-Id');
        if ($headerCallId && strlen($headerCallId) === 32) {
            $callId = $headerCallId;
            $source = 'header';
            Log::info('✅ Layer 1: Call ID from Request Header', [
                'call_id' => $callId,
                'length' => strlen($callId),
                'source' => 'X-Retell-Call-Id'
            ]);
            return $callId;
        }

        // ============================================
        // LAYER 2: Function Parameters (LEGACY FALLBACK)
        // ============================================
        // Note: With Layer 0, this should rarely be needed. Kept for edge cases/tests.

        // Define all known placeholders (fallback detection)
        // These are kept as safety net but Layer 0 handles them automatically
        $placeholders = ['dummy_call_id', 'None', 'current', 'current_call', 'call_1', 'call_001', '1', 'undefined', '12345', '123'];

        // Conservative placeholder heuristic: any 1-5 digit number is likely a placeholder
        $isNumericPlaceholder = $paramCallId && preg_match('/^\d{1,5}$/', $paramCallId);

        if ($paramCallId && (in_array($paramCallId, $placeholders, true) || $isNumericPlaceholder)) {
            Log::warning('⚠️ Layer 2: Parameter is placeholder and Layer 0 failed', [
                'placeholder' => $paramCallId,
                'is_numeric_placeholder' => $isNumericPlaceholder,
                'layer_0_failed' => 'data.call.call_id was empty or invalid'
            ]);
            $paramCallId = null; // Clear placeholder so Layer 4 can try
        }

        if ($paramCallId && !in_array($paramCallId, $placeholders, true) && !$isNumericPlaceholder) {
            $callId = $paramCallId;
            $source = 'parameter';

            // Check if truncated
            if (strlen($callId) === 32) {
                Log::info('✅ Layer 2: Full Call ID from Parameters', [
                    'call_id' => $callId,
                    'length' => 32
                ]);
                return $callId;
            } else {
                Log::warning('⚠️ Layer 2: TRUNCATED Call ID from Parameters', [
                    'call_id' => $callId,
                    'length' => strlen($callId),
                    'expected_length' => 32,
                    'missing_chars' => 32 - strlen($callId),
                    'will_try_layer_3' => true
                ]);
                // Don't return yet - try Layer 3 partial match
            }
        }

        // ============================================
        // LAYER 3: Partial Match (WORKAROUND)
        // ============================================
        if ($callId && strlen($callId) < 32 && strlen($callId) >= 20) {
            Log::info('🔍 Layer 3: Attempting Partial Match for Truncated ID', [
                'truncated_id' => $callId,
                'length' => strlen($callId)
            ]);

            // Try to find full Call ID using partial match
            $fullCallId = $this->findCallByPartialId($callId);
            if ($fullCallId) {
                Log::info('✅ Layer 3: SUCCESS - Full Call ID found via Partial Match', [
                    'truncated_id' => $callId,
                    'full_call_id' => $fullCallId,
                    'recovered_chars' => strlen($fullCallId) - strlen($callId)
                ]);
                return $fullCallId;
            } else {
                Log::warning('❌ Layer 3: FAILED - No match found for truncated ID', [
                    'truncated_id' => $callId
                ]);
            }
        }

        // ============================================
        // LAYER 4: Emergency Fallback (LAST RESORT)
        // ============================================
        if (!$callId || $callId === 'None') {
            Log::warning('🚨 Layer 4: Emergency Fallback - Using Most Recent Call', [
                'reason' => 'No valid Call ID from Layers 1-3'
            ]);

            $recentCall = \App\Models\Call::where('call_status', 'ongoing')
                ->where('start_timestamp', '>=', now()->subMinutes(5))
                ->orderBy('start_timestamp', 'desc')
                ->first();

            if ($recentCall) {
                Log::info('✅ Layer 4: SUCCESS - Using Most Recent Active Call', [
                    'call_id' => $recentCall->retell_call_id,
                    'started_at' => $recentCall->start_timestamp
                ]);
                return $recentCall->retell_call_id;
            } else {
                Log::error('❌ Layer 4: FAILED - No Recent Active Calls Found');
                return null;
            }
        }

        // If we get here, we have a callId but couldn't upgrade it
        Log::warning('⚠️ Call ID extraction completed with potential truncation', [
            'call_id' => $callId,
            'length' => strlen($callId),
            'source' => $source
        ]);
        return $callId;
    }

    /**
     * 🔧 FIX 2025-11-18: Layer 3 Helper - Find Full Call ID by Partial Match
     *
     * Used when Flow V76 truncates call_id from 32 → 27 chars
     *
     * Strategy:
     * - Use LIKE query with truncated ID as prefix
     * - Order by created_at DESC to get most recent match
     * - Limit to calls within last 10 minutes to avoid false positives
     *
     * @param string $partialId Truncated Call ID (e.g., 27 chars)
     * @return string|null Full Call ID (32 chars) if found
     */
    private function findCallByPartialId(string $partialId): ?string
    {
        $call = \App\Models\Call::where('retell_call_id', 'LIKE', $partialId . '%')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->orderBy('created_at', 'desc')
            ->first();

        return $call ? $call->retell_call_id : null;
    }

    /**
     * 🔧 FIX 2025-12-13: Ensure Call record exists in database BEFORE function routing
     *
     * This replaces the need for get_current_context/initializeCall as first tool.
     * By creating the Call record before any function handler runs, we guarantee
     * that getCallContext() will always find the call.
     *
     * Moved from initializeCall() to run on EVERY function call, not just when
     * the agent explicitly calls get_current_context.
     *
     * @param string|null $callId Resolved call ID from extractCallIdLayered()
     * @param array $data Full request data (for from_number/to_number from call.*)
     * @param array $parameters Function parameters (fallback for from_number/to_number)
     * @return \App\Models\Call|null
     */
    private function ensureCallRecordExists(?string $callId, array $data = [], array $parameters = []): ?\App\Models\Call
    {
        if (!$callId || $callId === 'None') {
            return null;
        }

        // Check if this is a test call
        $isTestCall = str_starts_with($callId, 'flow_test_') ||
                      str_starts_with($callId, 'test_') ||
                      str_starts_with($callId, 'phase1_test_');

        // 🔧 Härtung B: Pull metadata from data.call FIRST, fallback to parameters
        $createData = [
            'from_number' => $data['call']['from_number'] ?? $parameters['from_number'] ?? $parameters['caller_number'] ?? null,
            'to_number' => $data['call']['to_number'] ?? $parameters['to_number'] ?? $parameters['called_number'] ?? null,
            'status' => $isTestCall ? 'test' : 'ongoing',
            'call_status' => $isTestCall ? 'test' : 'ongoing',
            'start_timestamp' => now(),
            'direction' => 'inbound'
        ];

        $call = \App\Models\Call::firstOrCreate(
            ['retell_call_id' => $callId],
            $createData
        );

        // 🔧 Härtung C: Set test-call company/branch even for existing records without company_id
        if ($isTestCall && !$call->company_id) {
            $call->company_id = 1; // Friseur 1 for testing
            $call->branch_id = '34c4d48e-4753-4715-9c30-c55843a943e8'; // Main branch
            $call->save();

            Log::debug('🔧 ensureCallRecordExists: Test call company/branch set', [
                'call_id' => $callId,
                'call_db_id' => $call->id,
                'was_recently_created' => $call->wasRecentlyCreated,
                'company_id' => 1
            ]);
        }

        if ($call->wasRecentlyCreated) {
            Log::info('✅ ensureCallRecordExists: Call record created', [
                'call_id' => $callId,
                'call_db_id' => $call->id,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number
            ]);
        }

        return $call;
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
     * 🔧 FIX 2025-11-18: Now uses extractCallIdLayered() for 4-layer extraction
     * No longer needs fallback logic (moved to Layer 4 of extraction)
     *
     * @param string|null $callId Retell call ID
     * @return array|null ['company_id' => int, 'branch_id' => int|null, 'phone_number_id' => int]
     */
    private function getCallContext(?string $callId): ?array
    {
        // 🔧 FIX 2025-11-18: Fallback logic moved to extractCallIdLayered()
        // This method now expects a valid callId (already processed through 4 layers)
        if (!$callId || $callId === 'None') {
            Log::error('❌ getCallContext called with invalid Call ID', [
                'call_id' => $callId,
                'note' => 'Should have been handled by extractCallIdLayered()'
            ]);
            return null;
        }

        // 🔧 FIX: Race Condition - Retry with exponential backoff
        // The call session might not be committed yet when function is called
        // 🔧 FIX 2025-12-07: Increased retries to handle slow webhooks/cold starts
        // Previous: 5 attempts (~750ms). New: 10 attempts (~5.5s)
        $maxAttempts = 10;
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
                // Increase delay: 100ms * attempt (100, 200, 300... 1000ms)
                $delayMs = 100 * $attempt; 
                Log::info('⏳ getCallContext retry ' . $attempt . '/' . $maxAttempts, [
                    'call_id' => $callId,
                    'delay_ms' => $delayMs
                ]);
                usleep($delayMs * 1000); // Convert to microseconds
            }
        }

        if (!$call) {
            // 🔧 FIX 2025-12-07: JIT (Just-In-Time) Call Creation Fallback
            // If webhook is retarded/missing, fetch directly from Retell API
            Log::warning('⚠️ getCallContext failed after retries. Attempting JIT creation via Retell API...', [
                'call_id' => $callId
            ]);

            try {
                /** @var \App\Services\RetellApiClient $retellClient */
                $retellClient = app(\App\Services\RetellApiClient::class);
                $callData = $retellClient->getCallDetail($callId);

                if ($callData) {
                    $call = $retellClient->syncCallToDatabase($callData);
                    
                    if ($call) {
                        Log::info('✅ JIT Call Creation successful', [
                            'call_id' => $call->id, 
                            'retell_id' => $callId,
                            'company_id' => $call->company_id
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('❌ JIT Call Creation failed', [
                    'call_id' => $callId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!$call) {
            Log::error('❌ getCallContext failed completely after ' . $maxAttempts . ' attempts + JIT', [
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

        // 🔥 FIX 2025-11-19: DEFAULT BRANCH FALLBACK
        // If company_id/branch_id still NULL after all resolution attempts,
        // use default branch to prevent "Call context not available" errors
        if (!$companyId || !$branchId) {
            Log::error('❌ getCallContext: company/branch resolution failed - trying DEFAULT BRANCH fallback', [
                'call_id' => $call->id,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number
            ]);

            // Try to get default branch for company_id 1 (fallback)
            $defaultBranch = \App\Models\Branch::where('company_id', 1)
                ->where('is_default', true)
                ->first();

            if ($defaultBranch) {
                $companyId = $defaultBranch->company_id;
                $branchId = $defaultBranch->id;

                Log::info('✅ DEFAULT BRANCH FALLBACK successful', [
                    'call_id' => $call->id,
                    'fallback_company_id' => $companyId,
                    'fallback_branch_id' => $branchId,
                    'fallback_branch_name' => $defaultBranch->name,
                    'source' => 'default_branch_fallback'
                ]);
            } else {
                Log::critical('🚨 NO DEFAULT BRANCH FOUND - functions will fail!', [
                    'call_id' => $call->id,
                    'company_id' => 1,
                    'suggestion' => 'Ensure default branch exists in database with is_default=true'
                ]);
                return null;
            }
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

        // 🔧 FIX 2025-11-18: Use 4-Layer Call ID Extraction (Layered Defense Architecture)
        // Replaces all old extraction logic with comprehensive layered approach
        // Layers: Header → Full Parameter → Partial Match → Emergency Fallback
        $callId = $this->extractCallIdLayered($request, $data, $parameters);

        if (!$callId) {
            Log::error('❌ All 4 layers of Call ID extraction failed', [
                'function' => $functionName,
                'param_call_id' => $parameters['call_id'] ?? 'missing',
                'data_call_id' => $data['call_id'] ?? 'missing',
                'header_call_id' => $request->header('X-Retell-Call-Id') ?? 'missing'
            ]);
            // Continue processing - some functions may not need call context
        }

        // 🔧 FIX 2025-12-13: Ensure Call record exists BEFORE routing
        // This replaces the need for get_current_context/initializeCall as first tool.
        // By creating the Call record here, we guarantee getCallContext() will always find it.
        // Benefit: Agent can skip get_current_context → saves 1 roundtrip (~500-1000ms latency)
        $this->ensureCallRecordExists($callId, $data, $parameters);

        // 🔥 CRITICAL FIX 2025-11-19: Replace placeholder call_ids with real call_id in parameters
        // ROOT CAUSE: Retell sends placeholders in args.call_id but real call_id in call.call_id
        // PLACEHOLDERS: "dummy_call_id" (old), "None" (Python), "current" (V121 12:33), "current_call" (V121 14:28+)
        // IMPACT: getCallContext($parameters['call_id']) fails → "Call context not available" errors
        // SOLUTION: Replace ALL known placeholders with extracted real call_id
        //
        // 🔧 FIX 2025-11-26: ALWAYS replace parameters['call_id'] with extracted $callId
        // PROBLEM: Call #11044 had truncated call_id (31 chars instead of 32) causing "Call context not available"
        // The truncated call_id wasn't in the placeholder list, so it wasn't replaced
        // SOLUTION: Always trust $callId from the request, not parameters['call_id']
        // 🔥 FIX 2025-12-07: Added 'undefined' - Retell sends this when {{call_id}} variable is not set in conversation flow
        $knownPlaceholders = ['dummy_call_id', 'None', 'current', 'current_call', 'undefined'];

        if (isset($parameters['call_id']) && $callId) {
            $originalCallId = $parameters['call_id'];
            $isPlaceholder = in_array($originalCallId, $knownPlaceholders, true);
            $isTruncated = strlen($originalCallId) !== strlen($callId);
            $isMismatch = $originalCallId !== $callId;

            if ($isPlaceholder || $isTruncated || $isMismatch) {
                $parameters['call_id'] = $callId;

                Log::info('🔧 Replaced call_id in parameters', [
                    'function' => $functionName,
                    'original_call_id' => $originalCallId,
                    'real_call_id' => $callId,
                    'reason' => $isPlaceholder ? 'placeholder' : ($isTruncated ? 'truncated' : 'mismatch'),
                    'original_length' => strlen($originalCallId),
                    'real_length' => strlen($callId),
                    'source' => 'data.call.call_id'
                ]);
            }
        } elseif (!isset($parameters['call_id']) && $callId) {
            // If no call_id in parameters at all, add it
            $parameters['call_id'] = $callId;
            Log::info('🔧 Added missing call_id to parameters', [
                'function' => $functionName,
                'call_id' => $callId
            ]);
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
                    // 🔧 FIX 2025-11-28: Extract branch_id for session creation
                    // BUG: branch_id was not being propagated to retell_call_sessions
                    // causing AppointmentAlternativeFinder to fail with "Tenant context required"
                    $branchId = $callContext['branch_id'] ?? null;

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
                    // 🔧 FIX 2025-11-28: Include branch_id to ensure tenant context available
                    $existingSession = $this->callTracking->startCallSession([
                        'call_id' => $callId,
                        'company_id' => $companyId,
                        'customer_id' => $customerId,
                        'branch_id' => $branchId,
                        'agent_id' => $data['agent_id'] ?? null,
                    ]);

                    Log::info('📞 Auto-created call session on first function call', [
                        'call_id' => $callId,
                        'session_id' => $existingSession->id,
                        'first_function' => $functionName,
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
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

        // ========================================================================
        // SERVICE GATEWAY: Mode-based routing (Phase 1)
        // Feature-flag controlled: GATEWAY_MODE_ENABLED (default: false)
        // @see docs/SERVICE_GATEWAY_IMPLEMENTATION_PLAN.md
        // ========================================================================
        if (config('gateway.mode_enabled') && $callId) {
            try {
                $gatewayMode = app(\App\Services\Gateway\GatewayModeResolver::class)->resolve($callId);
            } catch (\RuntimeException $e) {
                // CRIT-002: Tenant context required (e.g., company soft-deleted)
                if (str_contains($e->getMessage(), 'CRIT-002')) {
                    Log::error('[Gateway] CRIT-002: Tenant context failure', [
                        'call_id' => $callId,
                        'function' => $baseFunctionName,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'CRIT-002: Tenant context required',
                        'message' => 'Es gab ein Problem bei der Zuordnung Ihres Anrufs.',
                    ], 400);
                }
                throw $e; // Re-throw non-CRIT-002 exceptions
            }

            if ($gatewayMode === 'service_desk') {
                Log::info('[Gateway] Routing to ServiceDeskHandler', [
                    'call_id' => $callId,
                    'function' => $baseFunctionName,
                    'mode' => $gatewayMode,
                ]);

                // Phase 2: ServiceDeskHandler aktiv
                return app(\App\Http\Controllers\ServiceDeskHandler::class)
                    ->handle($baseFunctionName, $parameters, $callId);
            }
            // 'appointment' and 'hybrid' modes continue with normal flow below
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
            // Alias for get_current_context tool used in some flows
            'get_current_context' => $this->initializeCall($parameters, $callId),
            // 🔧 FIX 2025-12-13: Slot reservation for race-condition prevention
            'reserve_slot' => $this->reserveSlot($parameters, $callId),
            'release_slot_reservation' => $this->releaseSlotReservation($parameters, $callId),
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
     * 🚀 PERFORMANCE OPTIMIZATION 2025-11-16: Cache-first strategy
     *
     * Called at start of every call to recognize returning customers.
     * Data is pre-loaded at call_started for instant response (0.1s vs 9.2s)
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

            // 🚀 PERFORMANCE OPTIMIZATION: Cache-first lookup
            // Data was pre-loaded at call_started → instant response
            $normalizedPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);
            $cacheKey = "customer_lookup:{$normalizedPhone}:{$companyId}";

            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                $cachedData = \Illuminate\Support\Facades\Cache::get($cacheKey);

                if ($cachedData === null) {
                    // Cached "not found" result
                    Log::info('⚡ Customer not found (from CACHE)', [
                        'call_id' => $callId,
                        'cache_key' => $cacheKey,
                        'performance' => 'instant'
                    ]);

                    return $this->responseFormatter->success([
                        'customer_id' => null,
                        'name' => null,
                        'found' => false,
                        'status' => 'new_customer',
                        'performance' => 'cached'
                    ], 'Dies ist ein neuer Kunde. Bitte fragen Sie nach dem Namen.');
                }

                // Cached customer data found!
                $customer = Customer::find($cachedData['customer_id']);
                if ($customer) {
                    $call->update(['customer_id' => $customer->id]);

                    Log::info('⚡ Customer found (from CACHE - INSTANT!)', [
                        'call_id' => $callId,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'cache_key' => $cacheKey,
                        'performance' => 'instant'
                    ]);

                    return $this->responseFormatter->success($cachedData, $cachedData['smart_greeting'] ?? 'Willkommen zurück!');
                }
            }

            // 🐢 FALLBACK: Cache miss - do full lookup (slower)
            Log::info('🐢 Cache MISS - performing full lookup', [
                'call_id' => $callId,
                'cache_key' => $cacheKey,
                'performance' => 'fallback'
            ]);

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
            // 🔧 FIX 2025-11-17: Handle null return from vague date input (e.g., "diese Woche" without time)
            if (!$requestedDate || !($requestedDate instanceof \Carbon\Carbon)) {
                // Check if it's a vague date needing time clarification
                $dateInput = $params['date'] ?? $params['datum'] ?? '';
                $isVagueDateWithoutTime = $requestedDate === null && preg_match('/(diese|nächste)\s+woche/i', $dateInput);

                if ($isVagueDateWithoutTime) {
                    Log::warning('⚠️ Vague date without time - asking user for clarification', [
                        'call_id' => $callId,
                        'date_input' => $dateInput,
                        'params' => $params
                    ]);

                    return $this->responseFormatter->success([
                        'success' => false,
                        'available' => false,
                        'error' => 'time_required',
                        'message' => 'Zu welcher Uhrzeit hätten Sie Zeit?',
                        'alternatives' => []
                    ]);
                }

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

            // 🔧 FIX 2025-11-24: Staff preference for availability checking
            // Allows checking availability for specific staff member instead of any staff
            $preferredStaffId = $params['preferred_staff_id'] ?? null;
            $mitarbeiterName = $params['mitarbeiter'] ?? null;

            // ═══════════════════════════════════════════════════════════════════════════
            // ✅ FIX 2025-12-08: Support both UUID and staff NAME for preferred_staff_id
            // ═══════════════════════════════════════════════════════════════════════════
            // PROBLEM: Retell agent sometimes sends staff NAME ("Udo Walz") instead of UUID
            //          in preferred_staff_id parameter due to extraction config
            // SOLUTION: Detect non-UUID values and use mapStaffNameToId() for conversion
            // RCA: Call #85183 - "Udo Walz" sent as preferred_staff_id, failed silently
            // ═══════════════════════════════════════════════════════════════════════════
            if ($preferredStaffId && !\Illuminate\Support\Str::isUuid($preferredStaffId)) {
                // It's a name, not a UUID - convert it
                $staffNameInput = $preferredStaffId;
                $preferredStaffId = $this->mapStaffNameToId($staffNameInput, $callId);

                Log::info('🔄 Converted staff name to ID in check_availability', [
                    'call_id' => $callId,
                    'input_name' => $staffNameInput,
                    'resolved_staff_id' => $preferredStaffId,
                    'fix' => 'FIX 2025-12-08: Name-to-UUID conversion'
                ]);
            } elseif (!$preferredStaffId && $mitarbeiterName) {
                // Legacy: mitarbeiter parameter (name-based)
                $preferredStaffId = $this->mapStaffNameToId($mitarbeiterName, $callId);

                Log::info('📌 Using mitarbeiter name mapping (legacy) in check_availability', [
                    'call_id' => $callId,
                    'mitarbeiter_name' => $mitarbeiterName,
                    'mapped_staff_id' => $preferredStaffId
                ]);
            }
            // ═══════════════════════════════════════════════════════════════════════════

            if ($preferredStaffId) {
                Log::info('👤 Staff preference received in check_availability', [
                    'call_id' => $callId,
                    'preferred_staff_id' => $preferredStaffId,
                    'service_name' => $serviceName,
                    'requested_time' => $requestedDate->format('Y-m-d H:i')
                ]);
            }

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

            // ═══════════════════════════════════════════════════════════════════════════
            // ✅ FIX 2025-12-08: Staff-Service Capability Check (Prevent Silent Fallback)
            // ═══════════════════════════════════════════════════════════════════════════
            // Check if requested staff can actually provide this service BEFORE
            // checking availability. Return helpful message with alternatives if not.
            // ═══════════════════════════════════════════════════════════════════════════
            if ($preferredStaffId) {
                $capabilityCheck = $this->validateStaffCanProvideService($preferredStaffId, $service, $callId);

                if (!$capabilityCheck['valid']) {
                    Log::warning('⚠️ Staff cannot provide requested service - returning alternatives', [
                        'call_id' => $callId,
                        'staff_id' => $preferredStaffId,
                        'staff_name' => $capabilityCheck['staff_name'],
                        'service_name' => $service->name,
                        'alternatives_count' => count($capabilityCheck['alternatives'] ?? []),
                        'fix' => 'FIX 2025-12-08: Prevent Silent Fallback'
                    ]);

                    // Return informative error with alternatives
                    return $this->responseFormatter->formatResponse([
                        'available' => false,
                        'reason' => 'staff_cannot_provide_service',
                        'message' => $capabilityCheck['message'],
                        'requested_staff' => $capabilityCheck['staff_name'],
                        'requested_service' => $service->name,
                        'alternative_staff' => $capabilityCheck['alternatives'],
                        'call_id' => $callId
                    ]);
                }
            }
            // ═══════════════════════════════════════════════════════════════════════════

            // 🔧 FIX 2025-11-24: Use staff-specific event type if preference exists
            $eventTypeId = $this->getEventTypeForStaff($service, $preferredStaffId, $branchId);

            Log::info('Using event type for availability check', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $eventTypeId,
                'preferred_staff_id' => $preferredStaffId ?? 'none',
                'is_staff_specific' => $preferredStaffId !== null,
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

                // 🔧 FIX 2025-11-25: Cache validated alternatives to skip re-check in start_booking
                // PROBLEM: When customer selects an alternative, start_booking re-checks and fails
                // SOLUTION: Cache alternatives as "pre-validated" for 5 minutes
                if ($callId && !empty($alternatives)) {
                    $validatedSlots = array_map(function($alt) {
                        return $alt['date'] . ' ' . $alt['time'];
                    }, $alternatives);

                    Cache::put("call:{$callId}:validated_alternatives", $validatedSlots, now()->addMinutes(5));
                    Cache::put("call:{$callId}:alternatives_validated_at", now(), now()->addMinutes(5));

                    Log::info('📋 Cached validated alternatives for re-check skip', [
                        'call_id' => $callId,
                        'slots' => $validatedSlots,
                        'ttl_minutes' => 5,
                    ]);

                    // 🔒 FIX 2025-11-26: Lock alternatives to prevent race conditions
                    // PROBLEM: 44-second window between check_availability and start_booking
                    // SOLUTION: Soft-lock alternatives when presenting them
                    $serviceDuration = $service->duration_minutes ?? 60;
                    if ($service->isComposite() && !empty($service->segments)) {
                        $serviceDuration = collect($service->segments)->sum(fn($s) => $s['durationMin'] ?? $s['duration'] ?? 0);
                    }
                    $this->lockAlternativeSlots(
                        $alternatives,
                        $companyId,
                        $service->id,
                        $serviceDuration,
                        $callId,
                        $params['customer_phone'] ?? $params['phone'] ?? 'unknown'
                    );
                }

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
                Cache::put("call:{$callId}:event_type_id", $eventTypeId, now()->addMinutes(30));

                Log::info('📌 Service pinned to call session', [
                    'call_id' => $callId,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'cache_key' => "call:{$callId}:service_id",
                    'ttl_minutes' => 30
                ]);
            }

            // 🔥 FIX 2025-11-19: PROCESSING TIME LOGIC
            // Services with has_processing_time=true (e.g., Dauerwelle) have 3 phases:
            // - Initial: Staff BUSY (applying treatment)
            // - Processing: Staff AVAILABLE (treatment processing, staff can serve others)
            // - Final: Staff BUSY (finishing treatment)
            // We must check phase-aware availability, not full duration blocking
            if ($service->has_processing_time) {
                Log::info('⏱️ Processing Time service detected - using phase-aware availability', [
                    'call_id' => $callId,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'initial_duration' => $service->initial_duration,
                    'processing_duration' => $service->processing_duration,
                    'final_duration' => $service->final_duration,
                    'total_duration' => $service->duration,
                    'requested_time' => $requestedDate->format('Y-m-d H:i')
                ]);

                // Get staff for this branch
                $staff = \App\Models\Staff::where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->whereHas('services', function($q) use ($service) {
                        $q->where('service_id', $service->id);
                    })
                    ->first();

                if (!$staff) {
                    Log::error('❌ No staff found for processing time service', [
                        'service_id' => $service->id,
                        'branch_id' => $branchId
                    ]);
                    return $this->responseFormatter->error('Kein Mitarbeiter für diesen Service verfügbar');
                }

                // Use ProcessingTimeAvailabilityService for phase-aware checking
                $availabilityService = app(\App\Services\ProcessingTimeAvailabilityService::class);

                $isPhaseAvailable = $availabilityService->isStaffAvailable(
                    $staff->id,
                    $requestedDate,
                    $service
                );

                if ($isPhaseAvailable) {
                    Log::info('✅ Processing time availability confirmed', [
                        'call_id' => $callId,
                        'staff_id' => $staff->id,
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'service' => $service->name,
                        'note' => 'Staff available for both initial and final phases'
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
                            $requestedDate->format('d.m.Y'),
                            $requestedDate->format('H:i')
                        ),
                    ];
                } else {
                    Log::warning('⚠️ Processing time availability check failed (phase conflict) - falling through to composite check', [
                        'call_id' => $callId,
                        'staff_id' => $staff->id,
                        'requested_time' => $requestedDate->format('Y-m-d H:i')
                    ]);
                }
            }

            // 🔧 REFACTORED 2025-11-13: Cal.com as SOURCE OF TRUTH for composite services
            // ⚠️ CRITICAL: Local DB only contains OUR bookings, Cal.com may have external bookings
            // Phase-aware availability checking for composite services
            // Composite services have segments with gaps where staff is available
            // 🔧 FIX 2025-12-07: COMPOSITE SERVICE AVAILABILITY CHECK
            // Use specific CompositeBookingService to check ALL segments
            if ($service->isComposite()) {
                Log::info('🧩 Composite Service detected - using specialized availability check', [
                    'call_id' => $callId,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'requested_time' => $requestedDate->format('Y-m-d H:i')
                ]);

                // Define search window (exact time requested +/- 0 to force exact match check first)
                // Actually, checkAvailability is usually for a specific time.
                // findCompositeSlots takes a start/end range.
                // We'll create a small window around the requested time to see if IT works starting at that time.
                
                try {
                    $slots = $this->compositeBookingService->findCompositeSlots($service, [
                        'start' => $requestedDate->copy()->toIso8601String(),
                        'end' => $requestedDate->copy()->addMinutes(120)->toIso8601String(), // Sufficient window for slots
                    ]);
                    
                    // Filter for slots exactly matching requested start time
                    $exactSlot = $slots->first(function($slot) use ($requestedDate) {
                         return Carbon::parse($slot['starts_at'])->eq($requestedDate);
                    });
    
                    if ($exactSlot) {
                        $staffId = $exactSlot['segments'][0]['staff_id'] ?? null;
                        
                        Log::info('✅ Composite availability confirmed', [
                            'call_id' => $callId,
                            'slot' => $exactSlot,
                            'pinned_staff_id' => $staffId
                        ]);
    
                        // Pin the staff ID for subsequent booking
                        if ($staffId && $callId) {
                            Cache::put("call:{$callId}:pinned_staff_id", $staffId, now()->addMinutes(30));
                             Log::info('📌 Pinned staff ID for composite booking', [
                                'call_id' => $callId,
                                'staff_id' => $staffId
                            ]);
                        }
    
                        $staffName = $exactSlot['segments'][0]['staff_name'] ?? 'einen Mitarbeiter';

                        return [
                            'success' => true,
                            'available' => true,
                            'service' => $service->name,
                            'staff' => $staffName,
                            'requested_time' => $requestedDate->format('Y-m-d H:i'),
                            'message' => sprintf(
                                'Ja, %s ist verfügbar am %s um %s Uhr.',
                                $service->name,
                                $requestedDate->format('d.m.Y'),
                                $requestedDate->format('H:i')
                            )
                        ];
                    } else {
                         Log::warning('❌ Composite check failed - no valid slot sequence found', [
                            'call_id' => $callId,
                            'requested_time' => $requestedDate->format('Y-m-d H:i')
                        ]);
                        
                        return [
                            'success' => true,
                            'available' => false,
                            'service' => $service->name,
                            'requested_time' => $requestedDate->format('Y-m-d H:i'),
                            'message' => 'Leider ist dieser Termin nicht möglich. Soll ich nach Alternativen schauen?'
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Composite availability check exception', [
                        'call_id' => $callId,
                        'message' => $e->getMessage()
                    ]);
                    // Fallback to error response
                    return $this->responseFormatter->error('Fehler bei der Verfügbarkeitsprüfung.');
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

            // 🧠 2025-11-25: SLOT INTELLIGENCE - Pre-load slots, fuzzy matching, positive framing
            // GOAL: Faster responses, better UX with positive messaging
            // - Pre-loads 7 days of slots at first check (cached for entire call)
            // - Handles vague time periods ("vormittags" → 09:00-12:00)
            // - Fuzzy matches specific times (10:00 → finds 10:05, 10:15 within ±30min)
            // - Positive framing: "Ja, 10:05 hätte ich" instead of "10:00 nicht frei"

            $timeInput = $params['uhrzeit'] ?? $params['time'] ?? $requestedDate->format('H:i');

            // 🕐 Check for vague time period first (vormittags, nachmittags, etc.)
            $vagueTimePeriod = $this->slotIntelligence->detectVagueTimePeriod($timeInput);

            if ($vagueTimePeriod) {
                Log::info('🕐 Vague time period detected - using SlotIntelligence', [
                    'call_id' => $callId,
                    'time_input' => $timeInput,
                    'period' => $vagueTimePeriod['label'],
                    'range' => $vagueTimePeriod['start'] . '-' . $vagueTimePeriod['end'],
                ]);

                // Pre-load slots for the call (cached for 15 minutes)
                $preloadResult = $this->slotIntelligence->preloadSlotsForCall($callId, $service);

                if ($preloadResult['success']) {
                    // Find slots in the requested time period
                    $periodSlots = $this->slotIntelligence->findSlotsInTimePeriod(
                        $callId,
                        $requestedDate->format('Y-m-d'),
                        $service->id,
                        $vagueTimePeriod,
                        3 // Return up to 3 options
                    );

                    $dateDisplay = $requestedDate->locale('de')->isoFormat('dddd, D.M.');
                    $periodResponse = $this->slotIntelligence->generateTimePeriodResponse(
                        $periodSlots,
                        $vagueTimePeriod,
                        $dateDisplay
                    );

                    Log::info('✅ SlotIntelligence: Time period response generated', [
                        'call_id' => $callId,
                        'positive' => $periodResponse['positive'],
                        'slots_found' => count($periodSlots),
                        'message_preview' => mb_substr($periodResponse['message'], 0, 50),
                    ]);

                    // Cache the offered slots for booking validation
                    if (!empty($periodSlots) && $callId) {
                        $validatedSlots = array_map(function($slot) use ($requestedDate) {
                            return $requestedDate->format('Y-m-d') . ' ' . $slot['time_display'];
                        }, $periodSlots);
                        Cache::put("call:{$callId}:validated_alternatives", $validatedSlots, now()->addMinutes(5));
                    }

                    return $this->responseFormatter->success([
                        'success' => true,
                        'available' => $periodResponse['positive'],
                        'message' => $periodResponse['message'],
                        'requested_time' => $requestedDate->format('Y-m-d') . ' ' . $vagueTimePeriod['label'],
                        'time_period' => $vagueTimePeriod['label'],
                        'alternatives' => array_map(function($slot) use ($requestedDate) {
                            return [
                                'date' => $requestedDate->format('Y-m-d'),
                                'time' => $slot['time_display'],
                                'formatted' => $slot['time_display'] . ' Uhr',
                            ];
                        }, $periodSlots),
                        'intelligent_response' => true,
                    ]);
                }
            }

            // 🎯 Specific time requested - use intelligent fuzzy matching
            // Pre-load slots (cached for 15 minutes, shared across all checks in this call)
            $preloadResult = $this->slotIntelligence->preloadSlotsForCall($callId, $service);

            // 🔧 FIX 2025-12-01: Preserve original requested time for accurate response
            // Previously, fuzzy matching modified $requestedDate which caused confusion
            // Now we keep $originalRequestedDate for reporting and use $requestedDate for validation
            $originalRequestedDate = $requestedDate->copy();
            $fuzzyMatchApplied = false;
            $fuzzyMatchDiff = 0;

            if ($preloadResult['success'] && config('features.slot_intelligence.fuzzy_match', true)) {
                // Try fuzzy matching first (much faster than Cal.com API)
                $requestedTimeStr = $requestedDate->format('H:i');
                $fuzzyResult = $this->slotIntelligence->findClosestSlot(
                    $callId,
                    $requestedDate->format('Y-m-d'),
                    $service->id,
                    $requestedTimeStr,
                    30 // ±30 minutes tolerance
                );

                if ($fuzzyResult['found']) {
                    $dateDisplay = $requestedDate->locale('de')->isoFormat('dddd, D.M.');
                    $positiveResponse = $this->slotIntelligence->generatePositiveResponse($fuzzyResult, $dateDisplay);

                    // Still validate with Cal.com for exact slot, but use positive framing
                    $foundSlot = $fuzzyResult['slot'];
                    $foundTime = Carbon::parse($requestedDate->format('Y-m-d') . ' ' . $foundSlot['time_display']);

                    Log::info('🎯 SlotIntelligence: Fuzzy match found', [
                        'call_id' => $callId,
                        'requested' => $requestedTimeStr,
                        'found' => $foundSlot['time_display'],
                        'diff_minutes' => $fuzzyResult['difference_minutes'],
                        'response_type' => $fuzzyResult['response_type'],
                    ]);

                    // 🔧 FIX 2025-12-08: ALWAYS use fuzzy-matched time when found
                    // BUG FIXED: Previously only applied for ≤15 min difference, causing:
                    // - message: "16:25 Uhr" (from SlotIntelligence)
                    // - slot_time: "16:00" (unchanged original)
                    // - Customer told 16:25, but agent confirmed 16:00 based on slot_time
                    //
                    // Now: ALWAYS update requestedDate AND track fuzzy match for ANY difference
                    // This ensures slot_time matches what the message tells the customer
                    $requestedDate = $foundTime;
                    $fuzzyMatchApplied = !$fuzzyResult['exact']; // true if not exact match
                    $fuzzyMatchDiff = $fuzzyResult['difference_minutes'];

                    Log::info('⚡ SlotIntelligence: Using fuzzy-matched time for Cal.com validation', [
                        'call_id' => $callId,
                        'original_request' => $requestedTimeStr,
                        'using_time' => $foundSlot['time_display'],
                        'fuzzy_match_applied' => $fuzzyMatchApplied,
                        'diff_minutes' => $fuzzyMatchDiff,
                        'original_preserved' => $originalRequestedDate->format('Y-m-d H:i'),
                    ]);
                }
            }

            // Use new CalcomAvailabilityService for direct slot checking
            $calcomAvailabilityService = app(\App\Services\Appointments\CalcomAvailabilityService::class);

            // 🔧 FIX 2025-12-05: Resolve preferred_staff_id (UUID) to calcom_host_id (integer)
            // This allows Cal.com API to filter availability by specific staff member
            // See: CalcomHostMapping table for mapping between internal staff and Cal.com users
            $calcomUserId = null;
            if ($preferredStaffId) {
                $hostMapping = \App\Models\CalcomHostMapping::where('staff_id', $preferredStaffId)
                    ->where('company_id', $service->company_id)
                    ->where('is_active', true)
                    ->first();

                if ($hostMapping) {
                    $calcomUserId = $hostMapping->calcom_host_id;
                    Log::info('👤 Resolved preferred_staff_id to Cal.com userId', [
                        'call_id' => $callId,
                        'preferred_staff_id' => $preferredStaffId,
                        'calcom_user_id' => $calcomUserId,
                        'calcom_name' => $hostMapping->calcom_name,
                        'calcom_email' => $hostMapping->calcom_email,
                    ]);
                } else {
                    Log::warning('⚠️ No CalcomHostMapping found for preferred_staff_id', [
                        'call_id' => $callId,
                        'preferred_staff_id' => $preferredStaffId,
                        'company_id' => $service->company_id,
                        'note' => 'Falling back to any available staff (Round Robin)',
                    ]);
                }
            }

            $calcomStartTime = microtime(true);
            $isAvailable = false;

            try {
                // Check availability directly with Cal.com API
                $isAvailable = $calcomAvailabilityService->isTimeSlotAvailable(
                    $requestedDate,
                    $eventTypeId,  // ✅ Staff-specific if preference exists
                    $service->duration_minutes ?? $duration,
                    null, // legacy staff_id (UUID) - deprecated, use calcomUserId instead
                    $service->company->calcom_team_id,
                    $calcomUserId  // 🔧 FIX 2025-12-05: Pass Cal.com user ID for staff filtering
                );

                $calcomDuration = round((microtime(true) - $calcomStartTime) * 1000, 2);

                Log::info('✅ Cal.com availability checked (regular service)', [
                    'call_id' => $callId,
                    'available' => $isAvailable,
                    'duration_ms' => $calcomDuration,
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'event_type_id' => $eventTypeId,  // ✅ Staff-specific if preference exists
                    'calcom_user_id' => $calcomUserId,  // 🔧 FIX 2025-12-05: Log Cal.com user ID
                    'performance_target' => '< 1000ms',
                    'performance_status' => $calcomDuration < 1000 ? 'GOOD' : 'NEEDS_OPTIMIZATION'
                ]);

                // 🔧 FIX 2025-11-14: Cache availability check timestamp for race condition prevention
                Cache::put("call:{$callId}:last_availability_check", now(), now()->addMinutes(10));

                // 🚀 PHASE 1 OPTIMIZATION (2025-11-17): Cache validated slot for fast booking
                // Eliminates redundant availability re-check in bookAppointment() (saves 300-800ms)
                // TTL: 90s covers typical voice conversation latency between check and booking
                if ($isAvailable && $callId) {
                    $validationCacheKey = "booking_validation:{$callId}:" . $requestedDate->format('YmdHi');
                    Cache::put($validationCacheKey, [
                        'available' => true,
                        'validated_at' => now(),
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'service_id' => $service->id,
                        'event_type_id' => $eventTypeId,  // ✅ Staff-specific if preference exists
                        'calcom_user_id' => $calcomUserId  // 🔧 FIX 2025-12-05: Include Cal.com user ID in cache
                    ], now()->addSeconds(90));

                    Log::info('⚡ PHASE 1: Cached availability validation for fast booking', [
                        'call_id' => $callId,
                        'cache_key' => $validationCacheKey,
                        'requested_time' => $requestedDate->format('Y-m-d H:i'),
                        'calcom_user_id' => $calcomUserId,
                        'ttl_seconds' => 90,
                        'optimization' => 'Eliminates redundant re-check in bookAppointment'
                    ]);
                }

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

                // 🔧 FIX 2025-11-27: COMPREHENSIVE LOCAL DB CONFLICT CHECK
                // CRITICAL: Cal.com API may return "available" due to:
                //   1. Sync delays (local appointment not yet synced)
                //   2. API caching (stale availability data)
                //   3. Duration calculation differences (Cal.com slot vs actual service duration)
                //
                // SOLUTION: Check ALL local appointments (regardless of sync status)
                // with the FULL service duration for accurate overlap detection.
                //
                // Example: Service = 55 min, Request 13:15
                // - Requested window: 13:15 - 14:10
                // - Existing appointment 13:30 - 14:25 OVERLAPS (13:30 - 14:10)
                // - Cal.com might say "13:15 available" but booking would fail

                // Calculate full appointment window
                // 🔧 FIX 2025-11-27: Calculate correct duration for COMPOSITE services
                $serviceDuration = $service->duration_minutes ?? $duration;
                $isComposite = false;

                if ($service->isComposite() && !empty($service->segments)) {
                    $isComposite = true;
                    $serviceDuration = collect($service->segments)->sum(fn($s) => $s['durationMin'] ?? $s['duration'] ?? 0);
                }

                $requestedEndTime = $requestedDate->copy()->addMinutes($serviceDuration);

                Log::debug('🔍 LOCAL DB CONFLICT CHECK - Full duration overlap detection', [
                    'call_id' => $callId,
                    'requested_start' => $requestedDate->format('Y-m-d H:i'),
                    'requested_end' => $requestedEndTime->format('Y-m-d H:i'),
                    'service_duration' => $serviceDuration,
                    'is_composite' => $isComposite,
                    'segments_count' => $isComposite ? count($service->segments) : 0,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                ]);

                // 🔧 FIX 2025-12-08: Add staff_id filter to prevent cross-staff conflicts
                // Previously: Checked ALL appointments in branch, blocking slots for wrong staff
                // Now: Only check conflicts for the REQUESTED staff member
                $conflictQuery = Appointment::where('company_id', $companyId)
                    ->where('branch_id', $branchId)
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked']);

                // Only filter by staff if a preferred staff was specified
                if ($preferredStaffId) {
                    $conflictQuery->where('staff_id', $preferredStaffId);
                    Log::debug('🔍 Checking conflicts for specific staff only', [
                        'call_id' => $callId,
                        'staff_id' => $preferredStaffId,
                        'time_window' => $requestedDate->format('H:i') . ' - ' . $requestedEndTime->format('H:i'),
                    ]);
                }

                $conflictingAppointment = $conflictQuery
                    // 🔧 FIX 2025-11-27: Removed sync_status filter - check ALL appointments
                    // Previously: ->whereIn('calcom_sync_status', ['pending', 'failed'])
                    // This missed synced appointments that Cal.com returned as "available"
                    ->where(function($query) use ($requestedDate, $requestedEndTime) {
                        // Comprehensive overlap detection using proper interval logic
                        // Two intervals [A_start, A_end) and [B_start, B_end) overlap if:
                        // A_start < B_end AND B_start < A_end

                        $query->where('starts_at', '<', $requestedEndTime)  // Existing starts before new ends
                              ->where('ends_at', '>', $requestedDate);       // Existing ends after new starts
                    })
                    ->first();

                if ($conflictingAppointment) {
                    Log::warning('🚨 LOCAL DB CONFLICT: Appointment blocks requested slot', [
                        'call_id' => $callId,
                        'calcom_said' => 'available',
                        'local_db_said' => 'blocked',
                        'requested_window' => [
                            'start' => $requestedDate->format('Y-m-d H:i'),
                            'end' => $requestedEndTime->format('Y-m-d H:i'),
                            'duration' => $serviceDuration,
                        ],
                        'blocking_appointment' => [
                            'id' => $conflictingAppointment->id,
                            'status' => $conflictingAppointment->status,
                            'sync_status' => $conflictingAppointment->calcom_sync_status ?? 'unknown',
                            'start' => $conflictingAppointment->starts_at->format('Y-m-d H:i'),
                            'end' => $conflictingAppointment->ends_at->format('Y-m-d H:i'),
                            'customer' => $conflictingAppointment->customer?->name,
                        ],
                        'overlap_analysis' => [
                            'new_starts_before_existing_ends' => $requestedDate < $conflictingAppointment->ends_at,
                            'existing_starts_before_new_ends' => $conflictingAppointment->starts_at < $requestedEndTime,
                        ],
                        'fix' => 'FIX 2025-11-27: Comprehensive local DB check with full duration overlap',
                    ]);

                    // 🔧 FIX 2025-12-08: Search for alternatives within customer's time preference
                    // Instead of returning empty alternatives, use TimePreference to find slots in the preferred window
                    $timeInput = $params['uhrzeit'] ?? $params['time'] ?? null;
                    $timePreference = $this->dateTimeParser->parseTimePreference($timeInput);

                    $alternativesResult = [];
                    $alternativeMessage = "Dieser Termin ist leider nicht verfügbar.";

                    // Only search for alternatives if we have valid service and event type
                    if ($service && $service->calcom_event_type_id) {
                        try {
                            $alternatives = $this->alternativeFinder
                                ->setTenantContext($companyId, $branchId ?? null)
                                ->findAlternatives(
                                    $requestedDate,
                                    $serviceDuration,
                                    $service->calcom_event_type_id,
                                    null, // customerId
                                    'de',
                                    $timePreference
                                );

                            $alternativesResult = $this->formatAlternativesForRetell($alternatives['alternatives'] ?? []);

                            // Generate appropriate message based on TimePreference
                            if (!empty($alternativesResult)) {
                                $preferenceLabel = $timePreference->getGermanLabel();
                                if ($timePreference->isTimeWindow()) {
                                    $alternativeMessage = "{$preferenceLabel} ist zu dieser Zeit leider belegt.";
                                } else {
                                    $alternativeMessage = "Um {$requestedDate->format('H:i')} Uhr ist leider schon ein Termin.";
                                }
                            } else {
                                $alternativeMessage = "Dieser Termin ist leider nicht verfügbar. Welche Zeit würde Ihnen alternativ passen?";
                            }

                            Log::info('🔍 LOCAL DB CONFLICT: Found alternatives with TimePreference', [
                                'call_id' => $callId,
                                'time_preference_type' => $timePreference->type,
                                'time_preference_window' => $timePreference->windowStart . '-' . $timePreference->windowEnd,
                                'alternatives_found' => count($alternativesResult),
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('⚠️ LOCAL DB CONFLICT: Failed to find alternatives', [
                                'call_id' => $callId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    return $this->responseFormatter->success([
                        'available' => false,
                        'message' => $alternativeMessage,
                        'requested_time' => $originalRequestedDate->format('Y-m-d H:i'),  // Original user request
                        'alternatives' => $alternativesResult,
                        'reason' => 'local_db_conflict',
                        'suggest_user_alternative' => empty($alternativesResult),
                        'conflict_info' => [
                            'existing_appointment_time' => $conflictingAppointment->starts_at->format('H:i') . ' - ' . $conflictingAppointment->ends_at->format('H:i'),
                            'staff_id' => $preferredStaffId,  // Include for debugging
                        ],
                        'time_preference' => [
                            'type' => $timePreference->type,
                            'label' => $timePreference->label,
                            'window_start' => $timePreference->windowStart,
                            'window_end' => $timePreference->windowEnd,
                        ],
                    ]);
                }

                // No existing appointment found - slot is truly available
                Log::info('✅ checkAvailability SUCCESS - Slot available', [
                    'call_id' => $callId,
                    'available' => true,
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'service' => $service->name ?? 'unknown',
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ]);

                // 🧠 2025-11-25: Use positive framing from SlotIntelligence if fuzzy match was used
                $positiveMessage = "Ja, {$requestedDate->format('H:i')} Uhr ist noch frei.";

                // Check if we used fuzzy matching (variable set earlier if match was found)
                if (isset($fuzzyResult) && $fuzzyResult['found'] && isset($positiveResponse)) {
                    $positiveMessage = $positiveResponse['message'];
                    Log::info('🎯 Using SlotIntelligence positive framing', [
                        'call_id' => $callId,
                        'original_message' => "Ja, {$requestedDate->format('H:i')} Uhr ist noch frei.",
                        'positive_message' => $positiveMessage,
                        'response_type' => $fuzzyResult['response_type'],
                    ]);
                }

                // Prepare availability result
                // 🔧 FIX 2025-12-01: Include both original request and matched slot for clarity
                $availabilityResult = [
                    'available' => true,
                    'message' => $positiveMessage,
                    'requested_time' => $originalRequestedDate->format('Y-m-d H:i'), // Original user request
                    'slot_time' => $requestedDate->format('Y-m-d H:i'), // Actual slot to book (may differ)
                    'fuzzy_match_applied' => $fuzzyMatchApplied,
                    'fuzzy_match_diff_minutes' => $fuzzyMatchDiff,
                    'alternatives' => [],
                    'intelligent_response' => isset($fuzzyResult) && $fuzzyResult['found'],
                ];

                // 🔒 REDIS LOCK INTEGRATION (2025-11-23)
                // Wrap with Redis lock to prevent race conditions (15-20% → <1%)
                // Only lock if slot is available - prevents double-booking during 8-12s gap
                if (config('features.slot_locking.enabled', false)) {
                    $customerPhone = $params['customer_phone'] ?? $params['phone'] ?? 'unknown';
                    $customerName = $params['customer_name'] ?? $params['name'] ?? null;

                    $availabilityResult = $this->lockWrapper->wrapWithLock(
                        $availabilityResult,
                        $companyId,
                        $service->id,
                        $requestedDate,
                        $requestedDate->copy()->addMinutes($duration),
                        $callId,
                        $customerPhone,
                        [
                            'customer_name' => $customerName,
                            'service_name' => $service->name,
                            'is_compound' => $service->is_compound ?? false,
                        ]
                    );

                    Log::info('🔒 Slot lock wrapper applied', [
                        'call_id' => $callId,
                        'has_lock_key' => isset($availabilityResult['lock_key']),
                        'slot_locked' => $availabilityResult['slot_locked'] ?? false,
                        'race_detected' => $availabilityResult['race_condition_detected'] ?? false,
                    ]);
                }

                return $this->responseFormatter->success($availabilityResult);
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

                // 🧠 2025-11-25: Use SlotIntelligence to find alternatives from pre-loaded cache
                // This is MUCH faster than Cal.com API call (5ms vs 800ms)
                $intelligentAlternatives = [];
                $intelligentMessage = "Dieser Termin ist leider nicht verfügbar. Welche Zeit würde Ihnen alternativ passen?";

                if (isset($preloadResult) && $preloadResult['success']) {
                    $requestedTimeStr = $requestedDate->format('H:i');

                    // 🔧 FIX 2025-11-26: Calculate full service duration for composite services
                    // PROBLEM: Cal.com slots are based on first segment (50 min), but Dauerwelle is 135 min
                    // SOLUTION: Use actual service duration for conflict detection
                    $serviceDuration = $duration; // Default from earlier in function
                    if (method_exists($service, 'getTotalDuration')) {
                        $serviceDuration = $service->getTotalDuration();
                    } elseif ($service->isComposite() && !empty($service->segments)) {
                        $serviceDuration = collect($service->segments)->sum(fn($s) => $s['durationMin'] ?? $s['duration'] ?? 0);
                    }

                    $nextSlots = $this->slotIntelligence->getNextAvailableSlots(
                        $callId,
                        $requestedDate->format('Y-m-d'),
                        $service->id,
                        3, // Get 3 alternatives
                        $requestedTimeStr, // After the requested time
                        $companyId,       // 🔧 FIX: Multi-tenant isolation
                        $branchId,        // 🔧 FIX: Branch isolation
                        $serviceDuration  // 🔧 FIX: Full composite duration for overlap check
                    );

                    if (!empty($nextSlots)) {
                        $intelligentAlternatives = array_map(function($slot) use ($requestedDate) {
                            return [
                                'date' => $requestedDate->format('Y-m-d'),
                                'time' => $slot['time_display'],
                                'formatted' => $slot['time_display'] . ' Uhr',
                            ];
                        }, $nextSlots);

                        // Generate positive message with alternatives
                        $times = array_map(fn($s) => $s['time'], $intelligentAlternatives);
                        if (count($times) === 1) {
                            $intelligentMessage = "Um {$requestedDate->format('H:i')} Uhr ist leider nichts mehr frei, aber ich hätte um {$times[0]} Uhr einen Termin für Sie.";
                        } else {
                            $lastTime = array_pop($times);
                            $timeList = implode(', ', $times) . ' oder ' . $lastTime;
                            $intelligentMessage = "Um {$requestedDate->format('H:i')} Uhr ist leider nichts mehr frei. Ich hätte noch um {$timeList} Uhr freie Termine.";
                        }

                        // Cache these alternatives for booking validation
                        if ($callId) {
                            $validatedSlots = array_map(function($alt) use ($requestedDate) {
                                return $requestedDate->format('Y-m-d') . ' ' . $alt['time'];
                            }, $intelligentAlternatives);
                            Cache::put("call:{$callId}:validated_alternatives", $validatedSlots, now()->addMinutes(5));

                            // 🔒 FIX 2025-11-26: Lock alternatives to prevent race conditions
                            $lockAlternatives = array_map(function($alt) use ($requestedDate) {
                                return [
                                    'date' => $requestedDate->format('Y-m-d'),
                                    'time' => $alt['time'],
                                ];
                            }, $intelligentAlternatives);
                            $this->lockAlternativeSlots(
                                $lockAlternatives,
                                $companyId,
                                $service->id,
                                $serviceDuration,
                                $callId,
                                $params['customer_phone'] ?? $params['phone'] ?? 'unknown'
                            );
                        }

                        Log::info('🧠 SlotIntelligence: Alternatives from pre-loaded cache', [
                            'call_id' => $callId,
                            'alternatives_count' => count($intelligentAlternatives),
                            'alternatives' => array_column($intelligentAlternatives, 'time'),
                            'source' => 'pre_loaded_cache',
                        ]);
                    }
                }

                return $this->responseFormatter->success([
                    'available' => false,
                    'message' => $intelligentMessage,
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'alternatives' => $intelligentAlternatives,
                    'suggest_user_alternative' => empty($intelligentAlternatives),
                    'intelligent_response' => !empty($intelligentAlternatives),
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
                    $eventTypeId,  // ✅ Staff-specific if preference exists
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

                // 🔒 FIX 2025-11-26: Lock alternatives to prevent race conditions
                $lockAlternatives = array_map(function($alt) {
                    if (isset($alt['datetime']) && $alt['datetime'] instanceof \Carbon\Carbon) {
                        return [
                            'date' => $alt['datetime']->format('Y-m-d'),
                            'time' => $alt['datetime']->format('H:i'),
                        ];
                    }
                    return null;
                }, $alternatives['alternatives']);
                $lockAlternatives = array_filter($lockAlternatives);

                if (!empty($lockAlternatives)) {
                    $this->lockAlternativeSlots(
                        $lockAlternatives,
                        $companyId,
                        $service->id,
                        $duration,
                        $callId,
                        $params['customer_phone'] ?? $params['phone'] ?? 'unknown'
                    );
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
     * Get the correct Cal.com Event Type ID for staff preference
     *
     * CRITICAL FIX 2025-11-24: Prevents double bookings by checking availability
     * with the correct staff-specific event type instead of default service event type.
     *
     * Logic:
     * - If preferred_staff_id set → Use staff-specific event type from CalcomEventMap
     * - If no preference → Use default service event type (any available staff)
     *
     * For composite services: Uses segment A event type for initial availability check
     *
     * @param Service $service The service to book
     * @param string|null $preferredStaffId Optional staff ID for preference
     * @param string $branchId Branch context for filtering
     * @return int Cal.com Event Type ID to use for availability check
     */
    private function getEventTypeForStaff(
        \App\Models\Service $service,
        ?string $preferredStaffId,
        string $branchId
    ): int
    {
        // No staff preference → use default event type (any staff)
        if (!$preferredStaffId) {
            Log::info('📅 No staff preference, using default event type', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $service->calcom_event_type_id
            ]);
            return $service->calcom_event_type_id;
        }

        // Staff preference exists → find staff-specific event type
        Log::info('👤 Staff preference detected, looking for staff-specific event type', [
            'service_id' => $service->id,
            'service_name' => $service->name,
            'preferred_staff_id' => $preferredStaffId,
            'is_composite' => $service->composite
        ]);

        // For composite services: use segment A (first segment)
        if ($service->composite) {
            $mapping = \App\Models\CalcomEventMap::where('service_id', $service->id)
                ->where('staff_id', $preferredStaffId)
                ->where('segment_key', 'A')  // First segment for availability check
                ->first();

            if ($mapping) {
                Log::info('✅ Found staff-specific event type (composite)', [
                    'service_id' => $service->id,
                    'staff_id' => $preferredStaffId,
                    'segment_key' => 'A',
                    'event_type_id' => $mapping->event_type_id,
                    'event_type_slug' => $mapping->event_type_slug
                ]);
                return $mapping->event_type_id;
            }

            Log::warning('⚠️ Staff preference for composite service but no CalcomEventMap found', [
                'service_id' => $service->id,
                'staff_id' => $preferredStaffId,
                'segment_key' => 'A'
            ]);
        } else {
            // For simple services: direct lookup (no segment key)
            $mapping = \App\Models\CalcomEventMap::where('service_id', $service->id)
                ->where('staff_id', $preferredStaffId)
                ->whereNull('segment_key')  // Simple services have no segments
                ->first();

            if ($mapping) {
                Log::info('✅ Found staff-specific event type (simple)', [
                    'service_id' => $service->id,
                    'staff_id' => $preferredStaffId,
                    'event_type_id' => $mapping->event_type_id,
                    'event_type_slug' => $mapping->event_type_slug
                ]);
                return $mapping->event_type_id;
            }

            Log::warning('⚠️ Staff preference for simple service but no CalcomEventMap found', [
                'service_id' => $service->id,
                'staff_id' => $preferredStaffId
            ]);
        }

        // Fallback: staff not found or no mapping exists
        // This can happen if:
        // - Staff not assigned to this service yet
        // - CalcomEventMap not populated for this staff/service combo
        // - Staff ID invalid
        Log::warning('⚠️ Fallback to default event type (staff preference set but no mapping)', [
            'service_id' => $service->id,
            'preferred_staff_id' => $preferredStaffId,
            'fallback_event_type_id' => $service->calcom_event_type_id,
            'reason' => 'No CalcomEventMap entry found for this staff/service combination'
        ]);

        return $service->calcom_event_type_id;
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

            // 🔧 NEW 2025-12-08: Extract TimePreference from customer's input
            // Supports: "vormittags", "ab 16 Uhr", "zwischen 10 und 16 Uhr", etc.
            // 🔧 FIX 2025-12-14: Add 'preferred_time' - Retell sends this parameter name!
            $timeInput = $params['preferred_time'] ?? $params['time'] ?? $params['uhrzeit'] ?? $params['zeitraum'] ?? null;
            $timePreference = $this->dateTimeParser->parseTimePreference($timeInput);

            Log::info('🕐 TimePreference parsed for alternatives', [
                'call_id' => $callId,
                'time_input' => $timeInput,
                'preference_type' => $timePreference->type,
                'window_start' => $timePreference->windowStart,
                'window_end' => $timePreference->windowEnd,
                'label' => $timePreference->label
            ]);

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

            // 🔧 FIX 2025-12-07: Use cached event_type_id if available (preserves staff selection)
            // This ensures consistency with check_availability which pins the specific event type
            $eventTypeId = \Illuminate\Support\Facades\Cache::get("call:{$callId}:event_type_id") ?? $service->calcom_event_type_id;

            Log::info('Using service for alternatives', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'default_event_type_id' => $service->calcom_event_type_id,
                'used_event_type_id' => $eventTypeId,
                'from_cache' => \Illuminate\Support\Facades\Cache::has("call:{$callId}:event_type_id"),
                'call_id' => $callId
            ]);

            // Find alternatives using our sophisticated finder
            // SECURITY: Set tenant context for cache isolation
            // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
            $call = $this->callLifecycle->findCallByRetellId($callId);
            $customerId = $call?->customer_id;

            // 🔧 FIX 2025-12-02: IDEMPOTENCY CHECK - Prevent offering alternatives when booking already exists
            // This fixes race condition where Retell timeout causes agent to think booking failed,
            // but the booking actually succeeded in the background. Without this check, the agent
            // would incorrectly offer alternatives for an already-booked appointment.
            if ($call) {
                // Check 1: Call already has a converted appointment
                if ($call->converted_appointment_id) {
                    $existingAppointment = \App\Models\Appointment::find($call->converted_appointment_id);
                    if ($existingAppointment && in_array($existingAppointment->status, ['booked', 'confirmed', 'pending'])) {
                        Log::info('🛡️ [IDEMPOTENCY] Booking already exists for this call - skipping alternatives', [
                            'call_id' => $callId,
                            'appointment_id' => $existingAppointment->id,
                            'appointment_status' => $existingAppointment->status,
                            'starts_at' => $existingAppointment->starts_at?->format('Y-m-d H:i'),
                        ]);

                        return $this->responseFormatter->success([
                            'found' => true,
                            'already_booked' => true,
                            'message' => 'Der Termin wurde bereits erfolgreich gebucht.',
                            'existing_appointment' => [
                                'id' => $existingAppointment->id,
                                'date' => $existingAppointment->starts_at?->format('d.m.Y'),
                                'time' => $existingAppointment->starts_at?->format('H:i'),
                                'service' => $existingAppointment->service?->name ?? 'Termin',
                            ],
                            'alternatives' => [], // No alternatives needed
                        ]);
                    }
                }

                // Check 2: Appointment exists with this call_id (set in CompositeBookingService)
                $appointmentByCallId = \App\Models\Appointment::where('call_id', $call->id)
                    ->whereIn('status', ['booked', 'confirmed', 'pending'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($appointmentByCallId) {
                    Log::info('🛡️ [IDEMPOTENCY] Appointment found by call_id - skipping alternatives', [
                        'call_id' => $callId,
                        'db_call_id' => $call->id,
                        'appointment_id' => $appointmentByCallId->id,
                        'appointment_status' => $appointmentByCallId->status,
                        'starts_at' => $appointmentByCallId->starts_at?->format('Y-m-d H:i'),
                    ]);

                    return $this->responseFormatter->success([
                        'found' => true,
                        'already_booked' => true,
                        'message' => 'Der Termin wurde bereits erfolgreich gebucht.',
                        'existing_appointment' => [
                            'id' => $appointmentByCallId->id,
                            'date' => $appointmentByCallId->starts_at?->format('d.m.Y'),
                            'time' => $appointmentByCallId->starts_at?->format('H:i'),
                            'service' => $appointmentByCallId->service?->name ?? 'Termin',
                        ],
                        'alternatives' => [],
                    ]);
                }
            }

            // 🐛 FIX 2025-11-25: Use $service->calcom_event_type_id instead of undefined $eventTypeId
            // 🔧 FIX 2025-12-07: Using resolved $eventTypeId (from cache or default)
            // 🔧 NEW 2025-12-08: Pass TimePreference for window-aware search
            $alternatives = $this->alternativeFinder
                ->setTenantContext($companyId, $branchId)
                ->findAlternatives(
                    $requestedDate,
                    $duration,
                    $eventTypeId,  // ✅ Fixed: using correctly resolved eventTypeId
                    $customerId,   // Pass customer ID to prevent offering conflicting times
                    'de',          // Preferred language
                    $timePreference // 🔧 NEW: Time window preference for targeted search
                );

            // Format response for natural conversation
            // 🔧 NEW 2025-12-08: Include preference context for intelligent follow-ups
            $preferenceContext = $alternatives['preference_context'] ?? null;

            // 🔧 FIX 2025-12-14: Integrate suggested_followup directly into message
            // Problem: Agent ignored preference_context and just read alternatives without context
            // Solution: Build intelligent message that includes time-shift warning
            $baseMessage = $alternatives['responseText'] ?? "Ich suche nach verfügbaren Terminen...";
            $finalMessage = $baseMessage;

            // If alternatives don't match preference (e.g., customer wanted morning, got evening)
            // 🔧 V128: Read config from company settings (Admin-configurable)
            $company = \App\Models\Company::find($companyId);

            // 🔧 FIX 2025-12-14: Log warning if company not found (debugging aid)
            if (!$company) {
                Log::warning('🚨 V128: Company not found, using defaults', [
                    'company_id' => $companyId,
                    'call_id' => $callId,
                ]);
            }

            $v128Config = $company?->getV128ConfigWithDefaults() ?? [
                'time_shift_enabled' => true,
                'time_shift_message' => '{label} ist leider schon ausgebucht. Soll ich am nächsten Tag {label} schauen, oder würde heute Abend auch passen? Heute hätte ich noch {alternatives} frei.',
            ];
            $timeShiftEnabled = $v128Config['time_shift_enabled'] ?? true;

            if ($timeShiftEnabled &&
                $preferenceContext &&
                !empty($preferenceContext['suggested_followup']) &&
                ($preferenceContext['all_match_preference'] ?? true) === false) {

                // Build contextual message for time-shift scenarios
                $preferenceLabel = $preferenceContext['label'] ?? null;
                $formattedAlternatives = $this->formatAlternativesForRetell($alternatives['alternatives'] ?? []);
                $altTimes = array_map(fn($a) => $a['spoken'] ?? $a['time'] ?? '', $formattedAlternatives);
                $altTimesText = implode(' oder ', array_filter($altTimes));

                // 🔧 FIX 2025-12-14: Only use template if we have valid data
                // Skip if either $preferenceLabel or $altTimesText is empty
                if ($preferenceLabel && $altTimesText) {
                    // 🔧 V128: Use custom template from admin settings if available
                    // 🔒 SECURITY: strip_tags to prevent XSS (double protection with Filament)
                    $messageTemplate = strip_tags(
                        $v128Config['time_shift_message'] ?? '{label} ist leider schon ausgebucht. Soll ich am nächsten Tag {label} schauen, oder würde heute Abend auch passen? Heute hätte ich noch {alternatives} frei.'
                    );
                    $finalMessage = str_replace(
                        ['{label}', '{alternatives}'],
                        [$preferenceLabel, $altTimesText],
                        $messageTemplate
                    );

                    Log::info('🕐 V128 Time preference mismatch - using contextual message', [
                        'call_id' => $callId,
                        'company_id' => $companyId,
                        'preference_label' => $preferenceLabel,
                        'all_match_preference' => false,
                        'suggested_followup' => $preferenceContext['suggested_followup'],
                        'time_shift_enabled' => $timeShiftEnabled,
                        'custom_template' => !empty($v128Config['time_shift_message']),
                        'final_message' => $finalMessage
                    ]);
                } else {
                    // 🔧 FIX 2025-12-14: Log warning if data missing for template
                    Log::warning('🚨 V128: Skipping time-shift message due to missing data', [
                        'call_id' => $callId,
                        'preference_label' => $preferenceLabel,
                        'alt_times_text' => $altTimesText,
                        'reason' => !$preferenceLabel ? 'missing_preference_label' : 'empty_alternatives',
                    ]);
                }
            }

            $responseData = [
                'found' => !empty($alternatives['alternatives']),
                'message' => $finalMessage,
                'alternatives' => $this->formatAlternativesForRetell($alternatives['alternatives'] ?? []),
                'original_request' => $requestedDate->format('Y-m-d H:i'),
                // 🔧 NEW: Preference context for agent decision-making
                'preference_context' => $preferenceContext ? [
                    'type' => $preferenceContext['type'] ?? null,
                    'label' => $preferenceContext['label'] ?? null,
                    'window' => ($preferenceContext['window_start'] ?? null) && ($preferenceContext['window_end'] ?? null)
                        ? "{$preferenceContext['window_start']}-{$preferenceContext['window_end']}"
                        : null,
                    'all_match_preference' => $preferenceContext['all_match_preference'] ?? true,
                    'suggested_followup' => $preferenceContext['suggested_followup'] ?? null
                ] : null
            ];

            // 🔧 FIX 2025-11-25: Cache alternatives with slot locking to prevent race conditions
            if (!empty($alternatives['alternatives'])) {
                $this->cacheValidatedAlternatives($callId, $alternatives['alternatives'], [
                    'company_id' => $companyId,
                    'service_id' => $service->id,
                    'duration' => $duration,
                    'customer_phone' => $params['customer_phone'] ?? null,
                    'customer_name' => $params['name'] ?? $params['customer_name'] ?? null,
                ]);
            }

            // 🐛 FIX 2025-10-22: Corrected method name (was successResponse, should be responseFormatter->success)
            return $this->responseFormatter->success($responseData);

        } catch (\Exception $e) {
            Log::error('Error getting alternatives', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'call_id' => $callId
            ]);
            // 🔧 FIX 2025-12-07: Return detailed error in local env or log for debugging, provide friendly message
            // For now, keep friendly message but maybe add detail if debug mode?
            // Retell sees the string in "error" field.
            return $this->responseFormatter->error('Fehler beim Suchen von Alternativen: ' . $e->getMessage()); // Temporary: Include error for debugging
        }
    }

    /**
     * Book an appointment after customer confirms
     * Called when customer says: "Ja, 15 Uhr passt mir" or "Den nehme ich"
     */
    private function bookAppointment(array $params, ?string $callId)
    {
        // 🔍 DEBUG 2025-10-22: Enhanced logging to diagnose Call #634 issues
        // 🔧 FIX 2025-11-26: Added performance tracking
        $bookingStartTime = now();

        Log::warning('🔷 bookAppointment START', [
            'call_id' => $callId,
            'params' => $params,
            'timestamp' => $bookingStartTime->toIso8601String()
        ]);

        // 🔧 FIX 2025-11-26: FUNCTION-LEVEL IDEMPOTENCY
        // Prevents duplicate booking when Retell calls start_booking twice due to timeout/retry
        // Returns cached successful response instead of processing again
        $idempotencyCacheKey = "booking_success:{$callId}";
        $cachedResult = Cache::get($idempotencyCacheKey);

        if ($cachedResult && isset($cachedResult['success']) && $cachedResult['success']) {
            Log::info('⚡ IDEMPOTENCY: Returning cached booking result for duplicate call', [
                'call_id' => $callId,
                'appointment_id' => $cachedResult['appointment_id'] ?? null,
                'cached_at' => $cachedResult['cached_at'] ?? null,
                'age_seconds' => isset($cachedResult['cached_at'])
                    ? now()->diffInSeconds(\Carbon\Carbon::parse($cachedResult['cached_at']))
                    : null
            ]);

            return response()->json($cachedResult['response']);
        }

        try {
            // FEATURE: Branch-aware booking with strict validation
            // Get call context to ensure branch isolation
            $callContext = $this->getCallContext($callId);

            if (!$callContext) {
                // 🔧 FIX 2025-11-19: Enhanced error logging for call context issues
                Log::error('❌ Cannot book: Call context not found', [
                    'call_id' => $callId,
                    'function' => 'bookAppointment',
                    'params' => $params,
                    'datetime_param' => $params['datetime'] ?? 'NOT_SET',
                    'service_name' => $params['service_name'] ?? 'NOT_SET',
                    'customer_name' => $params['customer_name'] ?? 'NOT_SET'
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            // 🔒 SECURITY FIX 2025-11-19 (CRIT-002): Enforce tenant context validation
            // CRITICAL: Prevent cache poisoning and cross-tenant data leakage
            if ($companyId === null || $branchId === null) {
                Log::error('⚠️ SECURITY: bookAppointment called without tenant context', [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'call_id' => $callId,
                    'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
                ]);
                return $this->responseFormatter->error(
                    'Sicherheitsfehler: Filialzuordnung fehlt. Bitte rufen Sie erneut an.'
                );
            }

            Log::debug('✅ Tenant context validated (CRIT-002)', [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'call_id' => $callId
            ]);

            // 🔧 FIX 2025-11-14: Get customer ID for alternatives filtering
            $call = $this->callLifecycle->findCallByRetellId($callId);
            $customerId = $call?->customer_id;

            // 🔧 FIX 2025-12-13: Check for existing slot reservation
            // If reserve_slot was called earlier, the slot is already held in Cal.com
            $reservationKey = "call:{$callId}:reservation";
            $existingReservation = Cache::get($reservationKey);
            $hasActiveReservation = false;

            if ($existingReservation) {
                // Check if reservation is still valid
                $reservationUntil = isset($existingReservation['until'])
                    ? \Carbon\Carbon::parse($existingReservation['until'])
                    : null;

                if ($reservationUntil && $reservationUntil->isFuture()) {
                    $hasActiveReservation = true;
                    Log::info('🔒 Active slot reservation found - skipping availability double-check', [
                        'call_id' => $callId,
                        'reservation_uid' => $existingReservation['uid'] ?? null,
                        'reservation_until' => $reservationUntil->toIso8601String(),
                        'reserved_datetime' => $existingReservation['datetime'] ?? null,
                        'service_id' => $existingReservation['service_id'] ?? null,
                    ]);
                } else {
                    // Reservation expired, clear it
                    Cache::forget($reservationKey);
                    Log::warning('🔓 Slot reservation expired', [
                        'call_id' => $callId,
                        'reservation_until' => $reservationUntil?->toIso8601String(),
                    ]);
                }
            }

            // 🔧 FIX 2025-11-19: Map "datetime" parameter to "date" + "time" for dateTimeParser
            // PROBLEM: Retell agent V117+ sends "datetime" but dateTimeParser expects "date" + "time"
            // SOLUTION: Parse datetime string into separate parameters
            if (isset($params['datetime']) && !isset($params['date']) && !isset($params['time'])) {
                $datetimeStr = trim($params['datetime']);

                // Try parsing "YYYY-MM-DD HH:MM" format
                if (preg_match('/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})/', $datetimeStr, $matches)) {
                    $params['date'] = $matches[1];
                    $params['time'] = $matches[2];

                    Log::debug('✅ Mapped datetime parameter', [
                        'original_datetime' => $datetimeStr,
                        'mapped_date' => $params['date'],
                        'mapped_time' => $params['time'],
                        'call_id' => $callId
                    ]);
                }
                // Try parsing ISO 8601 format "YYYY-MM-DDTHH:MM:SS"
                elseif (preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/', $datetimeStr, $matches)) {
                    $params['date'] = $matches[1];
                    $params['time'] = $matches[2];

                    Log::debug('✅ Mapped ISO datetime parameter', [
                        'original_datetime' => $datetimeStr,
                        'mapped_date' => $params['date'],
                        'mapped_time' => $params['time'],
                        'call_id' => $callId
                    ]);
                }
                else {
                    Log::warning('⚠️ Failed to parse datetime parameter', [
                        'datetime_value' => $datetimeStr,
                        'call_id' => $callId
                    ]);
                }
            }

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

            // 🔧 FIX 2025-12-04: Validate customer_name is not a time expression
            // BUG: Retell agent sometimes sends time slot (e.g., "Acht Uhr") as customer_name
            // instead of actual customer name (e.g., "Franziska Siebert")
            if ($customerName && $this->isTimeExpression($customerName)) {
                Log::warning('⚠️ Time expression detected as customer_name - rejecting', [
                    'call_id' => $callId,
                    'invalid_name' => $customerName,
                    'fix' => 'Name will be extracted from transcript instead'
                ]);
                $customerName = ''; // Reset so name extraction from transcript takes over
            }

            // 🔥 NEW 2025-11-16: Customer Recognition - Support both preferred_staff_id and legacy mitarbeiter
            // ⚠️ V128: Moved earlier to be available for customer name validation
            $preferredStaffId = $params['preferred_staff_id'] ?? null;
            $mitarbeiterName = $params['mitarbeiter'] ?? null;

            // 🔧 FIX 2025-12-08: Convert staff name to UUID if Retell sends name instead of ID
            // Bug: Retell agent sometimes sends preferred_staff_id as name (e.g., "Mario Basler")
            // instead of UUID, causing DB query failure: WHERE id = 'Mario Basler'
            if ($preferredStaffId && !\Illuminate\Support\Str::isUuid($preferredStaffId)) {
                $staffNameInput = $preferredStaffId;
                $preferredStaffId = $this->mapStaffNameToId($staffNameInput, $callId);

                Log::info('🔄 Converted staff name to ID in start_booking', [
                    'call_id' => $callId,
                    'input_name' => $staffNameInput,
                    'resolved_staff_id' => $preferredStaffId,
                ]);
            }

            // ═══════════════════════════════════════════════════════════════════════════
            // ✅ FIX V128 2025-12-08: Enhanced Customer Name Validation
            // ═══════════════════════════════════════════════════════════════════════════
            // Problems identified in Call #85212 and #85213:
            // 1. Agent sends empty or "anonymous" customer_name
            // 2. Agent confuses "bei Mario Basler" (staff preference) with customer name
            // Solution: Backend validation to catch these issues and return informative errors
            // ═══════════════════════════════════════════════════════════════════════════

            // Check 1: Reject anonymous/placeholder names
            $invalidNames = ['anonymous', 'anonym', 'unbekannt', 'unknown', 'gast', 'guest', 'kunde', 'customer', 'n/a', 'na', '-', ''];
            $cleanedName = strtolower(trim($customerName));

            if (in_array($cleanedName, $invalidNames) || strlen($cleanedName) < 2) {
                Log::warning('⚠️ V128: Invalid customer_name detected - booking blocked', [
                    'call_id' => $callId,
                    'customer_name' => $customerName,
                    'cleaned_name' => $cleanedName,
                    'reason' => 'anonymous_or_placeholder',
                    'fix' => 'FIX V128: Agent must ask for customer name'
                ]);

                return $this->responseFormatter->formatResponse([
                    'success' => false,
                    'reason' => 'customer_name_required',
                    'message' => 'Ich brauche noch Ihren Namen für die Buchung. Auf welchen Namen darf ich den Termin eintragen?',
                    'call_id' => $callId,
                    'error_type' => 'validation'
                ]);
            }

            // Check 2: Reject if customer_name looks like a staff preference pattern
            // Patterns: "bei Mario Basler", "zu Udo Walz", "mit Frau Mueller"
            $staffPrefixPatterns = [
                '/^bei\s+/i',        // "bei Mario"
                '/^zu\s+/i',         // "zu Udo"
                '/^mit\s+/i',        // "mit Frau Mueller"
                '/^beim\s+/i',       // "beim Mario"
                '/^zur\s+/i',        // "zur Frau Meyer"
                '/^bei\s+der\s+/i',  // "bei der Frau Schmidt"
                '/^zum\s+/i',        // "zum Herrn Mueller"
            ];

            foreach ($staffPrefixPatterns as $pattern) {
                if (preg_match($pattern, $customerName)) {
                    // Extract the actual staff name for preference
                    $extractedStaffName = preg_replace($pattern, '', $customerName);

                    Log::warning('⚠️ V128: Staff preference detected in customer_name field', [
                        'call_id' => $callId,
                        'customer_name' => $customerName,
                        'extracted_staff' => $extractedStaffName,
                        'reason' => 'staff_preference_in_customer_name',
                        'fix' => 'FIX V128: Should be preferred_staff_id, not customer_name'
                    ]);

                    return $this->responseFormatter->formatResponse([
                        'success' => false,
                        'reason' => 'staff_preference_confused_with_customer_name',
                        'message' => "'{$customerName}' klingt nach einer Mitarbeiter-Präferenz. Wie ist Ihr Name für die Buchung?",
                        'detected_staff_preference' => $extractedStaffName,
                        'call_id' => $callId,
                        'error_type' => 'validation'
                    ]);
                }
            }

            // Check 3: Reject if customer_name matches a known staff member
            $potentialStaffName = preg_replace('/^(herr|frau|mr|mrs|ms)\s+/i', '', $cleanedName);
            $staffWithName = \App\Models\Staff::where('company_id', $companyId)
                ->where('is_active', true)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . $potentialStaffName . '%'])
                ->first();

            if ($staffWithName && $potentialStaffName === strtolower($staffWithName->name)) {
                Log::warning('⚠️ V128: Staff member name detected as customer_name', [
                    'call_id' => $callId,
                    'customer_name' => $customerName,
                    'matched_staff' => $staffWithName->name,
                    'staff_id' => $staffWithName->id,
                    'fix' => 'FIX V128: Customer likely wants this staff, not their name'
                ]);

                // Auto-set as preferred_staff_id if not already set
                if (!$preferredStaffId && !$mitarbeiterName) {
                    $preferredStaffId = $staffWithName->id;
                    Log::info('✅ V128: Auto-converted staff name to preferred_staff_id', [
                        'call_id' => $callId,
                        'staff_id' => $preferredStaffId,
                        'staff_name' => $staffWithName->name
                    ]);
                }

                return $this->responseFormatter->formatResponse([
                    'success' => false,
                    'reason' => 'staff_name_as_customer_name',
                    'message' => "'{$staffWithName->name}' ist einer unserer Mitarbeiter. Wie ist Ihr eigener Name für die Buchung?",
                    'detected_staff' => $staffWithName->name,
                    'call_id' => $callId,
                    'error_type' => 'validation'
                ]);
            }
            // ═══════════════════════════════════════════════════════════════════════════

            $customerEmail = $params['customer_email'] ?? '';
            $customerPhone = $params['customer_phone'] ?? '';
            $serviceId = $params['service_id'] ?? null;
            $serviceName = $params['service_name'] ?? $params['dienstleistung'] ?? null;
            $notes = $params['notes'] ?? '';

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

            // 🔧 FIX 2025-12-07: Use pinned staff from availability check (Highest Priority)
            // Ensures that the exact staff member found during checkAvailability is used for booking
            $pinnedStaffId = $callId ? Cache::get("call:{$callId}:pinned_staff_id") : null;
            if ($pinnedStaffId) {
                $preferredStaffId = $pinnedStaffId;
                Log::info('📌 Using pinned staff ID for booking (from cache)', [
                    'call_id' => $callId,
                    'staff_id' => $preferredStaffId,
                    'source' => 'checkAvailability'
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
                // 🔧 FIX 2025-11-19: Enhanced error logging with available services list
                Log::error('❌ Cannot book: No active service with Cal.com event type', [
                    'call_id' => $callId,
                    'requested_service_id' => $serviceId,
                    'requested_service_name' => $serviceName,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'service_found' => $service !== null,
                    'has_calcom_event_type' => $service?->calcom_event_type_id !== null,
                    'available_services' => \App\Models\Service::where('company_id', $companyId)
                        ->where('branch_id', $branchId)
                        ->where('is_active', true)
                        ->whereNotNull('calcom_event_type_id')
                        ->pluck('name', 'id')
                        ->toArray()
                ]);
                return $this->responseFormatter->error('Service nicht verfügbar für diese Filiale');
            }

            // ═══════════════════════════════════════════════════════════════════════════
            // ✅ FIX 2025-12-08: Staff-Service Capability Check (Prevent Silent Fallback)
            // ═══════════════════════════════════════════════════════════════════════════
            // Validate that requested staff can provide this service BEFORE booking.
            // This is a safety net - primary check happens in checkAvailability()
            // ═══════════════════════════════════════════════════════════════════════════
            if ($preferredStaffId) {
                $capabilityCheck = $this->validateStaffCanProvideService($preferredStaffId, $service, $callId);

                if (!$capabilityCheck['valid']) {
                    Log::warning('⚠️ bookAppointment: Staff cannot provide service - blocking booking', [
                        'call_id' => $callId,
                        'staff_id' => $preferredStaffId,
                        'staff_name' => $capabilityCheck['staff_name'],
                        'service_name' => $service->name,
                        'alternatives_count' => count($capabilityCheck['alternatives'] ?? []),
                        'fix' => 'FIX 2025-12-08: Prevent Silent Fallback in bookAppointment'
                    ]);

                    // Return informative error with alternatives
                    return $this->responseFormatter->formatResponse([
                        'success' => false,
                        'reason' => 'staff_cannot_provide_service',
                        'message' => $capabilityCheck['message'],
                        'requested_staff' => $capabilityCheck['staff_name'],
                        'requested_service' => $service->name,
                        'alternative_staff' => $capabilityCheck['alternatives'],
                        'call_id' => $callId
                    ]);
                }
            }
            // ═══════════════════════════════════════════════════════════════════════════

            Log::info('Using service for booking', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $service->calcom_event_type_id,
                'call_id' => $callId
            ]);

            // 🔒 FIX 2025-11-26: Check if slot is locked by ANOTHER call
            // PROBLEM: Alternatives are presented to caller, but during decision window
            // another caller could book the same slot via their own call
            // SOLUTION: Check alt_lock ownership before proceeding with booking
            if (config('features.slot_locking.enabled', false) && $appointmentTime) {
                $altLockKey = "alt_lock:c{$companyId}:s{$service->id}:t{$appointmentTime->format('YmdHi')}";
                $existingLock = Cache::get($altLockKey);

                if ($existingLock && ($existingLock['call_id'] ?? null) !== $callId) {
                    Log::warning('🔒 SLOT LOCKED BY ANOTHER CALL - Generating new alternatives', [
                        'call_id' => $callId,
                        'blocking_call' => $existingLock['call_id'] ?? 'unknown',
                        'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                        'lock_key' => $altLockKey,
                        'locked_at' => $existingLock['locked_at'] ?? null,
                    ]);

                    // Calculate service duration (including composite services)
                    $serviceDuration = $service->duration_minutes ?? 60;
                    if ($service->isComposite() && !empty($service->segments)) {
                        $serviceDuration = collect($service->segments)->sum(fn($s) => $s['durationMin'] ?? $s['duration'] ?? 0);
                    }

                    // Generate new alternatives from SlotIntelligence
                    $newAlternatives = $this->slotIntelligence->getNextAvailableSlots(
                        $callId,
                        $appointmentTime->format('Y-m-d'),
                        $service->id,
                        3,
                        $appointmentTime->format('H:i'),
                        $companyId,
                        $branchId,
                        $serviceDuration
                    );

                    $formattedAlts = array_map(function($slot) use ($appointmentTime) {
                        return [
                            'date' => $appointmentTime->format('Y-m-d'),
                            'time' => $slot['time_display'],
                            'formatted' => $slot['time_display'] . ' Uhr',
                        ];
                    }, $newAlternatives);

                    // Lock these new alternatives for this call
                    $this->lockAlternativeSlots(
                        $formattedAlts,
                        $companyId,
                        $service->id,
                        $serviceDuration,
                        $callId,
                        $customerPhone
                    );

                    return $this->responseFormatter->error(
                        'Dieser Termin wurde gerade von einem anderen Kunden reserviert. ' .
                        (!empty($formattedAlts)
                            ? 'Ich habe andere freie Termine gefunden.'
                            : 'Welche Zeit würde Ihnen alternativ passen?'),
                        [
                            'available' => false,
                            'reason' => 'slot_locked_by_other',
                            'alternatives' => $formattedAlts,
                        ]
                    );
                }
            }

            // 🔧 FIX 2025-11-14: Multi-Layer Race Condition Defense
            // LAYER 1: Prevention - Re-validate availability before booking
            // LAYER 2: Smart Recovery - Find new alternatives on race condition
            // LAYER 3: Quality Assurance - Comprehensive logging & metrics

            // 🚀 PHASE 1 OPTIMIZATION (2025-11-17): Check cached validation FIRST
            // If checkAvailability ran recently (<90s), trust cached result (saves 300-800ms)
            $validationCacheKey = "booking_validation:{$callId}:" . $appointmentTime->format('YmdHi');
            $cachedValidation = Cache::get($validationCacheKey);

            // Initialize $timeSinceCheck before if/else to prevent "Undefined variable" error
            $timeSinceCheck = 0;

            if ($cachedValidation && $cachedValidation['available']) {
                $cacheAge = now()->diffInSeconds($cachedValidation['validated_at']);

                Log::info('⚡ PHASE 1: Using cached availability validation - SKIPPING redundant Cal.com check', [
                    'call_id' => $callId,
                    'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                    'validated_at' => $cachedValidation['validated_at'],
                    'cache_age_seconds' => $cacheAge,
                    'optimization' => 'Eliminated 300-800ms re-check latency',
                    'performance_gain' => '37% faster booking'
                ]);

                // Skip redundant Cal.com check - proceed directly to booking
                $timeSinceCheck = 0; // Cache hit = instant (no time since last check)
            } else {
                // LAYER 1: Check time since last availability check
                $lastCheckTime = Cache::get("call:{$callId}:last_availability_check");
                $timeSinceCheck = $lastCheckTime ? now()->diffInSeconds($lastCheckTime) : 999;

                // 🔧 FIX 2025-11-25: Check if slot is a pre-validated alternative
                // PROBLEM: Customer selects alternative from check_availability, but re-check fails
                // SOLUTION: Trust alternatives that were validated within last 5 minutes
                $validatedAlternatives = Cache::get("call:{$callId}:validated_alternatives", []);
                $alternativesValidatedAt = Cache::get("call:{$callId}:alternatives_validated_at");
                $requestedSlotKey = $appointmentTime->format('Y-m-d H:i');

                if (!empty($validatedAlternatives) && $alternativesValidatedAt) {
                    $alternativeAge = now()->diffInSeconds($alternativesValidatedAt);

                    if (in_array($requestedSlotKey, $validatedAlternatives) && $alternativeAge < 300) {
                        Log::info('⚡ SKIP RE-CHECK: Slot is a pre-validated alternative', [
                            'call_id' => $callId,
                            'requested_time' => $requestedSlotKey,
                            'validated_at' => $alternativesValidatedAt->toDateTimeString(),
                            'age_seconds' => $alternativeAge,
                            'validated_alternatives' => $validatedAlternatives,
                            'optimization' => 'Skipping Cal.com re-check for pre-validated alternative'
                        ]);

                        // Trust the pre-validated alternative - proceed to booking
                        $timeSinceCheck = 0;
                    }
                }

                // 🔧 FIX 2025-12-14: Skip Layer 1 re-check if we have an active slot reservation
                // ROOT CAUSE (Call #89576): reserve_slot created reservation, but Layer 1 re-check
                // was NOT checking $hasActiveReservation, causing Cal.com to say "not available"
                // even though the slot was reserved. The reservation HOLDS the slot - no need to re-verify.
                if ($timeSinceCheck > 30 && !$hasActiveReservation) {
                    Log::info('⏱️ Re-validating availability before booking (>30s since last check, no cache)', [
                        'call_id' => $callId,
                        'time_since_check' => $timeSinceCheck,
                        'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                        'cache_miss' => true,
                        'has_reservation' => false
                    ]);

                    // Quick availability re-check for exact requested time
                    try {
                        // 🔧 FIX 2025-12-01: Use segment A event type for composite services
                        // ROOT CAUSE: Parent event type (3757758) returns different slots than
                        // segment A child event type (3976747) which is used for booking
                        $reCheckEventTypeId = $service->calcom_event_type_id;

                        if ($service->isComposite() && !empty($service->segments)) {
                            // Get first available staff for composite re-check
                            $reCheckStaff = $preferredStaffId
                                ? \App\Models\Staff::find($preferredStaffId)
                                : \App\Models\Staff::where('branch_id', $branchId)
                                    ->where('is_active', true)
                                    ->whereHas('services', fn($q) => $q->where('service_id', $service->id))
                                    ->first();

                            if ($reCheckStaff) {
                                $segmentAMapping = \App\Models\CalcomEventMap::where('service_id', $service->id)
                                    ->where('segment_key', 'A')
                                    ->where('staff_id', $reCheckStaff->id)
                                    ->first();

                                if ($segmentAMapping) {
                                    $reCheckEventTypeId = $segmentAMapping->child_event_type_id
                                        ?? $segmentAMapping->event_type_id
                                        ?? $reCheckEventTypeId;

                                    Log::info('🎨 Using segment A event type for composite service re-check', [
                                        'call_id' => $callId,
                                        'service_id' => $service->id,
                                        'staff_id' => $reCheckStaff->id,
                                        'original_event_type' => $service->calcom_event_type_id,
                                        'segment_a_event_type' => $reCheckEventTypeId,
                                    ]);
                                }
                            }
                        }

                        // 🔧 FIX 2025-11-16: Cal.com expects Y-m-d format, not ISO 8601
                        $reCheckResponse = $this->calcomService->getAvailableSlots(
                            $reCheckEventTypeId,
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
                                'time_since_check' => $timeSinceCheck,
                                'event_type_checked' => $reCheckEventTypeId,
                                'slots_available' => count($reCheckSlots),
                            ]);

                            // Find new alternatives
                            // 🔧 FIX 2025-12-01: Use composite service alternatives for composite services
                            if ($service->isComposite() && !empty($service->segments) && isset($reCheckStaff)) {
                                $alternatives = $this->findAlternativesForCompositeService(
                                    $service,
                                    $reCheckStaff,
                                    $appointmentTime,
                                    $branchId
                                );
                            } else {
                                // 🔧 FIX 2025-11-14: Correct parameter order for findAlternatives()
                                // 🔒 FIX 2025-11-19: Set tenant context before calling findAlternatives()
                                $alternatives = $this->alternativeFinder
                                    ->setTenantContext($companyId, $branchId)
                                    ->findAlternatives(
                                        $appointmentTime,                   // Carbon $desiredDateTime
                                        $service->duration_minutes,         // int $durationMinutes
                                        $reCheckEventTypeId,                // int $eventTypeId
                                        $customerId                         // ?int $customerId
                                    );
                            }

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
                            'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                            'event_type_checked' => $reCheckEventTypeId,
                        ]);

                    } catch (\Exception $e) {
                        Log::warning('Re-check failed, proceeding with booking anyway', [
                            'call_id' => $callId,
                            'error' => $e->getMessage()
                        ]);
                    }
                } elseif ($timeSinceCheck > 30 && $hasActiveReservation) {
                    // 🔧 FIX 2025-12-14: Log when Layer 1 re-check is skipped due to active reservation
                    Log::info('🔒 SKIP Layer 1 re-check: Active slot reservation holds the slot', [
                        'call_id' => $callId,
                        'time_since_check' => $timeSinceCheck,
                        'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                        'reservation_uid' => $existingReservation['uid'] ?? null,
                        'reservation_until' => $existingReservation['until'] ?? null,
                        'fix' => 'FIX 2025-12-14: Prevents false "slot taken" when reservation exists'
                    ]);
                }
            }

            // 🔒 REDIS LOCK VALIDATION (2025-11-25) - Phase 2 Fix COMPLETE
            // CRITICAL: Validate lock ownership before booking to prevent race conditions
            //
            // Flow:
            // 1. Generate lock key from booking details
            // 2. Check if slot is locked
            // 3. If locked by this call → proceed (we own the reservation)
            // 4. If locked by another call → reject (race condition prevention)
            // 5. If not locked → warn but proceed (backwards compatibility)
            // 6. After successful booking → release lock
            //
            $lockKey = $this->lockService->generateLockKey(
                $companyId,
                $service->id,
                $appointmentTime
            );

            $lockInfo = $this->lockService->getLockInfo($lockKey);
            $lockValidationResult = null;

            if ($lockInfo) {
                // Slot is locked - check ownership
                $lockValidationResult = $this->lockService->validateLock($lockKey, $callId);

                if (!$lockValidationResult['valid']) {
                    // Another call owns this slot!
                    Log::error('❌ Slot locked by another call - RACE CONDITION PREVENTED', [
                        'call_id' => $callId,
                        'lock_key' => $lockKey,
                        'locked_by' => $lockInfo['call_id'] ?? 'unknown',
                        'reason' => $lockValidationResult['reason'],
                        'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                        'fix' => 'FIX 2025-11-25: Redis lock prevents double bookings'
                    ]);

                    // Find new alternatives for the customer
                    $alternatives = $this->alternativeFinder
                        ->setTenantContext($companyId, $branchId)
                        ->findAlternatives(
                            $appointmentTime,
                            $service->duration_minutes ?? $duration,
                            $service->calcom_event_type_id,
                            $customerId
                        );

                    return $this->responseFormatter->error(
                        'Dieser Termin wurde gerade vergeben. Ich habe neue Alternativen für Sie.',
                        [
                            'available' => false,
                            'status' => 'slot_locked_by_other',
                            'reason' => $lockValidationResult['reason'],
                            'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                            'alternatives' => $alternatives['alternatives'] ?? [],
                            'message' => 'Leider wurde dieser Termin gerade von einem anderen Anrufer reserviert.'
                        ]
                    );
                }

                Log::info('✅ Lock validated - this call owns the slot reservation', [
                    'call_id' => $callId,
                    'lock_key' => $lockKey,
                    'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                    'locked_at' => $lockInfo['locked_at'] ?? 'unknown',
                    'fix' => 'FIX 2025-11-25: Redis lock validation successful'
                ]);
            } else {
                // No lock exists - check cached validated alternatives as fallback
                $validatedAlternatives = Cache::get("call:{$callId}:validated_alternatives", []);
                $requestedSlotKey = $appointmentTime->format('Y-m-d H:i');

                if (in_array($requestedSlotKey, $validatedAlternatives)) {
                    Log::info('✅ Slot is in validated alternatives (no lock, but pre-validated)', [
                        'call_id' => $callId,
                        'requested_time' => $requestedSlotKey,
                        'validated_alternatives' => $validatedAlternatives
                    ]);
                } else {
                    Log::warning('⚠️ Booking slot without lock (backwards compatibility mode)', [
                        'call_id' => $callId,
                        'lock_key' => $lockKey,
                        'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                        'note' => 'Slot was not locked during check_availability - race condition possible'
                    ]);
                }
            }

            // 🎨 COMPOSITE SERVICE SUPPORT (2025-11-27)
            // If service is composite (e.g., Dauerwelle with 6 segments), use CompositeBookingService
            // This creates multiple linked appointments with proper Cal.com segment bookings
            if ($service->isComposite() && !empty($service->segments)) {
                Log::info('🎨 COMPOSITE SERVICE DETECTED - Using specialized booking flow', [
                    'call_id' => $callId,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'segments_count' => count($service->segments),
                    'total_duration' => $service->duration_minutes,
                    'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                    'segment_names' => collect($service->segments)->pluck('name')->toArray()
                ]);

                return $this->bookCompositeAppointment(
                    $service,
                    $appointmentTime,
                    $customerName,
                    $customerEmail,
                    $customerPhone,
                    $preferredStaffId,
                    $callId,
                    $companyId,
                    $branchId,
                    $lockKey ?? null,
                    $lockInfo ?? null
                );
            }

            // 🚀 PHASE 2 OPTIMIZATION (2025-11-17): ASYNC CAL.COM SYNC
            // Check if async sync is enabled (env flag)
            $asyncSyncEnabled = env('ASYNC_CALCOM_SYNC', false);

            if ($asyncSyncEnabled) {
                // ASYNC PATH: Create appointment immediately, sync to Cal.com in background
                Log::info('⚡ PHASE 2: Using ASYNC Cal.com sync', [
                    'call_id' => $callId,
                    'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                    'optimization' => 'Deferred Cal.com CREATE to background job',
                    'performance_gain' => '97% faster booking (3s → 100ms)'
                ]);

                try {
                    // Get call record for customer resolution
                    $call = $this->callLifecycle->findCallByRetellId($callId);

                    if (!$call) {
                        Log::error('❌ Call not found for async booking', ['call_id' => $callId]);
                        return $this->responseFormatter->error('Call context not available');
                    }

                    // Ensure customer exists or create from call context
                    $customer = $this->customerResolver->ensureCustomerFromCall($call, $customerName, $customerEmail);

                    // 🔒 SECURITY FIX 2025-11-19: PRE-SYNC VALIDATION (ASYNC path)
                    // 🔧 FIX 2025-11-25: TIMEZONE BUG - Database stores times in local timezone (Europe/Berlin)
                    // DO NOT convert to UTC - compare in local timezone for consistency
                    // Check for conflicting appointments BEFORE creating local appointment
                    // Uses pessimistic locking to prevent race conditions

                    // Keep times in local timezone since DB stores Berlin times
                    $startTimeLocal = $appointmentTime->copy()->setTimezone('Europe/Berlin');
                    $endTimeLocal = $appointmentTime->copy()
                        ->addMinutes($service->duration_minutes ?? 60)
                        ->setTimezone('Europe/Berlin');

                    $conflictCheck = \App\Models\Appointment::where('branch_id', $branchId)
                        ->where('company_id', $companyId)
                        ->where(function($query) use ($startTimeLocal, $endTimeLocal) {
                            // Check for TRUE overlap (strict inequalities prevent back-to-back false positives)
                            // Back-to-back appointments (ends_at == next starts_at) should be ALLOWED
                            $query->where(function($q) use ($startTimeLocal, $endTimeLocal) {
                                // New appointment starts during existing appointment
                                // Use strict < for ends_at to allow back-to-back
                                $q->where('starts_at', '<', $startTimeLocal)
                                  ->where('ends_at', '>', $startTimeLocal);
                            })->orWhere(function($q) use ($startTimeLocal, $endTimeLocal) {
                                // New appointment ends during existing appointment
                                // Use strict > for starts_at to allow back-to-back
                                $q->where('starts_at', '<', $endTimeLocal)
                                  ->where('ends_at', '>', $startTimeLocal);
                            });
                        })
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->lockForUpdate()
                        ->first();

                    if ($conflictCheck) {
                        Log::warning('🚨 PRE-SYNC CONFLICT: Slot already booked (ASYNC)', [
                            'call_id' => $callId,
                            'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                            'existing_appointment_id' => $conflictCheck->id,
                            'existing_customer' => $conflictCheck->customer_id,
                            'company_id' => $companyId,
                            'branch_id' => $branchId
                        ]);

                        return $this->responseFormatter->error(
                            'Dieser Termin wurde gerade vergeben. Bitte wählen Sie einen anderen Zeitpunkt.'
                        );
                    }

                    Log::debug('✅ PRE-SYNC VALIDATION passed: No conflicts (ASYNC)', [
                        'call_id' => $callId,
                        'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                        'company_id' => $companyId,
                        'branch_id' => $branchId
                    ]);

                    // 🔧 CRITICAL FIX 2025-11-22: Auto-assign staff if not specified
                    // This prevents appointments being created with staff_id = NULL
                    // which causes Cal.com sync to fail (requires hostId)
                    if (!$preferredStaffId) {
                        Log::info('🔍 No staff preference specified, auto-assigning available staff (ASYNC)', [
                            'service_id' => $service->id,
                            'service_name' => $service->name,
                            'branch_id' => $branchId,
                            'time' => $appointmentTime->format('Y-m-d H:i')
                        ]);

                        // Find available staff for this service
                        $endTime = $appointmentTime->copy()->addMinutes($service->duration_minutes);

                        $availableStaff = \App\Models\Staff::where('branch_id', $branchId)
                            ->where('is_active', true)
                            ->whereHas('services', function($q) use ($service) {
                                $q->where('service_id', $service->id)
                                  ->where('service_staff.is_active', true);
                            })
                            ->whereDoesntHave('appointments', function($q) use ($appointmentTime, $endTime) {
                                $q->where(function($query) use ($appointmentTime, $endTime) {
                                    $query->where('starts_at', '<', $endTime)
                                          ->where('ends_at', '>', $appointmentTime);
                                })
                                ->whereIn('status', ['scheduled', 'confirmed', 'booked']);
                            })
                            ->first();

                        if ($availableStaff) {
                            $preferredStaffId = $availableStaff->id;
                            Log::info('✅ Auto-assigned staff (ASYNC)', [
                                'staff_id' => $preferredStaffId,
                                'staff_name' => $availableStaff->name,
                                'calcom_user_id' => $availableStaff->calcom_user_id
                            ]);
                        } else {
                            Log::error('❌ No available staff found for booking (ASYNC)', [
                                'service_id' => $service->id,
                                'service_name' => $service->name,
                                'branch_id' => $branchId,
                                'time_range' => $appointmentTime->format('Y-m-d H:i') . ' - ' . $endTime->format('H:i')
                            ]);

                            return $this->responseFormatter->error(
                                'Leider ist zu dieser Zeit kein Mitarbeiter verfügbar. Bitte wählen Sie einen anderen Termin.'
                            );
                        }
                    }

                    // 🔧 FIX 2025-12-03: Check for existing appointment with same call_id (idempotency)
                    // Prevents duplicate appointments when Retell sends duplicate function calls
                    $existingCallAppointment = Appointment::where('call_id', $call->id)
                        ->whereIn('status', ['confirmed', 'booked', 'pending', 'scheduled'])
                        ->first();

                    if ($existingCallAppointment) {
                        Log::warning('🛡️ Duplicate booking attempt blocked (ASYNC) - returning existing appointment', [
                            'call_id' => $call->id,
                            'retell_call_id' => $callId,
                            'existing_appointment_id' => $existingCallAppointment->id,
                            'existing_status' => $existingCallAppointment->status,
                            'existing_time' => $existingCallAppointment->starts_at->format('Y-m-d H:i')
                        ]);

                        // Return success with existing appointment (idempotent response)
                        return $this->responseFormatter->success([
                            'booked' => true,
                            'appointment_id' => $existingCallAppointment->id,
                            'message' => "Perfekt! Ihr Termin am {$existingCallAppointment->starts_at->format('d.m.')} um {$existingCallAppointment->starts_at->format('H:i')} Uhr ist bereits gebucht.",
                            'appointment_time' => $existingCallAppointment->starts_at->format('Y-m-d H:i'),
                            'confirmation' => "Sie erhalten eine Bestätigung per SMS.",
                            'sync_mode' => 'async',
                            'idempotent_response' => true  // Flag for debugging
                        ]);
                    }

                    // Create local appointment with status "pending_sync"
                    $appointment = new Appointment();

                    // 🔧 FIX 2025-12-14: Include reservation_uid in metadata for sync job
                    // ROOT CAUSE (Call #89689): Sync job re-checked availability AFTER reserve_slot
                    // blocked the slot, causing "All slots blocked" error. By passing the
                    // reservation_uid, the sync job can skip validation and use the reservation.
                    $appointmentMetadata = [
                        'call_id' => $call->id,
                        'retell_call_id' => $callId,
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'customer_phone' => $customerPhone,
                        'booked_via' => 'retell_ai_async',
                        'sync_method' => 'async_job',
                        'created_at' => now()->toIso8601String()
                    ];

                    // Add reservation UID if reserve_slot was called
                    if ($hasActiveReservation && isset($existingReservation['uid'])) {
                        $appointmentMetadata['reservation_uid'] = $existingReservation['uid'];
                        $appointmentMetadata['reservation_until'] = $existingReservation['until'] ?? null;
                        Log::info('🔒 Including reservation_uid in appointment metadata for sync job', [
                            'call_id' => $callId,
                            'reservation_uid' => $existingReservation['uid'],
                        ]);
                    }

                    $appointment->forceFill([
                        'customer_id' => $customer->id,
                        'company_id' => $customer->company_id,
                        'branch_id' => $branchId,
                        'service_id' => $service->id,
                        'staff_id' => $preferredStaffId,
                        'call_id' => $call->id,
                        'starts_at' => $appointmentTime,
                        // CRITICAL FIX 2025-11-21: Use service duration, not parameter default
                        'ends_at' => $appointmentTime->copy()->addMinutes($service->duration_minutes),
                        'status' => 'confirmed',  // User-facing status (already confirmed)
                        'calcom_sync_status' => 'pending',  // Internal sync status
                        // 🔧 FIX 2025-11-19: sync_origin = 'system' for ASYNC (Cal.com sync happens later in job)
                        'sync_origin' => 'system',  // Will be updated to 'calcom' after background job succeeds
                        'source' => 'retell_phone',
                        'booking_type' => 'single',
                        'notes' => $notes,
                        // 🔧 FIX 2025-12-14: Don't json_encode - model cast 'array' handles it
                        // BUG: Double-encoding caused sync job to read metadata as string
                        'metadata' => $appointmentMetadata
                    ]);
                    $appointment->save();

                    Log::info('✅ Appointment created (async mode) - dispatching sync job', [
                        'appointment_id' => $appointment->id,
                        'call_id' => $call->id,
                        'customer_id' => $customer->id,
                        'status' => $appointment->status,
                        'sync_status' => $appointment->calcom_sync_status
                    ]);

                    // Load service relation (may be needed for downstream processing)
                    $appointment->load('service', 'customer', 'company');

                    // ═══════════════════════════════════════════════════════════════════════════
                    // 🔧 FIX 2025-12-01: REMOVED direct sync job dispatch - causes DOUBLE BOOKING!
                    // ═══════════════════════════════════════════════════════════════════════════
                    // The Cal.com sync is now handled EXCLUSIVELY by the AppointmentObserver:
                    // 1. AppointmentObserver::created() fires AppointmentBooked event
                    // 2. AppointmentBooked event triggers SyncToCalcomOnBooked listener
                    // 3. Listener dispatches SyncAppointmentToCalcomJob
                    //
                    // REMOVED CODE (caused double booking bug):
                    // \App\Jobs\SyncAppointmentToCalcomJob::dispatch($appointment, 'create');
                    //
                    // RCA: With both dispatches active, two sync jobs ran simultaneously:
                    // - Job #1 booked slot A (e.g., 16:55)
                    // - Job #2 found slot A blocked, booked slot B (e.g., 17:50)
                    // Result: TWO Cal.com bookings for ONE appointment
                    // ═══════════════════════════════════════════════════════════════════════════

                    // 🔧 FIX 2025-11-25 (Bug 2): Release slot lock after successful booking
                    if ($lockInfo) {
                        $this->lockService->releaseLock($lockKey, $callId, $appointment->id);
                        Log::info('🔓 Slot lock released after successful booking (ASYNC)', [
                            'call_id' => $callId,
                            'lock_key' => $lockKey,
                            'appointment_id' => $appointment->id
                        ]);
                    }

                    // 🔧 FIX 2025-11-26: Update appointment_made flag on Call record
                    // BUGFIX: Changed from has_appointment (wrong) to appointment_made (correct DB column)
                    // 🔧 FIX 2025-12-01: Wrap in try-catch to prevent non-critical failure from failing booking

                    // 🔍 CHECKPOINT 1: Before Call-Flag Update (ASYNC booking)
                    Log::channel('stack')->info('🔍 CHECKPOINT:BEFORE_FLAGS_UPDATE', [
                        'location' => 'ASYNC_BOOKING',
                        'call_id' => $callId,
                        'appointment_id' => $appointment->id ?? null,
                        'current_flags' => [
                            'appointment_made' => $call?->appointment_made,
                            'converted_appointment_id' => $call?->converted_appointment_id,
                        ],
                        'timestamp' => now()->toIso8601String(),
                    ]);

                    if ($call) {
                        try {
                            $call->update([
                                'appointment_made' => true,
                                'converted_appointment_id' => $appointment->id,
                            ]);
                            Log::info('✅ Updated call appointment_made flag (ASYNC)', [
                                'call_id' => $callId,
                                'appointment_id' => $appointment->id
                            ]);

                            // 🔍 CHECKPOINT 2: After Call-Flag Update SUCCESS
                            Log::channel('stack')->info('🔍 CHECKPOINT:AFTER_FLAGS_UPDATE', [
                                'location' => 'ASYNC_BOOKING',
                                'call_id' => $callId,
                                'update_success' => true,
                                'flags_after' => [
                                    'appointment_made' => $call->fresh()->appointment_made,
                                    'converted_appointment_id' => $call->fresh()->converted_appointment_id,
                                ],
                                'timestamp' => now()->toIso8601String(),
                            ]);
                        } catch (\Exception $callUpdateException) {
                            // Non-critical: Log but don't fail the booking
                            Log::warning('⚠️ Failed to update call flags (non-blocking)', [
                                'call_id' => $callId,
                                'appointment_id' => $appointment->id,
                                'error' => $callUpdateException->getMessage()
                            ]);

                            // 🔍 CHECKPOINT 2: After Call-Flag Update FAILED
                            Log::channel('stack')->info('🔍 CHECKPOINT:AFTER_FLAGS_UPDATE', [
                                'location' => 'ASYNC_BOOKING',
                                'call_id' => $callId,
                                'update_success' => false,
                                'error' => $callUpdateException->getMessage(),
                                'timestamp' => now()->toIso8601String(),
                            ]);
                        }
                    }

                    // Return SUCCESS immediately (user doesn't wait for Cal.com)
                    return $this->responseFormatter->success([
                        'booked' => true,
                        'appointment_id' => $appointment->id,
                        'message' => "Perfekt! Ihr Termin am {$appointmentTime->format('d.m.')} um {$appointmentTime->format('H:i')} Uhr ist gebucht.",
                        'appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                        'confirmation' => "Sie erhalten eine Bestätigung per SMS.",
                        'sync_mode' => 'async'
                    ]);

                } catch (\Exception $e) {
                    // 🔧 FIX 2025-12-01: Check if appointment was already created before returning error
                    // This handles the case where appointment was saved but a subsequent operation failed
                    if (isset($appointment) && $appointment->id) {
                        Log::warning('⚠️ Async booking partial success: appointment created but error occurred', [
                            'call_id' => $callId,
                            'appointment_id' => $appointment->id,
                            'error' => $e->getMessage(),
                            'note' => 'Returning success since appointment exists'
                        ]);

                        return $this->responseFormatter->success([
                            'booked' => true,
                            'appointment_id' => $appointment->id,
                            'message' => "Ihr Termin am {$appointmentTime->format('d.m.')} um {$appointmentTime->format('H:i')} Uhr ist gebucht.",
                            'appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                            'confirmation' => "Sie erhalten eine Bestätigung per SMS.",
                            'sync_mode' => 'async',
                            'partial_success' => true
                        ]);
                    }

                    Log::error('❌ Async booking failed', [
                        'call_id' => $callId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    return $this->responseFormatter->error(
                        'Ein Fehler ist beim Buchen aufgetreten. Bitte versuchen Sie es erneut.'
                    );
                }
            }

            // SYNC PATH (FALLBACK): Original synchronous booking

            // 🔧 FIX 2025-12-03: Check for existing appointment with same call_id (idempotency)
            // This check runs BEFORE conflict detection and BEFORE Cal.com API call
            // Prevents duplicate appointments when Retell sends duplicate function calls
            $call = $this->callLifecycle->findCallByRetellId($callId);
            if ($call) {
                $existingCallAppointment = Appointment::where('call_id', $call->id)
                    ->whereIn('status', ['confirmed', 'booked', 'pending', 'scheduled'])
                    ->first();

                if ($existingCallAppointment) {
                    Log::warning('🛡️ Duplicate booking attempt blocked (SYNC) - returning existing appointment', [
                        'call_id' => $call->id,
                        'retell_call_id' => $callId,
                        'existing_appointment_id' => $existingCallAppointment->id,
                        'existing_status' => $existingCallAppointment->status,
                        'existing_time' => $existingCallAppointment->starts_at->format('Y-m-d H:i')
                    ]);

                    // Return success with existing appointment (idempotent response)
                    return $this->responseFormatter->success([
                        'booked' => true,
                        'appointment_id' => $existingCallAppointment->id,
                        'message' => "Perfekt! Ihr Termin am {$existingCallAppointment->starts_at->format('d.m.')} um {$existingCallAppointment->starts_at->format('H:i')} Uhr ist bereits gebucht.",
                        'appointment_time' => $existingCallAppointment->starts_at->format('Y-m-d H:i'),
                        'confirmation' => "Sie erhalten eine Bestätigung per SMS.",
                        'sync_mode' => 'sync',
                        'idempotent_response' => true  // Flag for debugging
                    ]);
                }
            }

            // 🔒 SECURITY FIX 2025-11-19: PRE-SYNC VALIDATION (SYNC path)
            // 🔧 FIX 2025-11-25: TIMEZONE BUG - Database stores times in local timezone (Europe/Berlin)
            // DO NOT convert to UTC - compare in local timezone for consistency
            // Check for conflicting appointments BEFORE Cal.com API call
            // Uses pessimistic locking to prevent race conditions

            // Keep times in local timezone since DB stores Berlin times
            $startTimeLocal = $appointmentTime->copy()->setTimezone('Europe/Berlin');
            $endTimeLocal = $appointmentTime->copy()
                ->addMinutes($service->duration_minutes ?? 60)
                ->setTimezone('Europe/Berlin');

            $conflictCheck = \App\Models\Appointment::where('branch_id', $branchId)
                ->where('company_id', $companyId)
                ->where(function($query) use ($startTimeLocal, $endTimeLocal) {
                    // Check for TRUE overlap (strict inequalities prevent back-to-back false positives)
                    // Back-to-back appointments (ends_at == next starts_at) should be ALLOWED
                    $query->where(function($q) use ($startTimeLocal, $endTimeLocal) {
                        // New appointment starts during existing appointment
                        // Use strict < for ends_at to allow back-to-back
                        $q->where('starts_at', '<', $startTimeLocal)
                          ->where('ends_at', '>', $startTimeLocal);
                    })->orWhere(function($q) use ($startTimeLocal, $endTimeLocal) {
                        // New appointment ends during existing appointment
                        // Use strict > for starts_at to allow back-to-back
                        $q->where('starts_at', '<', $endTimeLocal)
                          ->where('ends_at', '>', $startTimeLocal);
                    });
                })
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->lockForUpdate()
                ->first();

            if ($conflictCheck) {
                // 🔧 FIX 2025-11-26: Check if this is OUR OWN booking (duplicate call scenario)
                // Prevents false "slot taken" message when Retell calls start_booking twice
                $conflictMetadata = is_string($conflictCheck->metadata)
                    ? json_decode($conflictCheck->metadata, true)
                    : ($conflictCheck->metadata ?? []);
                $conflictRetellCallId = $conflictMetadata['retell_call_id'] ?? null;

                if ($conflictRetellCallId && $conflictRetellCallId === $callId) {
                    Log::info('✅ DUPLICATE DETECTED: Conflict is OUR OWN booking - returning success', [
                        'call_id' => $callId,
                        'appointment_id' => $conflictCheck->id,
                        'original_created_at' => $conflictCheck->created_at,
                        'conflict_retell_call_id' => $conflictRetellCallId
                    ]);

                    // 🔧 FIX 2025-11-26: Cache this result for subsequent duplicate calls
                    $duplicateSuccessResponse = [
                        'booked' => true,
                        'appointment_id' => $conflictCheck->id,
                        'message' => "Ihr Termin ist bereits gebucht.",
                        'appointment_time' => $conflictCheck->starts_at->format('Y-m-d H:i'),
                        'duplicate_detected' => true,
                        'sync_mode' => 'already_synced'
                    ];

                    Cache::put($idempotencyCacheKey, [
                        'success' => true,
                        'response' => ['success' => true, 'data' => $duplicateSuccessResponse],
                        'appointment_id' => $conflictCheck->id,
                        'cached_at' => now()->toIso8601String()
                    ], 300);

                    return $this->responseFormatter->success($duplicateSuccessResponse);
                }

                // Truly conflicting appointment from different call/source
                Log::warning('🚨 PRE-SYNC CONFLICT: Slot already booked by another source (SYNC)', [
                    'call_id' => $callId,
                    'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                    'existing_appointment_id' => $conflictCheck->id,
                    'existing_customer' => $conflictCheck->customer_id,
                    'conflict_call_id' => $conflictRetellCallId ?? 'unknown',
                    'company_id' => $companyId,
                    'branch_id' => $branchId
                ]);

                return $this->responseFormatter->error(
                    'Dieser Termin wurde gerade vergeben. Bitte wählen Sie einen anderen Zeitpunkt.'
                );
            }

            Log::debug('✅ PRE-SYNC VALIDATION passed: No conflicts (SYNC)', [
                'call_id' => $callId,
                'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                'company_id' => $companyId,
                'branch_id' => $branchId
            ]);

            // LAYER 2: Booking attempt with smart retry
            $maxRetries = 1;
            $attempt = 0;
            $booking = null;
            $lastException = null;

            while ($attempt <= $maxRetries && !$booking) {
                $attempt++;

                try {
                    // 🔧 FIX 2025-12-13: Skip double-check if we have an active reservation
                    // The slot is already held in Cal.com, no need to re-verify
                    if ($hasActiveReservation) {
                        Log::info('🔒 RESERVATION ACTIVE: Skipping double-check (slot already held)', [
                            'call_id' => $callId,
                            'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                            'reservation_uid' => $existingReservation['uid'] ?? null,
                        ]);
                    } else {
                        // 🔧 FIX 2025-11-21: DOUBLE-CHECK PATTERN
                        // Re-check availability with Cal.com API immediately before booking
                        // This eliminates the race condition window between check_availability and start_booking
                        Log::debug('🔄 DOUBLE-CHECK: Re-verifying availability before booking', [
                            'call_id' => $callId,
                            'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                            'service' => $service->name
                        ]);

                        try {
                            $freshAvailability = $this->calcomService->getAvailableSlots(
                                eventTypeId: $service->calcom_event_type_id,
                                startDate: $appointmentTime->format('Y-m-d'),
                                endDate: $appointmentTime->format('Y-m-d'),
                                teamId: $company->calcom_team_id ?? null
                            );

                            $requestedSlot = $appointmentTime->format('Y-m-d\TH:i:s');
                            $slotAvailable = false;

                            foreach ($freshAvailability as $slot) {
                                if (str_starts_with($slot['time'], $requestedSlot)) {
                                    $slotAvailable = true;
                                    break;
                                }
                            }

                            if (!$slotAvailable) {
                                Log::warning('⚠️ DOUBLE-CHECK FAILED: Slot no longer available', [
                                    'call_id' => $callId,
                                    'requested_time' => $appointmentTime->format('Y-m-d H:i'),
                                    'service' => $service->name
                                ]);

                                return $this->responseFormatter->error(
                                    'Dieser Termin wurde gerade vergeben. Möchten Sie einen anderen Zeitpunkt wählen?'
                                );
                            }

                            Log::info('✅ DOUBLE-CHECK PASSED: Slot confirmed available', [
                                'call_id' => $callId,
                                'requested_time' => $appointmentTime->format('Y-m-d H:i')
                            ]);

                        } catch (\Exception $doubleCheckError) {
                            // If double-check fails, log but continue (don't block booking)
                            Log::warning('⚠️ DOUBLE-CHECK ERROR: Continuing with booking attempt', [
                                'call_id' => $callId,
                                'error' => $doubleCheckError->getMessage()
                            ]);
                        }
                    }

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
                                // 🔒 FIX 2025-11-19: Set tenant context before calling findAlternatives()
                                $alternatives = $this->alternativeFinder
                                    ->setTenantContext($companyId, $branchId)
                                    ->findAlternatives(
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

                // ═══════════════════════════════════════════════════════════════════════════
                // ✅ FIX 2025-12-08: Extract staff from Cal.com host response (SYNC path)
                // ═══════════════════════════════════════════════════════════════════════════
                // PROBLEM: staff_id was NULL when agent didn't send 'mitarbeiter' parameter
                //          but Cal.com successfully assigned a host to the booking
                // SOLUTION: Use CalcomHostMappingService to resolve staff from Cal.com hosts
                // RCA: Call #83396 - Heinrich Heine booking had NULL staff_id despite
                //      Cal.com assigning host 1414768 (Udo Walz)
                // ═══════════════════════════════════════════════════════════════════════════
                if (!$preferredStaffId && $bookingData && $companyId) {
                    try {
                        $hostMappingService = app(\App\Services\CalcomHostMappingService::class);
                        $calcomData = $bookingData['data'] ?? $bookingData;
                        $hostData = $hostMappingService->extractHostFromBooking($calcomData);

                        if ($hostData && isset($hostData['id'])) {
                            $hostContext = new \App\Services\Strategies\HostMatchContext(
                                companyId: $companyId,
                                branchId: $branchId,
                                serviceId: $service?->id,
                                calcomBooking: $calcomData
                            );

                            $resolvedStaffId = $hostMappingService->resolveStaffForHost($hostData, $hostContext);

                            if ($resolvedStaffId) {
                                $preferredStaffId = $resolvedStaffId;

                                Log::info('✅ SYNC: Staff resolved from Cal.com host', [
                                    'call_id' => $callId,
                                    'staff_id' => $resolvedStaffId,
                                    'calcom_host_id' => $hostData['id'],
                                    'host_name' => $hostData['name'] ?? 'unknown',
                                    'host_email' => $hostData['email'] ?? null,
                                    'booking_id' => $calcomBookingId,
                                    'fix' => 'FIX 2025-12-08: SYNC path staff resolution'
                                ]);
                            } else {
                                Log::warning('⚠️ SYNC: No staff match for Cal.com host', [
                                    'call_id' => $callId,
                                    'calcom_host_id' => $hostData['id'],
                                    'host_name' => $hostData['name'] ?? null,
                                    'host_email' => $hostData['email'] ?? null,
                                    'note' => 'Check CalcomHostMapping table for missing entry'
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        // Non-blocking: Log error but continue with booking
                        // Staff assignment is important but not critical for booking success
                        Log::warning('⚠️ SYNC: Staff resolution failed (non-blocking)', [
                            'call_id' => $callId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'note' => 'Booking will proceed with staff_id=NULL'
                        ]);
                    }
                }
                // ═══════════════════════════════════════════════════════════════════════════

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
                            // CRITICAL FIX 2025-11-21: Use service duration, not parameter default
                            'ends_at' => $appointmentTime->copy()->addMinutes($service->duration_minutes),
                            'status' => 'confirmed',
                            // 🔧 FIX 2025-11-19: Add sync_origin and calcom_sync_status for SYNC path
                            'sync_origin' => 'calcom',  // Booked directly in Cal.com (synchronous)
                            'calcom_sync_status' => 'synced',  // Already synced to Cal.com
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

                        // 🔧 FIX 2025-11-25 (Bug 2): Release slot lock after successful booking
                        if ($lockInfo) {
                            $this->lockService->releaseLock($lockKey, $callId, $appointment->id);
                            Log::info('🔓 Slot lock released after successful booking (SYNC)', [
                                'call_id' => $callId,
                                'lock_key' => $lockKey,
                                'appointment_id' => $appointment->id
                            ]);
                        }

                        // 🔧 FIX 2025-11-26: Update appointment_made flag on Call record
                        // BUGFIX: Changed from has_appointment (wrong) to appointment_made (correct DB column)
                        $call->update([
                            'appointment_made' => true,
                            'converted_appointment_id' => $appointment->id,
                        ]);
                        Log::info('✅ Updated call appointment_made flag (SYNC)', [
                            'call_id' => $callId,
                            'appointment_id' => $appointment->id
                        ]);

                        // 🔧 FIX 2025-12-13 (Patch B): Release slot reservation after successful booking
                        // BACKGROUND: reserve_slot holds the time slot for 5 minutes to prevent race conditions.
                        // After successful booking, we MUST release it so the slot count is accurate.
                        if ($hasActiveReservation && isset($existingReservation['uid'])) {
                            try {
                                $this->calcomService->releaseSlotReservation($existingReservation['uid']);
                                Cache::forget($reservationKey);
                                Log::info('🔓 Slot reservation released after successful booking', [
                                    'call_id' => $callId,
                                    'reservation_uid' => $existingReservation['uid'],
                                    'appointment_id' => $appointment->id
                                ]);
                            } catch (\Exception $e) {
                                Log::warning('⚠️ Failed to release slot reservation (non-critical)', [
                                    'call_id' => $callId,
                                    'reservation_uid' => $existingReservation['uid'],
                                    'error' => $e->getMessage()
                                ]);
                                // Don't fail the booking - reservation will auto-expire after 5 minutes
                            }
                        }

                        // 🔧 FIX 2025-11-26: Cache successful booking for idempotency
                        $successResponse = [
                            'booked' => true,
                            'appointment_id' => $appointment->id,
                            'message' => "Perfekt! Ihr Termin am {$appointmentTime->format('d.m.')} um {$appointmentTime->format('H:i')} Uhr ist gebucht.",
                            'booking_id' => $calcomBookingId,
                            'appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                            'confirmation' => "Sie erhalten eine Bestätigung per SMS."
                        ];

                        Cache::put($idempotencyCacheKey, [
                            'success' => true,
                            'response' => ['success' => true, 'data' => $successResponse],
                            'appointment_id' => $appointment->id,
                            'cached_at' => now()->toIso8601String()
                        ], 300); // 5 minutes TTL

                        Log::info('💾 IDEMPOTENCY: Cached successful booking result', [
                            'call_id' => $callId,
                            'appointment_id' => $appointment->id,
                            'cache_key' => $idempotencyCacheKey
                        ]);

                        return $this->responseFormatter->success($successResponse);
                    } else {
                        Log::warning('⚠️ Call not found for immediate appointment sync', [
                            'call_id' => $callId,
                            'calcom_booking_id' => $calcomBookingId
                        ]);

                        // 🔧 FIX 2025-11-26: Cache partial success for idempotency
                        $partialSuccessResponse = [
                            'booked' => true,
                            'message' => "Ihr Termin am {$appointmentTime->format('d.m.')} um {$appointmentTime->format('H:i')} Uhr ist gebucht.",
                            'booking_id' => $calcomBookingId,
                            'appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                            'confirmation' => "Sie erhalten eine Bestätigung per E-Mail."
                        ];

                        Cache::put($idempotencyCacheKey, [
                            'success' => true,
                            'response' => ['success' => true, 'data' => $partialSuccessResponse],
                            'appointment_id' => null,
                            'booking_id' => $calcomBookingId,
                            'cached_at' => now()->toIso8601String()
                        ], 300); // 5 minutes TTL

                        // Return partial success - Cal.com booking succeeded but no call context
                        return $this->responseFormatter->success($partialSuccessResponse);
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

                    // 🔧 FIX 2025-11-27: Cal.com booking EXISTS - don't tell user there was an error!
                    // The booking is in Cal.com, it will sync via webhook or manual process.
                    // Return SUCCESS to user because their appointment IS booked.
                    Log::warning('⚠️ Returning SUCCESS despite local save failure - Cal.com booking exists', [
                        'calcom_booking_id' => $calcomBookingId,
                        'call_id' => $callId,
                        'reason' => 'Cal.com booking confirmed, local sync will happen via webhook'
                    ]);

                    // Return success - the appointment IS booked in Cal.com
                    $partialSuccessResponse = [
                        'success' => true,
                        'message' => 'Ihr Termin wurde erfolgreich gebucht. Sie erhalten eine Bestätigung per E-Mail.',
                        'booking_id' => $calcomBookingId,
                        'appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                        'note' => 'Booking confirmed in calendar system'
                    ];

                    return $this->responseFormatter->success($partialSuccessResponse);
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
            // 🔧 FIX 2025-11-19: Comprehensive diagnostic logging with stack trace
            Log::error('❌ BOOKING EXCEPTION - FULL DETAILS', [
                'call_id' => $callId,
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'params_received' => $params,
                'datetime_param' => $params['datetime'] ?? 'NOT_SET',
                'date_param' => $params['date'] ?? 'NOT_SET',
                'time_param' => $params['time'] ?? 'NOT_SET',
                'appointment_date_param' => $params['appointment_date'] ?? 'NOT_SET',
                'appointment_time_param' => $params['appointment_time'] ?? 'NOT_SET'
            ]);

            // Return error with exception message for debugging
            return $this->responseFormatter->error(
                'Fehler bei der Terminbuchung: ' . $e->getMessage()
            );
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

            // 🔥 FIX 2025-11-19: SERVICE FILTERING (Progressive Disclosure UX)
            // PROBLEM: Agent lists all 20+ services → information overload
            // SOLUTION: Group by category, show categories first OR filter by gender/category if specified

            // Check for optional filtering parameters
            $category = $params['category'] ?? null;
            $gender = $params['gender'] ?? null; // "damen", "herren", "kinder"

            // If filtering requested, apply filters
            if ($category || $gender) {
                if ($gender) {
                    $services = $services->filter(function($service) use ($gender) {
                        return stripos($service->name, $gender) !== false;
                    });

                    Log::info('Services filtered by gender', [
                        'gender' => $gender,
                        'filtered_count' => $services->count(),
                        'call_id' => $callId
                    ]);
                }

                if ($category) {
                    $services = $services->filter(function($service) use ($category) {
                        return stripos($service->category, $category) !== false;
                    });

                    Log::info('Services filtered by category', [
                        'category' => $category,
                        'filtered_count' => $services->count(),
                        'call_id' => $callId
                    ]);
                }
            }

            // 🔥 UX IMPROVEMENT: Show categories overview if many services (>5)
            if ($services->count() > 5 && !$category && !$gender) {
                // Extract unique categories
                $categories = $services->pluck('category')->filter()->unique()->values();

                if ($categories->count() > 1) {
                    Log::info('Showing category overview instead of full service list', [
                        'total_services' => $services->count(),
                        'categories' => $categories->toArray(),
                        'call_id' => $callId
                    ]);

                    $message = "Wir bieten Dienstleistungen in folgenden Kategorien an: ";
                    $message .= $categories->join(', ') . '. ';
                    $message .= "Für welche Kategorie interessieren Sie sich?";

                    return $this->responseFormatter->success([
                        'auto_selected' => false,
                        'categories' => $categories->toArray(),
                        'service_count' => $services->count(),
                        'message' => $message,
                        'progressive_disclosure' => true
                    ]);
                }
            }

            // Standard behavior: List services (≤5 or after filtering)
            $serviceList = $services->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'duration' => $service->duration,
                    'price' => $service->price,
                    'description' => $service->description,
                    'category' => $service->category
                ];
            })->take(5); // Limit to max 5 services even after filtering

            $message = "Wir bieten folgende Services an: ";
            $message .= $services->take(5)->pluck('name')->join(', ');

            if ($services->count() > 5) {
                $message .= " ... und weitere. Welcher interessiert Sie?";
            }

            return $this->responseFormatter->success([
                'auto_selected' => false,
                'services' => $serviceList,
                'message' => $message,
                'count' => $services->count(),
                'shown' => min(5, $services->count())
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
     * Convert time (HH:MM) to natural German expression
     *
     * @param string $time Time in HH:MM format
     * @return string Natural German time expression
     */
    private function convertToNaturalGermanTime(string $time): string
    {
        [$hours, $minutes] = explode(':', $time);
        $hours = (int) $hours;
        $minutes = (int) $minutes;

        // Exact hours (10:00, 14:00, etc.)
        if ($minutes === 0) {
            return $this->numberToGermanWord($hours) . " Uhr";
        }

        // Special cases for common times
        if ($minutes === 30) {
            return "halb " . $this->numberToGermanWord($hours + 1);
        }

        if ($minutes === 15) {
            return "viertel nach " . $this->numberToGermanWord($hours);
        }

        if ($minutes === 45) {
            return "viertel vor " . $this->numberToGermanWord($hours + 1);
        }

        // 5 minutes before (09:55 -> "fünf vor zehn")
        if ($minutes === 55) {
            return "fünf vor " . $this->numberToGermanWord($hours + 1);
        }

        // 10 minutes before (09:50 -> "zehn vor zehn")
        if ($minutes === 50) {
            return "zehn vor " . $this->numberToGermanWord($hours + 1);
        }

        // 5 minutes after (10:05 -> "fünf nach zehn")
        if ($minutes === 5) {
            return "fünf nach " . $this->numberToGermanWord($hours);
        }

        // 10 minutes after (10:10 -> "zehn nach zehn")
        if ($minutes === 10) {
            return "zehn nach " . $this->numberToGermanWord($hours);
        }

        // 20 minutes after (10:20 -> "zwanzig nach zehn")
        if ($minutes === 20) {
            return "zwanzig nach " . $this->numberToGermanWord($hours);
        }

        // 20 minutes before (09:40 -> "zwanzig vor zehn")
        if ($minutes === 40) {
            return "zwanzig vor " . $this->numberToGermanWord($hours + 1);
        }

        // Fallback: standard format (e.g., "10 Uhr 17")
        return $this->numberToGermanWord($hours) . " Uhr " . $this->numberToGermanWord($minutes);
    }

    /**
     * Convert number to German word (1-24 for hours, 1-59 for minutes)
     *
     * @param int $number
     * @return string German word
     */
    private function numberToGermanWord(int $number): string
    {
        $words = [
            0 => 'null', 1 => 'eins', 2 => 'zwei', 3 => 'drei', 4 => 'vier',
            5 => 'fünf', 6 => 'sechs', 7 => 'sieben', 8 => 'acht', 9 => 'neun',
            10 => 'zehn', 11 => 'elf', 12 => 'zwölf', 13 => 'dreizehn', 14 => 'vierzehn',
            15 => 'fünfzehn', 16 => 'sechzehn', 17 => 'siebzehn', 18 => 'achtzehn', 19 => 'neunzehn',
            20 => 'zwanzig', 21 => 'einundzwanzig', 22 => 'zweiundzwanzig', 23 => 'dreiundzwanzig', 24 => 'vierundzwanzig',
            25 => 'fünfundzwanzig', 30 => 'dreißig', 35 => 'fünfunddreißig', 40 => 'vierzig',
            45 => 'fünfundvierzig', 50 => 'fünfzig', 55 => 'fünfundfünfzig'
        ];

        return $words[$number] ?? (string) $number;
    }

    /**
     * Format alternatives for Retell AI to speak naturally
     *
     * 🔧 FIX 2025-11-25 (Bug 3): Deduplicate alternatives before formatting
     * Problem: Same slot can appear multiple times with different types (same_day_earlier, same_day_later)
     * Solution: Use time as unique key to prevent duplicates in the response
     */
    private function formatAlternativesForRetell(array $alternatives): array
    {
        // 🔧 FIX 2025-11-25: Deduplicate by datetime to prevent showing same slot multiple times
        $seenTimes = [];
        $uniqueAlternatives = array_filter($alternatives, function($alt) use (&$seenTimes) {
            if (!isset($alt['datetime'])) {
                return false;
            }
            $timeKey = $alt['datetime']->format('Y-m-d H:i');
            if (isset($seenTimes[$timeKey])) {
                Log::debug('🔄 Filtered duplicate alternative', [
                    'time' => $timeKey,
                    'type' => $alt['type'] ?? 'unknown'
                ]);
                return false;
            }
            $seenTimes[$timeKey] = true;
            return true;
        });

        return array_values(array_map(function($alt) {
            $time = $alt['datetime']->format('H:i');

            return [
                'time' => $alt['datetime']->format('Y-m-d H:i'),
                'spoken' => $alt['description'],
                'spoken_time' => $this->convertToNaturalGermanTime($time),  // ✅ NEW: Natural time
                'available' => $alt['available'] ?? true,
                'type' => $alt['type'] ?? 'alternative'
            ];
        }, $uniqueAlternatives));
    }

    /**
     * Reserve a slot in Cal.com (prevents race conditions)
     *
     * UX: Agent says "Ich halte den Termin kurz fest" (truthful - slot IS reserved)
     * Then collects remaining data while reservation is active (5 min default)
     *
     * @param array $parameters {datetime: string, service_name?: string, service_id?: int}
     * @param string|null $callId
     * @return \Illuminate\Http\JsonResponse
     */
    private function reserveSlot(array $parameters, ?string $callId)
    {
        Log::info('🔒 reserve_slot called', [
            'call_id' => $callId,
            'params' => $parameters,
        ]);

        // Get call context
        $callContext = $this->getCallContext($callId);
        if (!$callContext) {
            return $this->responseFormatter->error(
                'Ich kann den Termin gerade nicht reservieren. Bitte versuchen Sie es erneut.',
                'call_context_unavailable'
            );
        }

        // Extract datetime
        $datetime = $parameters['datetime'] ?? $parameters['date_time'] ?? null;
        if (!$datetime) {
            return $this->responseFormatter->error(
                'Bitte nennen Sie mir den gewünschten Termin.',
                'missing_datetime'
            );
        }

        // Get service (from parameters or session)
        $serviceId = $parameters['service_id'] ?? null;
        $serviceName = $parameters['service_name'] ?? null;

        if (!$serviceId && !$serviceName) {
            // Try to get from session
            $sessionService = Cache::get("call:{$callId}:service");
            if ($sessionService) {
                $serviceId = $sessionService['id'] ?? null;
            }
        }

        // Resolve service
        $service = null;
        if ($serviceId) {
            $service = \App\Models\Service::find($serviceId);
        } elseif ($serviceName) {
            // 🔧 FIX 2025-12-13: Changed ILIKE to LIKE for MySQL compatibility
            // MySQL's LIKE is case-insensitive by default with utf8 collations
            // PostgreSQL's ILIKE is not supported in MySQL
            $service = \App\Models\Service::where('company_id', $callContext['company_id'])
                ->where(function($q) use ($serviceName) {
                    $q->where('name', 'LIKE', "%{$serviceName}%")
                      ->orWhere('display_name', 'LIKE', "%{$serviceName}%");
                })
                ->first();
        }

        if (!$service || !$service->calcom_event_type_id) {
            return $this->responseFormatter->error(
                'Ich konnte die Dienstleistung nicht finden. Welchen Service möchten Sie buchen?',
                'service_not_found'
            );
        }

        // Call Cal.com reservation API
        $calcomService = app(\App\Services\CalcomService::class);
        $result = $calcomService->reserveSlot(
            eventTypeId: $service->calcom_event_type_id,
            slotStart: $datetime,
            reservationDuration: 5 // 5 minutes default
        );

        if (!$result['success']) {
            Log::warning('🔒 Slot reservation failed', [
                'call_id' => $callId,
                'datetime' => $datetime,
                'error' => $result['error'],
            ]);

            return $this->responseFormatter->error(
                'Dieser Termin ist leider nicht mehr verfügbar. Soll ich Ihnen Alternativen anbieten?',
                $result['error'] ?? 'reservation_failed'
            );
        }

        // Store reservation in session for later use in start_booking
        $reservationKey = "call:{$callId}:reservation";
        Cache::put($reservationKey, [
            'uid' => $result['reservationUid'],
            'until' => $result['reservationUntil'],
            'datetime' => $datetime,
            'service_id' => $service->id,
            'event_type_id' => $service->calcom_event_type_id,
            'created_at' => now()->toIso8601String(),
        ], now()->addMinutes(6)); // Slightly longer than reservation

        Log::info('🔒 ✅ Slot reserved successfully', [
            'call_id' => $callId,
            'reservation_uid' => $result['reservationUid'],
            'reservation_until' => $result['reservationUntil'],
            'datetime' => $datetime,
            'service' => $service->name,
        ]);

        return $this->responseFormatter->success([
            'reserved' => true,
            'message' => 'Alles klar, ich halte den Termin kurz für Sie fest.',
            'reservation_uid' => $result['reservationUid'],
            'reservation_until' => $result['reservationUntil'],
            'datetime' => $datetime,
            'service' => $service->name,
        ]);
    }

    /**
     * Release a slot reservation (e.g., customer changed their mind)
     *
     * @param array $parameters {reservation_uid?: string}
     * @param string|null $callId
     * @return \Illuminate\Http\JsonResponse
     */
    private function releaseSlotReservation(array $parameters, ?string $callId)
    {
        Log::info('🔓 release_slot_reservation called', [
            'call_id' => $callId,
            'params' => $parameters,
        ]);

        // Get reservation UID from parameters or session
        $reservationUid = $parameters['reservation_uid'] ?? null;

        if (!$reservationUid && $callId) {
            // Try to get from session
            $reservationKey = "call:{$callId}:reservation";
            $sessionReservation = Cache::get($reservationKey);
            if ($sessionReservation) {
                $reservationUid = $sessionReservation['uid'] ?? null;
            }
        }

        if (!$reservationUid) {
            return $this->responseFormatter->success([
                'released' => false,
                'message' => 'Keine aktive Reservierung gefunden.',
            ]);
        }

        // Call Cal.com release API
        $calcomService = app(\App\Services\CalcomService::class);
        $result = $calcomService->releaseSlotReservation($reservationUid);

        // Clear session reservation
        if ($callId) {
            Cache::forget("call:{$callId}:reservation");
        }

        if (!$result['success']) {
            Log::warning('🔓 Slot release failed (non-critical)', [
                'call_id' => $callId,
                'reservation_uid' => $reservationUid,
                'error' => $result['error'],
            ]);

            // Still return success to agent - reservation will expire anyway
            return $this->responseFormatter->success([
                'released' => true,
                'message' => 'Reservierung wurde freigegeben.',
            ]);
        }

        Log::info('🔓 ✅ Slot reservation released', [
            'call_id' => $callId,
            'reservation_uid' => $reservationUid,
        ]);

        return $this->responseFormatter->success([
            'released' => true,
            'message' => 'Der Termin wurde wieder freigegeben.',
        ]);
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
                'cancel_appointment', 'reschedule_appointment', 'request_callback', 'find_next_available',
                'reserve_slot', 'release_slot_reservation'
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
            $uhrzeitRaw = $validatedData['uhrzeit'];
            $name = $validatedData['name'];
            $dienstleistung = $validatedData['dienstleistung'];
            $email = $validatedData['email'];
            $mitarbeiter = $validatedData['mitarbeiter'] ?? null; // PHASE 2: Staff preference

            // 🔧 FIX 2025-12-08: Parse TimePreference from uhrzeit to handle "ab X Uhr", "so gegen X Uhr", etc.
            // This extracts both the base time AND the customer's time preference (exact, from, window, approximate)
            $timePreference = $this->dateTimeParser->parseTimePreference($uhrzeitRaw);

            // Extract the base time for appointment scheduling
            // For "ab 14:00" → use "14:00" as starting point
            // For "so gegen 15:00" → use "15:00" as anchor
            // For "vormittags" → use anchor time (e.g., "10:30")
            $uhrzeit = $this->extractBaseTimeFromPreference($uhrzeitRaw, $timePreference);

            Log::info('🕐 TimePreference parsed for booking', [
                'raw_input' => $uhrzeitRaw,
                'preference_type' => $timePreference->type,
                'window_start' => $timePreference->windowStart,
                'window_end' => $timePreference->windowEnd,
                'extracted_base_time' => $uhrzeit,
                'label' => $timePreference->label,
            ]);

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

                    // 🔒 FIX (Call 88964): Validate booking date matches checked availability
                    // PROBLEM: check_availability_v17(datum=2025-12-13) → start_booking(datetime=2025-12-12)
                    // SOLUTION: Compare with cached checked_availability and use it if mismatch detected
                    // This prevents booking wrong dates when AI misinterprets date between function calls
                    if ($callId && $confirmBooking === true) {
                        $checkedAvailabilityCacheKey = "call:{$callId}:checked_availability";
                        $checkedData = Cache::get($checkedAvailabilityCacheKey);

                        if ($checkedData) {
                            $checkedDatetime = Carbon::parse($checkedData['datetime']);
                            $bookingDatetime = $appointmentDate;

                            // Check if dates match (allow small time differences for flexibility)
                            $dateMismatch = $checkedDatetime->format('Y-m-d') !== $bookingDatetime->format('Y-m-d');
                            $timeMismatch = $checkedDatetime->format('H:i') !== $bookingDatetime->format('H:i');

                            if ($dateMismatch || $timeMismatch) {
                                Log::critical('🚨 DATE-MISMATCH DETECTED between check_availability and start_booking', [
                                    'call_id' => $callId,
                                    'mismatch_type' => $dateMismatch ? 'DATE' : 'TIME',
                                    'checked_availability' => [
                                        'datum_input' => $checkedData['datum'],
                                        'datum_parsed' => $checkedData['datum_parsed'],
                                        'uhrzeit' => $checkedData['uhrzeit'],
                                        'datetime' => $checkedData['datetime'],
                                        'checked_at' => $checkedData['checked_at'],
                                    ],
                                    'start_booking_attempt' => [
                                        'datum_input' => $datum,
                                        'uhrzeit_input' => $uhrzeit,
                                        'parsed_datetime' => $bookingDatetime->format('Y-m-d H:i:s'),
                                    ],
                                    'action' => 'Using checked_availability datetime to prevent wrong booking',
                                    'bug_reference' => 'Call 88964 - Samstag 13.12 checked but Freitag 12.12 attempted'
                                ]);

                                // CORRECTION: Use the checked datetime instead of misparsed one
                                $appointmentDate = $checkedDatetime;

                                Log::info('✅ CORRECTED appointment datetime using checked_availability cache', [
                                    'call_id' => $callId,
                                    'corrected_from' => $bookingDatetime->format('Y-m-d H:i'),
                                    'corrected_to' => $appointmentDate->format('Y-m-d H:i'),
                                    'source' => 'checked_availability_cache'
                                ]);
                            } else {
                                Log::info('✅ Date validation passed: booking matches checked availability', [
                                    'call_id' => $callId,
                                    'checked_datetime' => $checkedDatetime->format('Y-m-d H:i'),
                                    'booking_datetime' => $bookingDatetime->format('Y-m-d H:i'),
                                    'status' => 'MATCH'
                                ]);
                            }
                        } else {
                            Log::warning('⚠️ No checked_availability cache found for booking validation', [
                                'call_id' => $callId,
                                'cache_key' => $checkedAvailabilityCacheKey,
                                'reason' => 'Either cache expired (>10min) or check_availability was not called',
                                'booking_datetime' => $appointmentDate->format('Y-m-d H:i'),
                                'proceeding_with' => 'parsed datetime (no validation possible)'
                            ]);
                        }
                    }

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

                        // 🔧 FIX 2025-11-25 (TIMEZONE BUG): Convert Cal.com UTC times to Berlin time
                        // PROBLEM: Cal.com returns slots in UTC, but we compare with Berlin time
                        // EXAMPLE: User asks for 12:15 Berlin, Cal.com returns 11:15 UTC - comparison failed!
                        // SOLUTION: Convert UTC slot time to Berlin before comparing
                        $requestedTimeStr = $appointmentDate->format('H:i');
                        $berlinTimezone = 'Europe/Berlin';

                        foreach ($daySlots as $slot) {
                            // Parse UTC time and convert to Berlin timezone
                            $slotTimeUtc = Carbon::parse($slot['time']);
                            $slotTimeBerlin = $slotTimeUtc->setTimezone($berlinTimezone);

                            if ($slotTimeBerlin->format('H:i') === $requestedTimeStr) {
                                $exactTimeAvailable = true;
                                Log::info('✅ Exact requested time IS available in Cal.com', [
                                    'requested' => $requestedTimeStr,
                                    'slot_utc' => $slotTimeUtc->format('H:i'),
                                    'slot_berlin' => $slotTimeBerlin->format('H:i'),
                                    'slot_found' => $slot['time']
                                ]);
                                break;
                            }
                        }

                        if (!$exactTimeAvailable) {
                            Log::info('❌ Exact requested time NOT available in Cal.com', [
                                'requested' => $requestedTimeStr,
                                'total_slots' => count($daySlots),
                                'available_times_utc' => array_map(fn($s) => Carbon::parse($s['time'])->format('H:i'), $daySlots),
                                'available_times_berlin' => array_map(fn($s) => Carbon::parse($s['time'])->setTimezone($berlinTimezone)->format('H:i'), $daySlots)
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to check exact time availability', [
                        'error' => $e->getMessage(),
                        'requested_time' => $appointmentDate->format('Y-m-d H:i')
                    ]);
                }

                // 🔧 FIX 2025-11-27: LOCAL DB CONFLICT CHECK in collectAppointment
                // CRITICAL: Cal.com API may return "available" even when:
                //   1. Local appointment exists but hasn't synced to Cal.com yet
                //   2. Cal.com cache is stale
                //   3. Appointment was just created in another call
                //
                // This prevents the "wurde gerade vergeben" error that occurs when:
                //   check_availability_v17 → says "available"
                //   start_booking → finds local conflict → fails
                //
                // Now both paths check the same local DB constraint.
                if ($exactTimeAvailable && $companyId && $branchId && $service) {
                    // 🔧 FIX 2025-11-27: Calculate correct duration for COMPOSITE services
                    // Composite services (e.g., Dauerwelle) have multiple segments with gaps
                    // Total duration = sum of all segment durations
                    $serviceDuration = $service->duration_minutes ?? 60;
                    $isComposite = false;

                    if ($service->isComposite() && !empty($service->segments)) {
                        $isComposite = true;
                        $serviceDuration = collect($service->segments)->sum(fn($s) => $s['durationMin'] ?? $s['duration'] ?? 0);
                    }

                    $requestedEndTime = $appointmentDate->copy()->addMinutes($serviceDuration);

                    Log::debug('🔍 LOCAL DB CONFLICT CHECK in collectAppointment', [
                        'call_id' => $callId,
                        'requested_start' => $appointmentDate->format('Y-m-d H:i'),
                        'requested_end' => $requestedEndTime->format('Y-m-d H:i'),
                        'service_duration' => $serviceDuration,
                        'is_composite' => $isComposite,
                        'segments_count' => $isComposite ? count($service->segments) : 0,
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                    ]);

                    $localConflict = Appointment::where('company_id', $companyId)
                        ->where('branch_id', $branchId)
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->where(function($query) use ($appointmentDate, $requestedEndTime) {
                            // Comprehensive overlap detection:
                            // Two intervals [A_start, A_end) and [B_start, B_end) overlap if:
                            // A_start < B_end AND B_start < A_end
                            $query->where('starts_at', '<', $requestedEndTime)
                                  ->where('ends_at', '>', $appointmentDate);
                        })
                        ->first();

                    if ($localConflict) {
                        Log::warning('🚨 LOCAL DB CONFLICT in collectAppointment: Cal.com said available but local DB has conflict!', [
                            'call_id' => $callId,
                            'calcom_said' => 'available',
                            'local_db_said' => 'blocked',
                            'requested_window' => [
                                'start' => $appointmentDate->format('Y-m-d H:i'),
                                'end' => $requestedEndTime->format('Y-m-d H:i'),
                                'duration' => $serviceDuration,
                            ],
                            'blocking_appointment' => [
                                'id' => $localConflict->id,
                                'status' => $localConflict->status,
                                'sync_status' => $localConflict->calcom_sync_status ?? 'unknown',
                                'start' => $localConflict->starts_at->format('Y-m-d H:i'),
                                'end' => $localConflict->ends_at->format('Y-m-d H:i'),
                                'customer' => $localConflict->customer?->name ?? 'unknown',
                            ],
                            'fix' => 'FIX 2025-11-27: Added local DB check to collectAppointment',
                        ]);

                        // Override Cal.com result - slot is NOT available
                        $exactTimeAvailable = false;
                    }
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
                    // 🔧 FIX 2025-11-25: Use actual service duration instead of hardcoded 60 minutes
                    // BUG: Hardcoded 60 caused wrong alternatives for services like Dauerwelle (135 min)
                    $serviceDuration = $service->duration_minutes ?? 60;
                    Log::info('🔍 Finding alternatives with actual service duration', [
                        'service_id' => $service->id,
                        'service_name' => $service->name,
                        'duration_minutes' => $serviceDuration,
                        'is_composite' => $service->composite ?? false
                    ]);

                    $alternatives = $this->alternativeFinder
                        ->setTenantContext($companyId, $branchId)
                        ->findAlternatives(
                            $checkDate,
                            $serviceDuration, // 🔧 FIX: Use actual service duration
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

                    // 🔒 FIX 2025-11-25 (Bug 2): Improved lock validation
                    // PROBLEM: Old code expected lock_key in args but Retell doesn't pass it
                    // SOLUTION: Generate lock key from booking parameters and validate ownership
                    $lockKey = null;
                    if (config('features.slot_locking.enabled', false) && $companyId && $service) {
                        // Generate lock key from booking parameters
                        $lockKey = $this->lockService->generateLockKey(
                            $companyId,
                            $service->id,
                            $appointmentDate
                        );

                        $lockInfo = $this->lockService->getLockInfo($lockKey);

                        if ($lockInfo) {
                            // Lock exists - validate ownership
                            $lockValidation = $this->lockService->validateLock($lockKey, $callId);

                            if (!$lockValidation['valid']) {
                                // Another call owns this slot - RACE CONDITION PREVENTED
                                Log::error('🚨 RACE CONDITION PREVENTED - Slot locked by another call', [
                                    'call_id' => $callId,
                                    'lock_key' => $lockKey,
                                    'locked_by' => $lockInfo['call_id'] ?? 'unknown',
                                    'reason' => $lockValidation['reason'],
                                    'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                                ]);

                                return response()->json([
                                    'success' => false,
                                    'status' => 'slot_locked',
                                    'message' => 'Dieser Termin wurde gerade vergeben. Bitte wählen Sie eine Alternative.',
                                    'reason' => $lockValidation['reason'],
                                    'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                                ], 200);
                            }

                            Log::info('✅ Lock validated - this call owns the slot', [
                                'call_id' => $callId,
                                'lock_key' => $lockKey,
                            ]);
                        } else {
                            // No lock exists - try to acquire one before booking
                            Log::info('🔒 No existing lock - acquiring lock before booking', [
                                'call_id' => $callId,
                                'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                            ]);

                            $lockResult = $this->lockService->acquireLock(
                                $companyId,
                                $service->id,
                                $appointmentDate,
                                $appointmentDate->copy()->addMinutes($service->duration ?? 60),
                                $callId,
                                $call?->from_number ?? 'unknown',
                                ['customer_name' => $name ?? null]
                            );

                            if (!$lockResult['success']) {
                                Log::warning('⚠️ Failed to acquire lock - slot may be taken', [
                                    'call_id' => $callId,
                                    'reason' => $lockResult['reason'] ?? 'unknown',
                                ]);
                                // Continue anyway - Cal.com double-check will catch it
                            }
                        }
                    }

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
                                    // 🔧 FIX 2025-12-01: Cal.com returns UTC times, convert to Berlin for comparison
                                    // $requestedTimeStr is Berlin time from $appointmentDate
                                    $slotTime = Carbon::parse($slot['time'])->setTimezone('UTC')->setTimezone('Europe/Berlin');
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
                                        // 🔧 FIX 2025-11-25: Use actual service duration (not hardcoded 60)
                                        $alternatives = $this->alternativeFinder
                                            ->setTenantContext($companyId, $branchId)
                                            ->findAlternatives(
                                                $appointmentDate,
                                                $serviceDuration, // 🔧 FIX: Use actual service duration
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

                                // 🔧 FIX 2025-11-20: Fetch full booking details with host/organizer data
                                // Cal.com POST /bookings doesn't include host info, must GET separately
                                $bookingWithHost = $booking;
                                $bookingUidOrId = $booking['uid'] ?? $booking['id'] ?? null;

                                if ($bookingUidOrId) {
                                    try {
                                        $fullBookingResponse = $calcomService->getBooking($bookingUidOrId);
                                        if ($fullBookingResponse->successful()) {
                                            $bookingWithHost = $fullBookingResponse->json()['data'] ?? $booking;

                                            Log::channel('calcom')->info('✅ Retrieved full booking details with host info', [
                                                'booking_uid' => $bookingUidOrId,
                                                'has_organizer' => isset($bookingWithHost['organizer']),
                                                'has_hosts' => isset($bookingWithHost['hosts']),
                                                'organizer_email' => $bookingWithHost['organizer']['email'] ?? null
                                            ]);
                                        }
                                    } catch (\Exception $e) {
                                        Log::channel('calcom')->warning('⚠️ Failed to fetch full booking details, using initial response', [
                                            'booking_uid' => $bookingUidOrId,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }

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
                                            calcomBookingData: $bookingWithHost  // 🔧 FIX: Pass full booking WITH host data
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

                                        // 🔓 REDIS LOCK RELEASE (2025-11-23)
                                        // Release lock after successful appointment creation
                                        if (config('features.slot_locking.enabled', false) && $lockKey) {
                                            $released = $this->lockService->releaseLock($lockKey, $callId, $appointment->id);

                                            Log::info('🔓 Slot lock released after successful booking', [
                                                'call_id' => $callId,
                                                'lock_key' => $lockKey,
                                                'appointment_id' => $appointment->id,
                                                'released' => $released,
                                            ]);
                                        }

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
                                        // 🔧 FIX 2025-11-25: Use actual service duration (not hardcoded 60)
                                        $alternatives = $this->alternativeFinder
                                            ->setTenantContext($companyId, $branchId)
                                            ->findAlternatives(
                                                $appointmentDate,
                                                $service->duration_minutes ?? 60, // 🔧 FIX: Use actual duration
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

                    // 🔒 FIX (Call 88964): Cache checked availability date/time for validation in start_booking
                    // PROBLEM: check_availability checked 2025-12-13, but start_booking tried to book 2025-12-12
                    // SOLUTION: Cache the checked datetime with call_id binding for validation in booking phase
                    // TTL: 10 minutes (reasonable time for user to confirm)
                    if ($callId) {
                        $checkedAvailabilityCacheKey = "call:{$callId}:checked_availability";
                        $checkedData = [
                            'datum' => $datum,                                   // Original input ("Samstag", "2025-12-13", etc.)
                            'datum_parsed' => $appointmentDate->format('Y-m-d'), // Normalized date (2025-12-13)
                            'uhrzeit' => $uhrzeit,                              // Time input ("9 Uhr", "09:00")
                            'datetime' => $appointmentDate->format('Y-m-d H:i:s'), // Full datetime for exact comparison
                            'service_id' => $service->id,
                            'event_type_id' => $service->calcom_event_type_id,
                            'checked_at' => now()->toIso8601String(),
                        ];

                        Cache::put($checkedAvailabilityCacheKey, $checkedData, now()->addMinutes(10));

                        Log::info('🔒 Cached checked availability for validation', [
                            'call_id' => $callId,
                            'cache_key' => $checkedAvailabilityCacheKey,
                            'cached_data' => $checkedData,
                            'ttl' => '10 minutes',
                            'purpose' => 'Validate start_booking uses same date as check_availability'
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

                    // 🔒 FIX 2025-11-25 (Bug 2): Cache validated alternatives WITH slot locks
                    // PROBLEM: Alternatives were returned but not cached/locked, causing race conditions
                    // SOLUTION: Call cacheValidatedAlternatives with lockContext to acquire Redis locks
                    if ($callId && !empty($alternatives['alternatives'])) {
                        $this->cacheValidatedAlternatives($callId, $alternatives['alternatives'], [
                            'company_id' => $companyId,
                            'service_id' => $service->id,
                            'duration' => $service->duration ?? 60,
                            'customer_phone' => $call?->from_number ?? 'unknown',
                            'customer_name' => $name ?? null,
                        ]);
                    }

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

        // ═══════════════════════════════════════════════════════════════════════════
        // ✅ FIX 2025-12-08: Dynamic staff mapping from database
        // ═══════════════════════════════════════════════════════════════════════════
        // PROBLEM: Static mapping didn't include "Udo Walz", "Mario Basler" etc.
        // SOLUTION: Query database for staff by name (case-insensitive)
        // RCA: Call #85183 - "Udo Walz" couldn't be resolved
        // ═══════════════════════════════════════════════════════════════════════════

        // Try to get company context from call
        $companyId = null;
        if ($callId) {
            $call = $this->callLifecycle->findCallByRetellId($callId);
            $companyId = $call?->company_id;
        }

        // 1. Try exact name match from database (case-insensitive)
        $staffQuery = \App\Models\Staff::where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [$cleaned]);

        if ($companyId) {
            $staffQuery->where('company_id', $companyId);
        }

        $staff = $staffQuery->first();

        if ($staff) {
            Log::info('✅ Staff name matched from database (exact)', [
                'input' => $staffName,
                'cleaned' => $cleaned,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'company_id' => $companyId,
                'call_id' => $callId
            ]);
            return $staff->id;
        }

        // 2. Try partial match from database (first name only)
        $staffQuery = \App\Models\Staff::where('is_active', true)
            ->whereRaw('LOWER(name) LIKE ?', ['%' . $cleaned . '%']);

        if ($companyId) {
            $staffQuery->where('company_id', $companyId);
        }

        $staff = $staffQuery->first();

        if ($staff) {
            Log::info('✅ Staff name matched from database (partial)', [
                'input' => $staffName,
                'cleaned' => $cleaned,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'company_id' => $companyId,
                'call_id' => $callId
            ]);
            return $staff->id;
        }

        // 3. Fallback: Try first name only (e.g., "Udo" -> "Udo Walz")
        $firstName = explode(' ', $cleaned)[0];
        if ($firstName !== $cleaned) {
            $staffQuery = \App\Models\Staff::where('is_active', true)
                ->whereRaw('LOWER(name) LIKE ?', [$firstName . '%']);

            if ($companyId) {
                $staffQuery->where('company_id', $companyId);
            }

            $staff = $staffQuery->first();

            if ($staff) {
                Log::info('✅ Staff name matched from database (first name)', [
                    'input' => $staffName,
                    'first_name' => $firstName,
                    'staff_id' => $staff->id,
                    'staff_name' => $staff->name,
                    'company_id' => $companyId,
                    'call_id' => $callId
                ]);
                return $staff->id;
            }
        }

        // Log failure with available staff names
        $availableStaff = \App\Models\Staff::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->pluck('name')
            ->toArray();

        Log::warning('❌ Staff name could not be mapped', [
            'input' => $staffName,
            'cleaned' => $cleaned,
            'call_id' => $callId,
            'company_id' => $companyId,
            'available_staff' => $availableStaff
        ]);

        return null;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ✅ FIX 2025-12-08: Staff-Service Capability Validation
    // ═══════════════════════════════════════════════════════════════════════════
    // PROBLEM: Silent Fallback - When customer requests specific staff who can't
    //          provide a service, system silently books with another staff
    // SOLUTION: Validate staff capability upfront and return informative message
    //           with alternative staff suggestions
    // EXAMPLE: "Mario Basler bietet leider keine Dauerwelle an. Aber Udo Walz kann das."
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Extract base time from TimePreference for appointment scheduling
     *
     * Handles time formats with prefixes like "ab 14:00", "so gegen 15:00", "vormittags"
     * Returns the base time string (HH:MM format) for scheduling.
     *
     * @param string|null $rawInput Original input string
     * @param \App\ValueObjects\TimePreference $timePreference Parsed time preference
     * @return string|null Time in HH:MM format or null
     *
     * @since 2025-12-08 Fix for time preference extraction
     */
    private function extractBaseTimeFromPreference(?string $rawInput, \App\ValueObjects\TimePreference $timePreference): ?string
    {
        // If no input, return null
        if (empty($rawInput)) {
            return null;
        }

        // For TYPE_ANY, try to extract any time from the raw input
        if ($timePreference->type === \App\ValueObjects\TimePreference::TYPE_ANY) {
            // Fallback: Try to extract numeric time from raw input
            if (preg_match('/(\d{1,2})(?::(\d{2}))?/', $rawInput, $m)) {
                return sprintf('%02d:%s', $m[1], !empty($m[2]) ? $m[2] : '00');
            }
            return null;
        }

        // For EXACT type, use the exact time
        if ($timePreference->type === \App\ValueObjects\TimePreference::TYPE_EXACT) {
            return $timePreference->windowStart;
        }

        // For FROM type ("ab X Uhr"), use the start time
        if ($timePreference->type === \App\ValueObjects\TimePreference::TYPE_FROM) {
            return $timePreference->windowStart;
        }

        // For APPROXIMATE type ("so gegen X Uhr"), use anchor time (middle of window)
        if ($timePreference->type === \App\ValueObjects\TimePreference::TYPE_APPROXIMATE) {
            return $timePreference->getAnchorTime();
        }

        // For WINDOW and RANGE types, use the anchor time (middle of window)
        if (in_array($timePreference->type, [
            \App\ValueObjects\TimePreference::TYPE_WINDOW,
            \App\ValueObjects\TimePreference::TYPE_RANGE
        ])) {
            return $timePreference->getAnchorTime();
        }

        // Fallback: Try to extract from raw input
        if (preg_match('/(\d{1,2})(?::(\d{2}))?/', $rawInput, $m)) {
            return sprintf('%02d:%s', $m[1], !empty($m[2]) ? $m[2] : '00');
        }

        return $timePreference->windowStart;
    }

    /**
     * Validates if a staff member can provide a specific service
     * Returns validation result with alternative staff suggestions if invalid
     *
     * @param string $staffId The staff member's UUID
     * @param Service $service The service to check
     * @param string|null $callId Optional call ID for logging
     * @return array{
     *   valid: bool,
     *   staff_name: string|null,
     *   service_name: string,
     *   alternatives: array<array{id: string, name: string}>|null,
     *   message: string|null
     * }
     */
    private function validateStaffCanProvideService(string $staffId, Service $service, ?string $callId = null): array
    {
        $result = [
            'valid' => false,
            'staff_name' => null,
            'service_name' => $service->name,
            'alternatives' => null,
            'message' => null,
        ];

        try {
            // Get staff with their bookable services
            $staff = \App\Models\Staff::find($staffId);

            if (!$staff) {
                Log::warning('❌ Staff not found for capability check', [
                    'staff_id' => $staffId,
                    'service_id' => $service->id,
                    'call_id' => $callId
                ]);
                // Let it pass - don't block on data issues
                $result['valid'] = true;
                return $result;
            }

            $result['staff_name'] = $staff->name;

            // Check if staff can book this service (pivot: can_book = true, is_active = true)
            $canProvide = $staff->services()
                ->where('services.id', $service->id)
                ->wherePivot('can_book', true)
                ->wherePivot('is_active', true)
                ->exists();

            if ($canProvide) {
                Log::info('✅ Staff can provide service', [
                    'staff_id' => $staffId,
                    'staff_name' => $staff->name,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'call_id' => $callId
                ]);
                $result['valid'] = true;
                return $result;
            }

            // Staff cannot provide this service - find alternatives
            Log::warning('⚠️ Staff cannot provide requested service', [
                'staff_id' => $staffId,
                'staff_name' => $staff->name,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'call_id' => $callId
            ]);

            // Find alternative staff who CAN provide this service
            $alternativeStaff = $service->staff()
                ->wherePivot('can_book', true)
                ->wherePivot('is_active', true)
                ->where('staff.id', '!=', $staffId)
                ->where('staff.is_active', true)
                ->limit(3)
                ->get(['staff.id', 'staff.name']);

            if ($alternativeStaff->isNotEmpty()) {
                $result['alternatives'] = $alternativeStaff->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name
                ])->toArray();

                // Build German message
                $altNames = $alternativeStaff->pluck('name')->toArray();
                if (count($altNames) === 1) {
                    $altText = $altNames[0];
                } elseif (count($altNames) === 2) {
                    $altText = $altNames[0] . ' oder ' . $altNames[1];
                } else {
                    $last = array_pop($altNames);
                    $altText = implode(', ', $altNames) . ' oder ' . $last;
                }

                $result['message'] = sprintf(
                    '%s bietet leider keine %s an. Aber %s kann das. Soll ich bei %s buchen?',
                    $staff->name,
                    $service->name,
                    $altText,
                    count($result['alternatives']) === 1 ? $result['alternatives'][0]['name'] : 'einem davon'
                );
            } else {
                // No alternatives available
                $result['message'] = sprintf(
                    'Leider kann %s keine %s durchführen, und aktuell ist auch kein anderer Mitarbeiter für diesen Service verfügbar.',
                    $staff->name,
                    $service->name
                );
            }

            Log::info('📋 Staff capability validation result', [
                'valid' => $result['valid'],
                'staff_name' => $result['staff_name'],
                'service_name' => $result['service_name'],
                'alternatives_count' => count($result['alternatives'] ?? []),
                'message' => $result['message'],
                'call_id' => $callId
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Staff capability validation error', [
                'staff_id' => $staffId,
                'service_id' => $service->id,
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);
            // Non-blocking: Let it pass on errors
            $result['valid'] = true;
            return $result;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════

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

            // 🔒 SECURITY: Phone-based cancellation policy
            // Requirement: Only customers with verified phone numbers can cancel
            // Anonymous bookings → must request callback for verification
            $customer = $appointment->customer;

            if (!$customer) {
                Log::error('🚨 Cancellation blocked: Appointment has no customer', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $callId
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'no_customer',
                    'message' => 'Dieser Termin konnte keinem Kunden zugeordnet werden. Bitte kontaktieren Sie uns direkt für eine Stornierung.'
                ], 200);
            }

            // Check if customer has valid phone number
            $customerPhone = $customer->phone;
            $isPhoneValid = !empty($customerPhone) &&
                           !in_array(strtolower($customerPhone), ['anonymous', 'unknown', 'withheld', 'restricted', '00000000', '']);

            if (!$isPhoneValid) {
                Log::warning('🔒 Cancellation blocked: Customer has no valid phone number', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                    'customer_phone' => $customerPhone,
                    'call_id' => $callId,
                    'reason' => 'phone_verification_required'
                ]);

                // Create callback request for manual verification
                return $this->createAnonymousCallbackRequest($call, array_merge($params, [
                    'customer_name' => $customer->name,
                    'appointment_id' => $appointment->id,
                    'appointment_date' => $appointment->starts_at->format('Y-m-d'),
                    'reason' => 'Termin wurde ohne Telefonnummer gebucht - manuelle Verifikation erforderlich'
                ]), 'cancellation');
            }

            // Verify caller identity: caller's phone must match customer's phone
            // This prevents unauthorized cancellations from different numbers
            $callerPhone = $call->from_number;
            $callerPhoneNormalized = preg_replace('/[^0-9]/', '', $callerPhone ?? '');
            $customerPhoneNormalized = preg_replace('/[^0-9]/', '', $customerPhone);

            // Match last 8 digits (handles different country code formats)
            $callerLast8 = substr($callerPhoneNormalized, -8);
            $customerLast8 = substr($customerPhoneNormalized, -8);

            if ($callerLast8 !== $customerLast8) {
                Log::warning('🚨 SECURITY: Phone mismatch - unauthorized cancellation attempt', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_phone_last8' => $customerLast8,
                    'caller_phone_last8' => $callerLast8,
                    'call_id' => $callId,
                    'security_violation' => 'phone_mismatch'
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'unauthorized',
                    'message' => sprintf(
                        'Für eine Stornierung benötigen wir eine Verifikation. Dieser Termin ist auf eine andere Telefonnummer gebucht. Möchten Sie, dass wir Sie unter %s zurückrufen, um die Stornierung zu bestätigen?',
                        $customerPhone
                    ),
                    'callback_available' => true,
                    'reason' => 'phone_verification_failed'
                ], 200);
            }

            Log::info('✅ Phone verification successful for cancellation', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'phone_match' => true,
                'call_id' => $callId
            ]);

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

                // Prepare success response BEFORE firing events
                // This ensures notification failures don't affect the cancellation success
                $feeMessage = $policyResult->fee > 0
                    ? " Es fällt eine Stornogebühr von {$policyResult->fee}€ an."
                    : "";

                $successResponse = [
                    'success' => true,
                    'status' => 'cancelled',
                    'message' => "Ihr Termin am {$appointment->starts_at->format('d.m.Y')} um {$appointment->starts_at->format('H:i')} Uhr wurde erfolgreich storniert.{$feeMessage}",
                    'fee' => $policyResult->fee,
                    'appointment_id' => $appointment->id
                ];

                Log::info('✅ Appointment cancelled via Retell AI', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $callId,
                    'fee' => $policyResult->fee,
                    'within_policy' => true
                ]);

                // Fire events for listeners (notifications, stats, Cal.com sync, etc.)
                // AFTER response is prepared - event failures won't affect user response
                try {
                    // 1. Fire AppointmentCancellationRequested for notifications
                    event(new \App\Events\Appointments\AppointmentCancellationRequested(
                        appointment: $appointment->fresh(),
                        reason: $params['reason'] ?? 'Via Telefonassistent storniert',
                        customer: $appointment->customer,
                        fee: $policyResult->fee,
                        withinPolicy: true
                    ));

                    // 2. Fire AppointmentCancelled for Cal.com sync and cache invalidation
                    event(new \App\Events\Appointments\AppointmentCancelled(
                        appointment: $appointment->fresh(),
                        reason: $params['reason'] ?? 'Via Telefonassistent storniert',
                        cancelledBy: 'customer'
                    ));
                } catch (\Exception $eventException) {
                    // Log event failures but DON'T fail the cancellation
                    Log::warning('⚠️ Event firing failed after successful cancellation (non-critical)', [
                        'appointment_id' => $appointment->id,
                        'call_id' => $callId,
                        'event_error' => $eventException->getMessage(),
                        'note' => 'Cancellation was successful, only event firing failed'
                    ]);
                    // Don't re-throw - cancellation already succeeded
                }

                return response()->json($successResponse, 200);
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

            Log::warning('❌ Cancellation denied by policy', [
                'appointment_id' => $appointment->id,
                'call_id' => $callId,
                'reason' => $reasonCode,
                'details' => $details
            ]);

            // Prepare denial response BEFORE firing events
            $denialResponse = [
                'success' => false,
                'status' => 'denied',
                'message' => $message,
                'reason' => $reasonCode,
                'details' => $details
            ];

            // Fire policy violation event AFTER response is prepared
            try {
                event(new \App\Events\Appointments\AppointmentPolicyViolation(
                    appointment: $appointment,
                    policyResult: $policyResult,
                    attemptedAction: 'cancel',
                    source: 'retell_ai'
                ));
            } catch (\Exception $eventException) {
                // Log but don't fail - policy denial message is more important
                Log::warning('⚠️ Event firing failed after policy denial (non-critical)', [
                    'appointment_id' => $appointment->id,
                    'event_error' => $eventException->getMessage()
                ]);
            }

            return response()->json($denialResponse, 200);

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

            // 🔧 FIX: Wenn kein altes Datum angegeben, versuche new_datum als Fallback
            // Das hilft bei Anrufen wie "Ich will meinen Termin am 2. Dezember verschieben"
            if (!$oldDate && !empty($params['new_datum'])) {
                try {
                    $newDatumDate = Carbon::parse($params['new_datum'])->format('Y-m-d');
                    Log::info('🔄 Reschedule: Kein old_date angegeben, verwende new_datum als Suchkriterium', [
                        'call_id' => $callId,
                        'new_datum' => $params['new_datum'],
                        'search_date' => $newDatumDate,
                    ]);
                    $oldDate = $newDatumDate;
                } catch (\Exception $e) {
                    Log::warning('⚠️ Reschedule: Konnte new_datum nicht parsen', [
                        'call_id' => $callId,
                        'new_datum' => $params['new_datum'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $appointment = $this->findAppointmentFromCall($call, ['appointment_date' => $oldDate]);

            if (!$appointment) {
                // Try listing all upcoming appointments for customer
                if ($call->customer_id) {
                    // 🔧 FIX 2025-11-25: Increased limit from 3 to 10
                    // Bug: Customers with >3 appointments couldn't see later ones
                    // Example: Hans Schuster had appointments on Nov 26, 27, 28 AND Dec 2
                    //          but only the first 3 were shown, hiding the Dec 2 appointment
                    $upcomingAppointments = Appointment::where('customer_id', $call->customer_id)
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->where('starts_at', '>=', now())
                        ->orderBy('starts_at', 'asc')
                        ->limit(10)
                        ->get();

                    if ($upcomingAppointments->count() > 0) {
                        // 🔧 FIX 2025-11-25: Limit verbal list to 5 for better UX
                        // Full list remains in JSON for agent to reference
                        $verboseLimit = 5;
                        $verboseAppointments = $upcomingAppointments->take($verboseLimit);
                        $remainingCount = $upcomingAppointments->count() - $verboseLimit;

                        $appointments_list = $verboseAppointments->map(function($apt) {
                            return $apt->starts_at->format('d.m.Y \u\m H:i \U\h\r');
                        })->join(', ');

                        // Add hint about remaining appointments if any
                        $message = "Ich habe mehrere Termine für Sie gefunden: {$appointments_list}";
                        if ($remainingCount > 0) {
                            $message .= " und {$remainingCount} weitere";
                        }
                        $message .= ". Welchen möchten Sie verschieben?";

                        return response()->json([
                            'success' => false,
                            'status' => 'multiple_found',
                            'message' => $message,
                            'appointment_count' => $upcomingAppointments->count(),
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

            // 🔒 SECURITY: Phone-based reschedule policy (same as cancellation)
            // Requirement: Only customers with verified phone numbers can reschedule
            // Anonymous bookings → must request callback for verification
            $customer = $appointment->customer;

            if (!$customer) {
                Log::error('🚨 Reschedule blocked: Appointment has no customer', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $callId
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'no_customer',
                    'message' => 'Dieser Termin konnte keinem Kunden zugeordnet werden. Bitte kontaktieren Sie uns direkt für eine Terminänderung.'
                ], 200);
            }

            // Check if customer has valid phone number
            $customerPhone = $customer->phone;
            $isPhoneValid = !empty($customerPhone) &&
                           !in_array(strtolower($customerPhone), ['anonymous', 'unknown', 'withheld', 'restricted', '00000000', '']);

            if (!$isPhoneValid) {
                Log::warning('🔒 Reschedule blocked: Customer has no valid phone number', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                    'customer_phone' => $customerPhone,
                    'call_id' => $callId,
                    'reason' => 'phone_verification_required'
                ]);

                // Create callback request for manual verification
                return $this->createAnonymousCallbackRequest($call, array_merge($params, [
                    'customer_name' => $customer->name,
                    'appointment_id' => $appointment->id,
                    'appointment_date' => $appointment->starts_at->format('Y-m-d'),
                    'reason' => 'Termin wurde ohne Telefonnummer gebucht - manuelle Verifikation erforderlich'
                ]), 'reschedule');
            }

            // Verify caller identity: caller's phone must match customer's phone
            $callerPhone = $call->from_number;
            $callerPhoneNormalized = preg_replace('/[^0-9]/', '', $callerPhone ?? '');
            $customerPhoneNormalized = preg_replace('/[^0-9]/', '', $customerPhone);

            // Match last 8 digits (handles different country code formats)
            $callerLast8 = substr($callerPhoneNormalized, -8);
            $customerLast8 = substr($customerPhoneNormalized, -8);

            if ($callerLast8 !== $customerLast8) {
                Log::warning('🚨 SECURITY: Phone mismatch - unauthorized reschedule attempt', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_phone_last8' => $customerLast8,
                    'caller_phone_last8' => $callerLast8,
                    'call_id' => $callId,
                    'security_violation' => 'phone_mismatch'
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'unauthorized',
                    'message' => sprintf(
                        'Für eine Terminänderung benötigen wir eine Verifikation. Dieser Termin ist auf eine andere Telefonnummer gebucht. Möchten Sie, dass wir Sie unter %s zurückrufen?',
                        $customerPhone
                    ),
                    'callback_available' => true,
                    'reason' => 'phone_verification_failed'
                ], 200);
            }

            Log::info('✅ Phone verification successful for reschedule', [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'phone_match' => true,
                'call_id' => $callId
            ]);

            // 3. Parse new date FIRST (before policy check)
            // FIX 2025-11-16: Support both German (new_datum, new_uhrzeit) and English (new_date, new_time)
            // Root cause: Retell Agent sends German parameter names but code expected English
            $newDate = $params['new_date'] ?? $params['new_datum'] ?? null;
            $newTime = $params['new_time'] ?? $params['new_uhrzeit'] ?? null;

            if (!$newDate || !$newTime) {
                return response()->json([
                    'success' => false,  // FIX 2025-11-16: Changed from true - this is NOT success, it's awaiting input
                    'status' => 'needs_new_time',
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

            // FIX 2025-11-16: parseDateString returns string (YYYY-MM-DD), not Carbon object
            // Convert to Carbon before calling setTime()
            $date = Carbon::parse($newDateParsed);

            // Add time to date
            if (strpos($newTime, ':') !== false) {
                list($hour, $minute) = explode(':', $newTime);
            } else {
                $hour = intval($newTime);
                $minute = 0;
            }
            $newDateTime = $date->setTime($hour, $minute);

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
                    // 🔧 FIX 2025-11-25: Use actual service duration (not hardcoded 60)
                    $alternatives = $alternativeFinder
                        ->setTenantContext($companyId, $branchId)
                        ->findAlternatives($newDateTime, $service->duration_minutes ?? 60, $service->calcom_event_type_id, $customerId);

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
            }

            // 🔧 FIX 2025-10-18: Add 2-STEP CONFIRMATION for reschedule (like collect_appointment_data)
            // 🔧 FIX 2025-11-25: Auto-confirm when called twice with same parameters (conversation flow doesn't pass bestaetigung)
            // PROBLEM: Conversation flow calls reschedule_appointment without bestaetigung parameter
            // SOLUTION: Cache the pending reschedule request, auto-confirm on second identical call
            $confirmReschedule = $params['bestaetigung'] ?? $params['confirm_reschedule'] ?? null;
            $rescheduleRequestKey = "call:{$callId}:reschedule_pending";
            $pendingReschedule = Cache::get($rescheduleRequestKey);

            // Auto-confirm if this is a second call with same parameters (user confirmed via conversation)
            if (!$confirmReschedule && $pendingReschedule) {
                $sameParams = $pendingReschedule['new_date'] === $newDateTime->format('Y-m-d') &&
                              $pendingReschedule['new_time'] === $newDateTime->format('H:i') &&
                              $pendingReschedule['appointment_id'] === $appointment->id;

                if ($sameParams) {
                    Log::info('🔄 AUTO-CONFIRM: Second reschedule call with same params, treating as confirmation', [
                        'call_id' => $callId,
                        'appointment_id' => $appointment->id,
                        'new_datetime' => $newDateTime->format('Y-m-d H:i'),
                        'pending_since' => $pendingReschedule['requested_at'],
                        'fix' => '2025-11-25 - Conversation flow auto-confirm'
                    ]);
                    $confirmReschedule = true;
                    Cache::forget($rescheduleRequestKey); // Clear pending state
                }
            }

            // STEP 1: If available but no confirmation yet → Ask for confirmation
            if (!$confirmReschedule) {
                // Cache this request for auto-confirm on second call
                Cache::put($rescheduleRequestKey, [
                    'appointment_id' => $appointment->id,
                    'new_date' => $newDateTime->format('Y-m-d'),
                    'new_time' => $newDateTime->format('H:i'),
                    'requested_at' => now()->toIso8601String()
                ], now()->addMinutes(5));

                Log::info('✅ STEP 1 - Reschedule available, requesting user confirmation', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $callId,
                    'new_date' => $newDateTime->format('Y-m-d H:i'),
                    'old_date' => $appointment->starts_at->format('Y-m-d H:i'),
                    'auto_confirm_ready' => true
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
            $oldCalcomBookingUid = $appointment->calcom_v2_booking_uid;

            // 🔧 FIX 2025-12-01: Calculate time offset for composite segments
            $timeOffset = $oldStartsAt->diffInMinutes($newDateTime, false); // Positive = moved later, negative = moved earlier

            // Update appointment with reschedule tracking
            $updateData = [
                'starts_at' => $newDateTime,
                'ends_at' => $newDateTime->copy()->addMinutes($service->duration ?? 60),
                'updated_at' => now(),
                // Reschedule tracking fields (2025-11-25)
                'rescheduled_at' => now(),
                'rescheduled_by' => 'retell_ai',
                'rescheduled_count' => ($appointment->rescheduled_count ?? 0) + 1,
                'previous_starts_at' => $oldStartsAt,
                'calcom_previous_booking_uid' => $oldCalcomBookingUid,
            ];

            // 🔧 FIX 2025-12-01: Update composite segment times
            // Each segment needs its starts_at/ends_at shifted by the same time offset
            $isComposite = $appointment->is_composite || (method_exists($appointment, 'isComposite') && $appointment->isComposite());

            if ($isComposite) {
                $segments = $appointment->segments;
                if (is_string($segments)) {
                    $segments = json_decode($segments, true);
                }

                if (is_array($segments) && !empty($segments)) {
                    $updatedSegments = [];
                    foreach ($segments as $segment) {
                        if (isset($segment['starts_at'])) {
                            $oldSegmentStart = Carbon::parse($segment['starts_at']);
                            $newSegmentStart = $oldSegmentStart->copy()->addMinutes($timeOffset);
                            $segment['starts_at'] = $newSegmentStart->toIso8601String();
                        }
                        if (isset($segment['ends_at'])) {
                            $oldSegmentEnd = Carbon::parse($segment['ends_at']);
                            $newSegmentEnd = $oldSegmentEnd->copy()->addMinutes($timeOffset);
                            $segment['ends_at'] = $newSegmentEnd->toIso8601String();
                        }
                        $updatedSegments[] = $segment;
                    }
                    $updateData['segments'] = $updatedSegments;

                    Log::info('🔄 Composite segments updated for reschedule', [
                        'appointment_id' => $appointment->id,
                        'time_offset_minutes' => $timeOffset,
                        'segments_count' => count($updatedSegments),
                        'old_start' => $oldStartsAt->toIso8601String(),
                        'new_start' => $newDateTime->toIso8601String()
                    ]);
                }
            }

            $appointment->update($updateData);

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
            // FIX 2025-11-25: Wrap in try-catch so listener failures don't affect response
            // The appointment is already saved - listener failures are non-critical
            try {
                event(new \App\Events\Appointments\AppointmentRescheduled(
                    appointment: $appointment->fresh(),
                    oldStartTime: $oldStartsAt,
                    newStartTime: $newDateTime,
                    reason: $params['reason'] ?? null,
                    fee: $policyResult->fee,
                    withinPolicy: true
                ));
            } catch (\Exception $eventException) {
                // Log but don't fail - appointment is already rescheduled
                Log::warning('⚠️ Event listener error (non-critical, appointment already saved)', [
                    'appointment_id' => $appointment->id,
                    'event' => 'AppointmentRescheduled',
                    'error' => $eventException->getMessage()
                ]);
            }

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
        // 🔧 FIX 2025-12-01: Strategy -1: Direct appointment_id lookup (HIGHEST PRIORITY)
        // When appointment_id is provided explicitly, use it directly without other lookups
        $appointmentId = $data['appointment_id'] ?? $data['termin_id'] ?? null;
        if ($appointmentId) {
            $appointment = Appointment::where('id', $appointmentId)
                ->where('company_id', $call->company_id)
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->first();

            if ($appointment) {
                Log::info('✅ Found appointment via appointment_id (direct lookup)', [
                    'appointment_id' => $appointment->id,
                    'company_id' => $call->company_id,
                    'status' => $appointment->status
                ]);
                return $appointment;
            } else {
                Log::warning('⚠️ appointment_id provided but not found or wrong company', [
                    'provided_id' => $appointmentId,
                    'company_id' => $call->company_id
                ]);
                // Fall through to other strategies in case ID was wrong
            }
        }

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

            // FIX 2025-11-25: Only auto-select if customer has EXACTLY ONE upcoming appointment
            // If multiple exist, return null to trigger multiple_found flow
            if ($call->customer_id) {
                $upcomingAppointments = Appointment::where('customer_id', $call->customer_id)
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                    ->where('starts_at', '>=', now())
                    ->orderBy('starts_at', 'asc')
                    ->limit(5)  // Only need to check if >1
                    ->get();

                if ($upcomingAppointments->count() === 1) {
                    $appointment = $upcomingAppointments->first();
                    Log::info('✅ Found ONLY upcoming appointment (auto-selected)', [
                        'appointment_id' => $appointment->id,
                        'starts_at' => $appointment->starts_at->toIso8601String(),
                        'customer_id' => $call->customer_id
                    ]);
                    return $appointment;
                } elseif ($upcomingAppointments->count() > 1) {
                    Log::info('⚠️ Multiple upcoming appointments found, NOT auto-selecting', [
                        'customer_id' => $call->customer_id,
                        'count' => $upcomingAppointments->count(),
                        'appointments' => $upcomingAppointments->pluck('id', 'starts_at')->toArray()
                    ]);
                    // Return null to trigger multiple_found response in calling function
                    return null;
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
                    // 🔧 FIX 2025-11-17: Add separate variables for Retell prompt replacement
                    // Bug: Agent was saying "{{new_datum}}" instead of actual date
                    // Solution: Return date/time as separate variables for LLM to use
                    'neues_datum' => $newDatum,              // NEW: For variable replacement in confirmation
                    'neue_uhrzeit' => $newUhrzeit,           // NEW: For variable replacement in confirmation
                    'altes_datum' => $oldDatum,              // NEW: For context if needed
                    'alte_uhrzeit' => $oldUhrzeit,           // NEW: For context if needed
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
                // Check if this is a test call
                $isTestCall = $callId && (str_starts_with($callId, 'flow_test_') ||
                                          str_starts_with($callId, 'test_') ||
                                          str_starts_with($callId, 'phase1_test_'));

                $createData = [
                    'from_number' => $parameters['from_number'] ?? $parameters['caller_number'] ?? null,
                    'to_number' => $parameters['to_number'] ?? $parameters['called_number'] ?? null,
                    'status' => $isTestCall ? 'test' : 'ongoing',
                    'call_status' => $isTestCall ? 'test' : 'ongoing',
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
        // 🔧 FIX 2025-12-01: Use Cal.com slots instead of internal availability check
        // ROOT CAUSE: ProcessingTimeAvailabilityService generates alternatives that don't exist in Cal.com
        // PROBLEM: System offered 08:00, 08:15, 08:30 but Cal.com only had 07:00, 11:00, 13:15 (UTC → Berlin)
        // SOLUTION: Fetch actual Cal.com slots for segment A event type and return those as alternatives

        $alternatives = [];

        // Get segment A event type ID from CalcomEventMap for this staff
        $segmentAMapping = \App\Models\CalcomEventMap::where('service_id', $service->id)
            ->where('segment_key', 'A')
            ->where('staff_id', $staff->id)
            ->first();

        if (!$segmentAMapping) {
            Log::warning('⚠️ findAlternativesForCompositeService: No CalcomEventMap for segment A', [
                'service_id' => $service->id,
                'staff_id' => $staff->id,
                'fallback' => 'Using internal availability check (may cause booking failures)',
            ]);

            // Fallback to original internal availability check
            return $this->findAlternativesForCompositeServiceLegacy($service, $staff, $requestedDate, $branchId);
        }

        // Use child_event_type_id if available (staff-specific), otherwise use parent
        $eventTypeId = $segmentAMapping->child_event_type_id ?? $segmentAMapping->event_type_id;

        Log::info('🔍 findAlternativesForCompositeService: Fetching Cal.com slots for segment A', [
            'service_id' => $service->id,
            'service_name' => $service->name,
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'event_type_id' => $eventTypeId,
            'requested_date' => $requestedDate->format('Y-m-d H:i'),
        ]);

        try {
            // Fetch slots from Cal.com for same day
            $sameDayResponse = $this->calcomService->getAvailableSlots(
                $eventTypeId,
                $requestedDate->copy()->startOfDay()->format('Y-m-d'),
                $requestedDate->copy()->endOfDay()->format('Y-m-d')
            );

            // Parse the response - slots are grouped by date: {"data": {"slots": {"2025-12-02": [{...}]}}}
            $responseData = $sameDayResponse->json();
            $dateKey = $requestedDate->format('Y-m-d');
            $slots = $responseData['data']['slots'][$dateKey] ?? [];

            Log::info('📅 Cal.com returned slots for composite service', [
                'event_type_id' => $eventTypeId,
                'date' => $dateKey,
                'slot_count' => count($slots),
                'slots_preview' => array_slice(array_column($slots, 'time'), 0, 5),
                'response_keys' => array_keys($responseData['data']['slots'] ?? []),
            ]);

            // Convert Cal.com slots to alternatives format
            // 🔧 FIX 2025-12-03: Ensure consistent timezone for comparison to avoid contradictory responses
            $requestedTimeStr = $requestedDate->copy()->setTimezone('Europe/Berlin')->format('Y-m-d H:i');

            foreach ($slots as $slot) {
                $slotTime = Carbon::parse($slot['time'])->setTimezone('Europe/Berlin');
                $slotTimeStr = $slotTime->format('Y-m-d H:i');

                // Skip the originally requested time (we know it's not available for the full service)
                // Both times are now guaranteed to be in Europe/Berlin timezone
                if ($slotTimeStr === $requestedTimeStr) {
                    Log::debug('🔧 Filtering out requested time from alternatives', [
                        'requested_time' => $requestedTimeStr,
                        'slot_time' => $slotTimeStr,
                        'match' => true
                    ]);
                    continue;
                }

                // Skip past times
                if ($slotTime->isPast()) {
                    continue;
                }

                $alternatives[] = [
                    'time' => $slotTime->format('Y-m-d H:i'),
                    'spoken' => $slotTime->locale('de')->isoFormat('dddd, [den] D. MMMM [um] H:mm [Uhr]'),
                    'available' => true,
                    'type' => $slotTime->isSameDay($requestedDate) ? 'same_day' : 'next_day',
                    'source' => 'calcom', // Mark as coming from Cal.com (for debugging)
                ];

                if (count($alternatives) >= 3) {
                    break;
                }
            }

            // If not enough same-day alternatives, check next few days
            if (count($alternatives) < 3) {
                for ($dayOffset = 1; $dayOffset <= 3 && count($alternatives) < 5; $dayOffset++) {
                    $nextDay = $requestedDate->copy()->addDays($dayOffset);

                    $nextDayResponse = $this->calcomService->getAvailableSlots(
                        $eventTypeId,
                        $nextDay->copy()->startOfDay()->format('Y-m-d'),
                        $nextDay->copy()->endOfDay()->format('Y-m-d')
                    );

                    $nextDayData = $nextDayResponse->json();
                    $nextDateKey = $nextDay->format('Y-m-d');
                    $slots = $nextDayData['data']['slots'][$nextDateKey] ?? [];

                    foreach ($slots as $slot) {
                        $slotTime = Carbon::parse($slot['time'])->setTimezone('Europe/Berlin');

                        if ($slotTime->isPast()) {
                            continue;
                        }

                        $alternatives[] = [
                            'time' => $slotTime->format('Y-m-d H:i'),
                            'spoken' => $slotTime->locale('de')->isoFormat('dddd, [den] D. MMMM [um] H:mm [Uhr]'),
                            'available' => true,
                            'type' => 'next_day',
                            'source' => 'calcom',
                        ];

                        if (count($alternatives) >= 5) {
                            break 2;
                        }
                    }
                }
            }

            Log::info('✅ findAlternativesForCompositeService: Generated Cal.com-based alternatives', [
                'service_id' => $service->id,
                'alternatives_count' => count($alternatives),
                'alternatives_times' => array_column($alternatives, 'time'),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ findAlternativesForCompositeService: Cal.com API error', [
                'service_id' => $service->id,
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage(),
                'fallback' => 'Using internal availability check',
            ]);

            // Fallback to original internal availability check
            return $this->findAlternativesForCompositeServiceLegacy($service, $staff, $requestedDate, $branchId);
        }

        return $alternatives;
    }

    /**
     * Legacy alternative finder for composite services (fallback when Cal.com unavailable)
     *
     * ⚠️ WARNING: This method uses internal availability checking which may not match Cal.com
     * Only use as fallback when CalcomEventMap is missing or Cal.com API fails
     */
    private function findAlternativesForCompositeServiceLegacy($service, $staff, $requestedDate, $branchId)
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
                    'type' => 'same_day',
                    'source' => 'internal_legacy', // Mark as legacy (may not match Cal.com)
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
                            'type' => 'next_day',
                            'source' => 'internal_legacy',
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
     * Cache validated alternatives for re-check skip in start_booking
     *
     * 🔧 FIX 2025-11-25: Prevents "Termin wurde gerade vergeben" when customer selects an alternative
     * PROBLEM: check_availability returns alternatives, customer selects one, start_booking re-checks and fails
     * SOLUTION: Cache alternatives as "pre-validated" for 5 minutes so start_booking trusts them
     *
     * @param string|null $callId The call identifier
     * @param array $alternatives Array of alternative slots
     * @return void
     */
    private function cacheValidatedAlternatives(?string $callId, array $alternatives, ?array $lockContext = null): void
    {
        if (!$callId || empty($alternatives)) {
            return;
        }

        // Extract slot keys from various alternative formats
        $validatedSlots = [];
        foreach ($alternatives as $alt) {
            // Handle different alternative formats
            if (isset($alt['date']) && isset($alt['time'])) {
                $validatedSlots[] = $alt['date'] . ' ' . $alt['time'];
            } elseif (isset($alt['datetime'])) {
                $dt = $alt['datetime'] instanceof Carbon ? $alt['datetime'] : Carbon::parse($alt['datetime']);
                $validatedSlots[] = $dt->format('Y-m-d H:i');
            } elseif (isset($alt['start_time'])) {
                $dt = $alt['start_time'] instanceof Carbon ? $alt['start_time'] : Carbon::parse($alt['start_time']);
                $validatedSlots[] = $dt->format('Y-m-d H:i');
            }
        }

        if (!empty($validatedSlots)) {
            Cache::put("call:{$callId}:validated_alternatives", $validatedSlots, now()->addMinutes(5));
            Cache::put("call:{$callId}:alternatives_validated_at", now(), now()->addMinutes(5));

            Log::info('📋 Cached validated alternatives for re-check skip', [
                'call_id' => $callId,
                'slots' => $validatedSlots,
                'ttl_minutes' => 5,
            ]);

            // 🔧 FIX 2025-12-11: Release previous locks BEFORE acquiring new ones
            // PROBLEM: Same call invokes get_alternatives multiple times → blocks itself with old locks
            // EXAMPLE: Customer changes mind: "Actually, I want a different time" → new alternatives needed
            // SOLUTION: Cleanup old locks first, then acquire new ones
            if ($lockContext && isset($lockContext['company_id']) && isset($lockContext['service_id'])) {
                // Step 1: Release all previous locks owned by this call
                $cleanupResult = $this->lockService->releaseAllCallLocks($callId, 'alternatives_refresh');

                if ($cleanupResult['released_count'] > 0) {
                    Log::info('🧹 Released previous slot locks before acquiring new alternatives', [
                        'call_id' => $callId,
                        'released_count' => $cleanupResult['released_count'],
                        'failed_count' => $cleanupResult['failed_count'],
                    ]);
                }

                // Step 2: Acquire new locks for the new alternatives
                $lockedSlots = [];
                $duration = $lockContext['duration'] ?? 60;

                foreach ($validatedSlots as $slot) {
                    try {
                        $slotTime = Carbon::parse($slot);
                        $lockResult = $this->lockService->acquireLock(
                            $lockContext['company_id'],
                            $lockContext['service_id'],
                            $slotTime,
                            $slotTime->copy()->addMinutes($duration),
                            $callId,
                            $lockContext['customer_phone'] ?? 'unknown',
                            ['customer_name' => $lockContext['customer_name'] ?? null]
                        );

                        if ($lockResult['success']) {
                            $lockedSlots[] = $slot;
                        } else {
                            // Log detailed conflict information for debugging
                            Log::warning('⚠️ Failed to acquire slot lock after cleanup', [
                                'call_id' => $callId,
                                'slot' => $slot,
                                'reason' => $lockResult['reason'] ?? 'unknown',
                                'locked_by' => $lockResult['locked_by'] ?? 'unknown',
                                'cleanup_result' => $cleanupResult,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('⚠️ Exception during slot lock acquisition', [
                            'call_id' => $callId,
                            'slot' => $slot,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }

                if (!empty($lockedSlots)) {
                    // Store lock keys for cleanup later
                    Cache::put("call:{$callId}:slot_locks", $lockedSlots, now()->addMinutes(5));

                    Log::info('🔒 Acquired slot locks for alternatives', [
                        'call_id' => $callId,
                        'locked_slots' => $lockedSlots,
                        'company_id' => $lockContext['company_id'],
                        'service_id' => $lockContext['service_id'],
                        'ttl_minutes' => 5
                    ]);
                }
            }
        }
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

    /**
     * 🎨 COMPOSITE SERVICE BOOKING (2025-11-27)
     *
     * Handles booking for multi-segment services like Dauerwelle.
     * Creates multiple linked appointments with proper Cal.com integration.
     *
     * FEATURES:
     * - Builds segments from service definition with proper timing
     * - Auto-assigns staff if not specified
     * - SAGA pattern for rollback on partial failure
     * - Comprehensive logging for debugging
     * - Proper slot lock release after booking
     *
     * @param Service $service The composite service to book
     * @param Carbon $startTime Desired start time
     * @param string|null $customerName Customer name
     * @param string|null $customerEmail Customer email
     * @param string|null $customerPhone Customer phone
     * @param string|null $preferredStaffId Preferred staff ID
     * @param string $callId Retell call ID
     * @param int $companyId Company context
     * @param string $branchId Branch context
     * @param string|null $lockKey Slot lock key for release
     * @param array|null $lockInfo Slot lock info
     * @return \Illuminate\Http\JsonResponse
     */
    private function bookCompositeAppointment(
        Service $service,
        Carbon $startTime,
        ?string $customerName,
        ?string $customerEmail,
        ?string $customerPhone,
        ?string $preferredStaffId,
        string $callId,
        int $companyId,
        string $branchId,
        ?string $lockKey,
        ?array $lockInfo
    ): \Illuminate\Http\JsonResponse {
        $bookingStartTime = microtime(true);

        try {
            Log::info('🎨 [COMPOSITE] Starting composite booking flow', [
                'call_id' => $callId,
                'service' => $service->name,
                'segments' => count($service->segments),
                'start_time' => $startTime->format('Y-m-d H:i'),
                'customer_name' => $customerName
            ]);

            // STEP 1: Get call record
            $call = $this->callLifecycle->findCallByRetellId($callId);
            if (!$call) {
                Log::error('🎨 [COMPOSITE] ❌ Call not found', ['call_id' => $callId]);
                return $this->responseFormatter->error('Call context not available');
            }

            // STEP 2: Ensure customer exists
            $customer = $this->customerResolver->ensureCustomerFromCall($call, $customerName, $customerEmail);
            if (!$customer) {
                Log::error('🎨 [COMPOSITE] ❌ Customer resolution failed', [
                    'call_id' => $callId,
                    'customer_name' => $customerName
                ]);
                return $this->responseFormatter->error('Kundeninformationen konnten nicht verarbeitet werden.');
            }

            Log::info('🎨 [COMPOSITE] Customer resolved', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name
            ]);

            // STEP 3: Build segments from service definition
            // 🔧 FIX 2025-11-28: Pass preferred staff ID to ensure CalcomEventMap compatibility
            $segments = $this->buildCompositeSegments($service, $startTime, $preferredStaffId);
            if (empty($segments)) {
                Log::error('🎨 [COMPOSITE] ❌ Failed to build segments', [
                    'service_id' => $service->id,
                    'service_name' => $service->name
                ]);
                return $this->responseFormatter->error('Terminsegmente konnten nicht erstellt werden.');
            }

            Log::info('🎨 [COMPOSITE] Segments built', [
                'segment_count' => count($segments),
                'total_duration' => collect($segments)->sum(fn($s) =>
                    Carbon::parse($s['starts_at'])->diffInMinutes(Carbon::parse($s['ends_at']))
                ),
                'first_segment' => $segments[0]['name'] ?? 'unknown',
                'last_segment' => end($segments)['name'] ?? 'unknown'
            ]);

            // STEP 4: Get or create CompositeBookingService
            if (!$this->compositeBookingService) {
                $this->compositeBookingService = app(CompositeBookingService::class);
            }

            // STEP 5: Prepare booking data
            // 🔧 FIX 2025-12-01: Use provided customerName if customer.name is empty
            // ROOT CAUSE: Customer records may exist with empty name (e.g., ID 1279 had phone but no name)
            // When this happens, Cal.com rejects the booking with "responses - {name}error_required_field"
            // SOLUTION: Fall back to the provided customerName parameter from the booking request
            $effectiveCustomerName = !empty($customer->name) ? $customer->name : ($customerName ?? 'Kunde');

            // 🔧 FIX 2025-12-03: Use !empty() instead of ?? for email validation
            // ROOT CAUSE (Call 62692): Retell passes empty string "" for customerEmail, not NULL
            // The ?? operator only checks for NULL, so "" passes through and Cal.com rejects it
            // Cal.com V2 API requires valid email for attendee: "Attendee must have at least one contact method"
            // SOLUTION: Generate unique placeholder email if both customer.email and customerEmail are empty
            $effectiveCustomerEmail = !empty($customer->email)
                ? $customer->email
                : (!empty($customerEmail)
                    ? $customerEmail
                    : 'booking_' . time() . '_' . substr(md5(($customerName ?? 'customer') . $call->id), 0, 8) . '@noreply.askproai.de');

            $bookingData = [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'service_id' => $service->id,
                'customer_id' => $customer->id,
                'service_name' => $service->name, // 🔧 FIX: Pass service name for bookingFieldsResponses.title
                'customer' => [
                    'name' => $effectiveCustomerName,
                    'email' => $effectiveCustomerEmail,
                    'phone' => $customerPhone ?? $customer->phone
                ],
                'segments' => $segments,
                'preferred_staff_id' => $preferredStaffId,
                'timeZone' => 'Europe/Berlin',
                'source' => 'retell_ai',
                'metadata' => [
                    'call_id' => $call->id,
                    'retell_call_id' => $callId,
                    'booked_via' => 'retell_composite_booking',
                    'created_at' => now()->toIso8601String()
                ]
            ];

            Log::info('🎨 [COMPOSITE] Customer name resolution', [
                'call_id' => $callId,
                'customer_id' => $customer->id,
                'db_customer_name' => $customer->name ?: '(empty)',
                'provided_customer_name' => $customerName ?: '(not provided)',
                'effective_name' => $effectiveCustomerName,
            ]);

            Log::info('🎨 [COMPOSITE] Calling CompositeBookingService', [
                'call_id' => $callId,
                'segments' => count($segments),
                'preferred_staff' => $preferredStaffId ?? 'auto-assign'
            ]);

            // STEP 6: Execute composite booking (with SAGA pattern)
            $appointment = $this->compositeBookingService->bookComposite($bookingData);

            $bookingDuration = round((microtime(true) - $bookingStartTime) * 1000);

            Log::info('🎨 [COMPOSITE] ✅ Composite booking successful', [
                'call_id' => $callId,
                'appointment_id' => $appointment->id,
                'composite_uid' => $appointment->composite_group_uid,
                'is_composite' => $appointment->is_composite,
                'segments_booked' => count($appointment->segments ?? []),
                'starts_at' => $appointment->starts_at->format('Y-m-d H:i'),
                'ends_at' => $appointment->ends_at->format('Y-m-d H:i'),
                'duration_ms' => $bookingDuration
            ]);

            // STEP 7: Release slot lock if it exists
            if ($lockKey && $lockInfo) {
                try {
                    $this->lockService->releaseLock($lockKey, $callId, $appointment->id);
                    Log::info('🎨 [COMPOSITE] 🔓 Slot lock released', [
                        'lock_key' => $lockKey,
                        'appointment_id' => $appointment->id
                    ]);
                } catch (\Exception $lockException) {
                    // Non-blocking - lock will expire anyway
                    Log::warning('🎨 [COMPOSITE] ⚠️ Lock release failed (non-blocking)', [
                        'lock_key' => $lockKey,
                        'error' => $lockException->getMessage()
                    ]);
                }
            }

            // STEP 8: Update call record
            $call->update([
                'appointment_made' => true,
                'converted_appointment_id' => $appointment->id,
            ]);

            // STEP 9: Build segment summary for voice response
            $activeSegments = collect($service->segments)
                ->filter(fn($s) => ($s['staff_required'] ?? false) || ($s['type'] ?? '') === 'active')
                ->values();

            $segmentSummary = $activeSegments->count() > 1
                ? sprintf('%d Termine', $activeSegments->count())
                : 'Termin';

            // Calculate end time for response
            $endTime = $appointment->ends_at ?? $startTime->copy()->addMinutes($service->duration_minutes);

            return $this->responseFormatter->success([
                'booked' => true,
                'appointment_id' => $appointment->id,
                'composite_uid' => $appointment->composite_group_uid,
                'is_composite' => true,
                'segments_count' => count($appointment->segments ?? []),
                'message' => sprintf(
                    'Perfekt! Ihre %s am %s von %s bis %s Uhr ist gebucht.',
                    $service->name,
                    $startTime->format('d.m.'),
                    $startTime->format('H:i'),
                    $endTime->format('H:i')
                ),
                'appointment_time' => $startTime->format('Y-m-d H:i'),
                'appointment_end' => $endTime->format('Y-m-d H:i'),
                'total_duration_minutes' => $service->duration_minutes,
                'confirmation' => sprintf(
                    'Der komplette Termin dauert %d Minuten mit %s.',
                    $service->duration_minutes,
                    $segmentSummary
                ),
                'sync_mode' => 'composite'
            ]);

        } catch (\App\Services\Booking\BookingConflictException $e) {
            // Slot was taken during booking
            Log::warning('🎨 [COMPOSITE] ⚠️ Booking conflict', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'service' => $service->name
            ]);

            return $this->responseFormatter->error(
                'Dieser Termin wurde leider gerade vergeben. Bitte wählen Sie eine andere Zeit.'
            );

        } catch (\Exception $e) {
            $bookingDuration = round((microtime(true) - $bookingStartTime) * 1000);

            // ═══════════════════════════════════════════════════════════════════
            // 🛡️ FIX 2025-12-03: Post-Timeout Success Check
            // PROBLEM: Booking succeeds but takes >15s → Retell timeout → success:false
            //          Agent thinks booking failed, but appointment exists in DB
            // SOLUTION: Check if appointment was created despite exception
            // BENEFIT: Returns correct success:true for successful bookings
            // ═══════════════════════════════════════════════════════════════════
            // 🔧 FIX 2025-12-03: Changed $appointmentTime to $startTime (correct variable name)
            // BUG: $appointmentTime was undefined in this method scope, causing PHP error
            $existingAppointment = Appointment::where('call_id', $call->id)
                ->where('service_id', $service->id)
                ->where('starts_at', $startTime)
                ->whereIn('status', ['booked', 'confirmed', 'scheduled'])
                ->first();

            if ($existingAppointment) {
                Log::warning('🎨 [COMPOSITE] ⚠️ Exception occurred but booking SUCCEEDED (timeout recovery)', [
                    'call_id' => $callId,
                    'appointment_id' => $existingAppointment->id,
                    'composite_uid' => $existingAppointment->composite_group_uid,
                    'duration_ms' => $bookingDuration,
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage(),
                    'recovery_type' => 'post_timeout_success_check'
                ]);

                // Return SUCCESS since booking actually completed
                return $this->responseFormatter->success([
                    'booked' => true,
                    'appointment_id' => $existingAppointment->id,
                    'composite_uid' => $existingAppointment->composite_group_uid,
                    'is_composite' => true,
                    'service' => $service->name,
                    'staff' => $existingAppointment->staff?->name ?? 'Mitarbeiter',
                    'appointment_time' => $existingAppointment->starts_at->format('Y-m-d H:i'),
                    'message' => sprintf(
                        'Ihr %s-Termin am %s um %s Uhr wurde erfolgreich gebucht.',
                        $service->name,
                        $existingAppointment->starts_at->format('d.m.Y'),
                        $existingAppointment->starts_at->format('H:i')
                    ),
                    'recovered_from_timeout' => true
                ]);
            }

            Log::error('🎨 [COMPOSITE] ❌ Composite booking failed', [
                'call_id' => $callId,
                'service' => $service->name,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'duration_ms' => $bookingDuration,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->responseFormatter->error(
                'Bei der Terminbuchung ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.'
            );
        }
    }

    /**
     * 🎨 Build segment array from composite service definition
     *
     * Converts service.segments JSON into booking segments with calculated timestamps.
     * Only includes "active" segments that require staff (skips processing/wait times).
     *
     * 🔧 FIX 2025-11-28: Accept optional staff_id to pre-assign staff to all segments
     * This ensures CalcomEventMap lookup succeeds by using a staff member known to have mappings.
     *
     * @param Service $service Composite service with segments JSON
     * @param Carbon $startTime Desired start time
     * @param string|null $staffId Optional staff ID to assign to all segments
     * @return array Array of segments for CompositeBookingService
     */
    private function buildCompositeSegments(Service $service, Carbon $startTime, ?string $staffId = null): array
    {
        $serviceSegments = $service->segments;

        if (empty($serviceSegments)) {
            Log::warning('🎨 [COMPOSITE] Service has no segments defined', [
                'service_id' => $service->id,
                'service_name' => $service->name
            ]);
            return [];
        }

        // 🔧 FIX 2025-11-28: If no staff provided, find one with CalcomEventMap entries
        if (!$staffId) {
            $staffId = $this->findStaffWithCompositeMapping($service);
            if ($staffId) {
                Log::info('🎨 [COMPOSITE] Auto-assigned staff with CalcomEventMap entries', [
                    'service_id' => $service->id,
                    'staff_id' => $staffId
                ]);
            }
        }

        // 🔧 FIX 2025-12-03: Check if consolidated booking should be used
        // This reduces Cal.com API calls by merging segments not separated by useful gaps
        if ($this->shouldUseConsolidatedBooking($service)) {
            return $this->buildConsolidatedSegments($service, $startTime, $staffId);
        }

        // Legacy: Build individual segments
        return $this->buildIndividualSegments($service, $serviceSegments, $startTime, $staffId);
    }

    /**
     * 🔧 FIX 2025-12-03: Build consolidated segments for Cal.com booking
     *
     * Uses SegmentConsolidationService to merge consecutive segments
     * that are not separated by useful gaps (≥15 min).
     *
     * Example: Dauerwelle [A][gap15][B][gap10][C][D]
     * → Consolidated: [A, gap_after:15], [B_C_D, duration:70]
     *
     * @param Service $service The composite service
     * @param Carbon $startTime Start time for first segment
     * @param string|null $staffId Staff ID to assign
     * @return array Consolidated booking segments
     */
    private function buildConsolidatedSegments(Service $service, Carbon $startTime, ?string $staffId): array
    {
        $consolidator = app(\App\Services\Booking\SegmentConsolidationService::class);
        $consolidated = $consolidator->consolidateForBooking($service->segments);

        Log::info('🎨 [COMPOSITE] Building CONSOLIDATED segments', [
            'service' => $service->name,
            'original_segments' => count($service->segments),
            'consolidated_count' => count($consolidated),
            'consolidated_keys' => array_column($consolidated, 'key'),
            'start_time' => $startTime->format('Y-m-d H:i')
        ]);

        $segments = [];
        $currentTime = $startTime->copy();

        foreach ($consolidated as $index => $group) {
            $duration = $group['duration'] ?? $group['durationMin'] ?? 60;
            $endTime = $currentTime->copy()->addMinutes($duration);

            $segments[] = [
                'key' => $group['key'],
                'name' => $group['combined_title'] ?? $group['name'] ?? $group['key'],
                'starts_at' => $currentTime->toIso8601String(),
                'ends_at' => $endTime->toIso8601String(),
                'duration' => $duration,
                'staff_id' => $staffId,
                'order' => $index + 1,
                'merged_from' => $group['merged_from'] ?? null,
            ];

            Log::debug('🎨 [COMPOSITE] Added consolidated segment', [
                'key' => $group['key'],
                'title' => $group['combined_title'] ?? $group['name'],
                'time' => $currentTime->format('H:i') . ' - ' . $endTime->format('H:i'),
                'duration' => $duration,
                'merged_from' => $group['merged_from'],
                'gap_after' => $group['gap_after'] ?? 0
            ]);

            // Move current time forward including gap_after
            $gapAfter = $group['gap_after'] ?? 0;
            $currentTime = $endTime->copy()->addMinutes($gapAfter);
        }

        Log::info('🎨 [COMPOSITE] Consolidated segments built successfully', [
            'segment_count' => count($segments),
            'total_duration' => $startTime->diffInMinutes($currentTime),
            'assigned_staff' => $staffId
        ]);

        return $segments;
    }

    /**
     * Build individual segments (legacy method)
     *
     * @param Service $service The composite service
     * @param array $serviceSegments Raw segment definitions
     * @param Carbon $startTime Start time for first segment
     * @param string|null $staffId Staff ID to assign
     * @return array Individual booking segments
     */
    private function buildIndividualSegments(Service $service, array $serviceSegments, Carbon $startTime, ?string $staffId): array
    {
        $segments = [];
        $currentTime = $startTime->copy();

        Log::debug('🎨 [COMPOSITE] Building individual segments (legacy)', [
            'service' => $service->name,
            'segment_definitions' => count($serviceSegments),
            'start_time' => $startTime->format('Y-m-d H:i'),
            'pre_assigned_staff' => $staffId
        ]);

        foreach ($serviceSegments as $index => $segment) {
            // Get duration (supports both durationMin and duration keys)
            $duration = $segment['durationMin'] ?? $segment['duration'] ?? 60;

            // Calculate end time for this segment
            $endTime = $currentTime->copy()->addMinutes($duration);

            // Only include segments that require staff (active segments)
            $requiresStaff = $segment['staff_required'] ?? (($segment['type'] ?? '') === 'active');

            if ($requiresStaff) {
                $segments[] = [
                    'key' => $segment['key'] ?? "segment_{$index}",
                    'name' => $segment['name'] ?? "Segment " . ($index + 1),
                    'starts_at' => $currentTime->toIso8601String(),
                    'ends_at' => $endTime->toIso8601String(),
                    'duration' => $duration,
                    'staff_id' => $staffId,
                    'order' => $segment['order'] ?? $index + 1
                ];

                Log::debug('🎨 [COMPOSITE] Added active segment', [
                    'key' => $segment['key'],
                    'name' => $segment['name'],
                    'time' => $currentTime->format('H:i') . ' - ' . $endTime->format('H:i'),
                    'duration' => $duration,
                    'staff_id' => $staffId
                ]);
            } else {
                Log::debug('🎨 [COMPOSITE] Skipped processing segment', [
                    'key' => $segment['key'],
                    'name' => $segment['name'],
                    'duration' => $duration,
                    'type' => $segment['type'] ?? 'unknown'
                ]);
            }

            // Move current time forward (including processing segments)
            $currentTime = $endTime->copy();
        }

        Log::info('🎨 [COMPOSITE] Segments built successfully', [
            'active_segments' => count($segments),
            'total_segments' => count($serviceSegments),
            'total_duration' => $startTime->diffInMinutes($currentTime),
            'assigned_staff' => $staffId
        ]);

        return $segments;
    }

    /**
     * Check if consolidated booking should be used for a service.
     *
     * @param Service $service The service to check
     * @return bool True if consolidated booking should be used
     */
    private function shouldUseConsolidatedBooking(Service $service): bool
    {
        if (!$this->compositeBookingService) {
            $this->compositeBookingService = app(\App\Services\Booking\CompositeBookingService::class);
        }

        return $this->compositeBookingService->shouldUseConsolidatedBooking($service);
    }

    /**
     * 🔧 FIX 2025-11-28: Find staff member with CalcomEventMap entries for composite service
     *
     * Queries CalcomEventMap to find staff who have ALL segment mappings configured.
     * This prevents booking failures due to missing CalcomEventMap entries.
     *
     * @param Service $service Composite service
     * @return string|null Staff ID with complete mappings, or null if none found
     */
    private function findStaffWithCompositeMapping(Service $service): ?string
    {
        $serviceSegments = $service->segments ?? [];
        if (empty($serviceSegments)) {
            return null;
        }

        // 🔧 FIX 2025-12-04: Use CONSOLIDATED segment keys, not original segment keys
        // BUG: Original code searched for ["A","B","C","D"] but CalcomEventMap has ["A","B_C_D"]
        // This caused "No staff found with complete CalcomEventMap entries" errors
        $consolidator = app(\App\Services\Booking\SegmentConsolidationService::class);
        $consolidatedSegments = $consolidator->consolidateForBooking($serviceSegments);

        // Get CONSOLIDATED active segment keys (e.g., ["A", "B_C_D"])
        $activeSegmentKeys = collect($consolidatedSegments)
            ->filter(fn($seg) => ($seg['staff_required'] ?? true) === true && ($seg['type'] ?? 'active') !== 'gap')
            ->pluck('key')
            ->toArray();

        if (empty($activeSegmentKeys)) {
            Log::warning('🎨 [COMPOSITE] No active consolidated segments found', [
                'service_id' => $service->id,
                'original_segments' => count($serviceSegments),
            ]);
            return null;
        }

        Log::debug('🎨 [COMPOSITE] Searching CalcomEventMap with consolidated keys', [
            'service_id' => $service->id,
            'consolidated_keys' => $activeSegmentKeys,
        ]);

        // Find staff who have mappings for ALL CONSOLIDATED active segments
        $staffWithMappings = \App\Models\CalcomEventMap::where('service_id', $service->id)
            ->whereIn('segment_key', $activeSegmentKeys)
            ->select('staff_id')
            ->selectRaw('COUNT(DISTINCT segment_key) as segment_count')
            ->groupBy('staff_id')
            ->havingRaw('COUNT(DISTINCT segment_key) = ?', [count($activeSegmentKeys)])
            ->pluck('staff_id')
            ->toArray();

        if (empty($staffWithMappings)) {
            Log::warning('🎨 [COMPOSITE] No staff found with complete CalcomEventMap entries', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'required_consolidated_segments' => $activeSegmentKeys
            ]);
            return null;
        }

        // Prefer first available staff from the list
        $selectedStaff = $staffWithMappings[0];

        Log::debug('🎨 [COMPOSITE] Found staff with complete CalcomEventMap entries', [
            'service_id' => $service->id,
            'staff_id' => $selectedStaff,
            'total_qualified_staff' => count($staffWithMappings)
        ]);

        return $selectedStaff;
    }

    /**
     * 🔧 FIX 2025-11-28: Calculate total duration for composite service
     *
     * Sums all segment durations including processing/gap times.
     * This is used for slot locking to reserve the entire composite time block.
     *
     * @param Service $service Composite service
     * @return int Total duration in minutes
     */
    private function calculateCompositeTotalDuration(Service $service): int
    {
        // Fast path: Use service duration_minutes if available
        if (!$service->isComposite() || empty($service->segments)) {
            return $service->duration_minutes ?? 60;
        }

        // Calculate from segments
        $totalDuration = collect($service->segments)->sum(function($segment) {
            return $segment['durationMin'] ?? $segment['duration'] ?? 0;
        });

        // Fallback to service duration if segments don't add up
        if ($totalDuration <= 0) {
            return $service->duration_minutes ?? 60;
        }

        Log::debug('🎨 [COMPOSITE] Calculated total duration', [
            'service_id' => $service->id,
            'service_name' => $service->name,
            'total_duration' => $totalDuration,
            'segment_count' => count($service->segments)
        ]);

        return $totalDuration;
    }

    /**
     * 🔒 Lock alternative slots to prevent race conditions
     *
     * 🔧 FIX 2025-11-26: ROOT CAUSE FIX for "wurde gerade vergeben" errors
     *
     * PROBLEM: Alternatives are presented to caller without locking them.
     * During 10-60 second decision window, another caller could book the slot.
     * Result: "Dieser Termin wurde gerade vergeben" when trying to book presented alternative.
     *
     * SOLUTION: Lock each alternative for 2 minutes (soft lock) when presenting.
     * This reserves the slots for THIS call_id only.
     *
     * @param array $alternatives Array of alternatives with 'date' and 'time' keys
     * @param int $companyId Company context for multi-tenant isolation
     * @param int $serviceId Service being booked
     * @param int $duration Service duration in minutes
     * @param string $callId Retell call ID for lock ownership
     * @param string $customerPhone Customer phone for logging
     * @return array Array of locked slot keys for potential release
     */
    private function lockAlternativeSlots(
        array $alternatives,
        int $companyId,
        int $serviceId,
        int $duration,
        string $callId,
        string $customerPhone = 'unknown'
    ): array {
        // Only lock if feature is enabled
        if (!config('features.slot_locking.enabled', false)) {
            Log::debug('🔓 Slot locking disabled, skipping alternative locks', [
                'call_id' => $callId,
                'alternatives_count' => count($alternatives),
            ]);
            return [];
        }

        $lockedKeys = [];
        $lockTtl = 120; // 2 minutes soft lock for alternatives

        foreach ($alternatives as $alternative) {
            try {
                // Parse alternative time
                $dateStr = $alternative['date'] ?? null;
                $timeStr = $alternative['time'] ?? null;

                if (!$dateStr || !$timeStr) {
                    continue;
                }

                $slotStart = Carbon::parse("{$dateStr} {$timeStr}", 'Europe/Berlin');
                $slotEnd = $slotStart->copy()->addMinutes($duration);

                // Generate lock key (using soft lock prefix to differentiate from hard locks)
                $lockKey = "alt_lock:c{$companyId}:s{$serviceId}:t{$slotStart->format('YmdHi')}";

                // Check if already locked by another call
                if (Cache::has($lockKey)) {
                    $existingLock = Cache::get($lockKey);
                    if (($existingLock['call_id'] ?? null) !== $callId) {
                        Log::debug('🔒 Alternative already locked by another call', [
                            'call_id' => $callId,
                            'blocking_call' => $existingLock['call_id'] ?? 'unknown',
                            'slot' => "{$dateStr} {$timeStr}",
                        ]);
                        continue; // Skip this alternative, don't present it
                    }
                }

                // Acquire soft lock
                Cache::put($lockKey, [
                    'call_id' => $callId,
                    'customer_phone' => $customerPhone,
                    'locked_at' => now()->toIso8601String(),
                    'slot_time' => $slotStart->toIso8601String(),
                    'type' => 'alternative_soft_lock',
                ], $lockTtl);

                $lockedKeys[] = $lockKey;

                Log::debug('🔒 Alternative slot soft-locked', [
                    'call_id' => $callId,
                    'lock_key' => $lockKey,
                    'slot' => "{$dateStr} {$timeStr}",
                    'ttl_seconds' => $lockTtl,
                ]);

            } catch (\Exception $e) {
                Log::warning('⚠️ Failed to lock alternative slot', [
                    'call_id' => $callId,
                    'alternative' => $alternative,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($lockedKeys)) {
            // Cache the locked keys for this call for later release
            Cache::put("call:{$callId}:alternative_locks", $lockedKeys, $lockTtl + 60);

            Log::info('🔒 Alternatives soft-locked for call', [
                'call_id' => $callId,
                'locked_count' => count($lockedKeys),
                'ttl_seconds' => $lockTtl,
            ]);
        }

        return $lockedKeys;
    }

    /**
     * Check if an alternative slot is locked by another call
     *
     * @param int $companyId Company ID
     * @param int $serviceId Service ID
     * @param Carbon $slotTime Slot start time
     * @param string $callId Current call ID (to check ownership)
     * @return bool True if locked by ANOTHER call
     */
    private function isAlternativeLockedByOther(
        int $companyId,
        int $serviceId,
        Carbon $slotTime,
        string $callId
    ): bool {
        $lockKey = "alt_lock:c{$companyId}:s{$serviceId}:t{$slotTime->format('YmdHi')}";

        if (!Cache::has($lockKey)) {
            return false;
        }

        $lockData = Cache::get($lockKey);
        return ($lockData['call_id'] ?? null) !== $callId;
    }

    /**
     * Check if a value is a time expression (not a valid customer name)
     * FIX 2025-12-04: Prevents time slots like "Acht Uhr" being stored as customer names
     *
     * @param string|null $value The value to check
     * @return bool True if the value is a time expression
     */
    private function isTimeExpression(?string $value): bool
    {
        if (!$value) {
            return false;
        }

        // German number words used in time expressions
        $timeWords = [
            'null', 'eins', 'zwei', 'drei', 'vier', 'fünf', 'sechs', 'sieben',
            'acht', 'neun', 'zehn', 'elf', 'zwölf', 'dreizehn', 'vierzehn',
            'fünfzehn', 'sechzehn', 'siebzehn', 'achtzehn', 'neunzehn',
            'zwanzig', 'einundzwanzig', 'zweiundzwanzig', 'dreiundzwanzig',
            'ein', 'eine', 'halb', 'viertel', 'halbe'
        ];

        $lowerValue = mb_strtolower(trim($value));

        // Pattern 1: Ends with "uhr" (e.g., "Acht Uhr", "Vierzehn Uhr")
        if (str_ends_with($lowerValue, ' uhr') || $lowerValue === 'uhr') {
            return true;
        }

        // Pattern 2: Just a number word (e.g., "Acht", "Vierzehn")
        if (in_array($lowerValue, $timeWords)) {
            return true;
        }

        // Pattern 3: Number word + "uhr" (e.g., "acht uhr", "vierzehn uhr")
        $pattern = '/^(' . implode('|', $timeWords) . ')\s*(uhr)?$/iu';
        if (preg_match($pattern, $lowerValue)) {
            return true;
        }

        // Pattern 4: Digital time format (e.g., "8:00", "14:30")
        if (preg_match('/^\d{1,2}(:\d{2})?\s*(uhr)?$/i', $lowerValue)) {
            return true;
        }

        // Pattern 5: "um X Uhr" pattern
        if (preg_match('/^um\s+\d{1,2}/i', $lowerValue)) {
            return true;
        }

        return false;
    }
}