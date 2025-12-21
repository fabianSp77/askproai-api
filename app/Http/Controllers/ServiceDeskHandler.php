<?php

namespace App\Http\Controllers;

use App\Models\ServiceCase;
use App\Services\ServiceDesk\ServiceDeskLockService;
use App\Services\Retell\CallLifecycleService;
use App\Services\Gateway\IntentDetectionService;
use App\Jobs\ServiceGateway\DeliverCaseOutputJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service Desk Handler
 *
 * Handles Retell.ai function calls for service desk operations.
 * Routes to this controller when GatewayModeResolver returns mode === 'service_desk'.
 *
 * ARCHITECTURE:
 * - NOT middleware - standard controller
 * - Entry point: handle() from RetellFunctionCallHandler
 * - Idempotency: ServiceDeskLockService prevents duplicates
 * - Multi-Tenancy: CRIT-002 compliance via CallLifecycleService
 * - Transaction Safety: DB::transaction for case creation
 *
 * CRITICAL PATTERNS:
 * 1. Idempotency Check → Lock Acquisition → Double-Check → Create
 * 2. company_id validation via CallLifecycleService
 * 3. Cache-based idempotency (case_created:{callId})
 * 4. Async output delivery via DeliverCaseOutputJob
 *
 * @see ServiceDeskLockService For lock/idempotency logic
 * @see CallLifecycleService For tenant context resolution
 * @see DeliverCaseOutputJob For async case delivery
 */
class ServiceDeskHandler extends Controller
{
    /**
     * @param ServiceDeskLockService $lockService Lock and idempotency service
     * @param CallLifecycleService $callLifecycle Call context resolution
     */
    public function __construct(
        private ServiceDeskLockService $lockService,
        private CallLifecycleService $callLifecycle
    ) {}

    /**
     * Main entry point from RetellFunctionCallHandler
     *
     * Routes function calls to appropriate handlers
     *
     * @param string $functionName Retell function name
     * @param array $parameters Function parameters from Retell
     * @param string $callId Retell call ID
     * @return JsonResponse Response to Retell
     */
    public function handle(string $functionName, array $parameters, string $callId): JsonResponse
    {
        Log::info('[ServiceDeskHandler] Function call received', [
            'function' => $functionName,
            'call_id' => $callId,
            'parameters' => $parameters,
        ]);

        return match($functionName) {
            'collect_issue_details' => $this->collectIssueDetails($parameters, $callId),
            'categorize_request' => $this->categorizeRequest($parameters, $callId),
            'route_ticket' => $this->routeTicket($parameters, $callId),
            'finalize_ticket' => $this->finalizeTicket($parameters, $callId),
            'detect_intent' => $this->detectIntent($parameters, $callId),
            default => $this->handleUnknownFunction($functionName, $parameters, $callId),
        };
    }

    /**
     * Collect customer issue details
     *
     * Stores issue information in session/cache for later case creation
     *
     * @param array $params Parameters from Retell
     * @param string $callId Retell call ID
     * @return JsonResponse
     */
    private function collectIssueDetails(array $params, string $callId): JsonResponse
    {
        try {
            // Validate required fields
            $requiredFields = ['subject', 'description'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (empty($params[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing required fields',
                    'missing_fields' => $missingFields,
                    'message' => 'Bitte geben Sie weitere Details zu Ihrem Anliegen an.',
                ], 400);
            }

            // Store in cache for this call session
            $cacheKey = "service_desk:issue:{$callId}";
            $issueData = [
                'subject' => $params['subject'],
                'description' => $params['description'],
                'customer_name' => $params['customer_name'] ?? null,
                'customer_email' => $params['customer_email'] ?? null,
                'customer_phone' => $params['customer_phone'] ?? null,
                'additional_info' => $params['additional_info'] ?? [],
                'collected_at' => now()->toIso8601String(),
            ];

            cache()->put($cacheKey, $issueData, 3600); // 1 hour

            Log::info('[ServiceDeskHandler] Issue details collected', [
                'call_id' => $callId,
                'subject' => $params['subject'],
                'cache_key' => $cacheKey,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ich habe Ihr Anliegen erfasst.',
                'captured_fields' => array_keys($issueData),
            ]);

        } catch (Exception $e) {
            Log::error('[ServiceDeskHandler] Error collecting issue details', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to collect issue details',
                'message' => 'Es gab ein Problem beim Erfassen Ihrer Angaben.',
            ], 500);
        }
    }

    /**
     * Categorize the request (ITIL: incident/request/inquiry)
     *
     * Uses AI metadata or explicit categorization to determine case type and priority
     * In Phase 3, this will use TicketCategorizationService
     *
     * @param array $params Parameters from Retell
     * @param string $callId Retell call ID
     * @return JsonResponse
     */
    private function categorizeRequest(array $params, string $callId): JsonResponse
    {
        try {
            // Validate case_type
            $caseType = $params['case_type'] ?? 'inquiry';
            if (!in_array($caseType, ServiceCase::CASE_TYPES)) {
                $caseType = 'inquiry';
            }

            // Validate priority
            $priority = $params['priority'] ?? 'normal';
            if (!in_array($priority, ServiceCase::PRIORITIES)) {
                $priority = 'normal';
            }

            // Get category_id (required)
            $categoryId = $params['category_id'] ?? null;

            // Store categorization in cache
            $cacheKey = "service_desk:category:{$callId}";
            $categoryData = [
                'case_type' => $caseType,
                'priority' => $priority,
                'category_id' => $categoryId,
                'urgency' => $params['urgency'] ?? $priority,
                'impact' => $params['impact'] ?? $priority,
                'categorized_at' => now()->toIso8601String(),
            ];

            cache()->put($cacheKey, $categoryData, 3600); // 1 hour

            Log::info('[ServiceDeskHandler] Request categorized', [
                'call_id' => $callId,
                'case_type' => $caseType,
                'priority' => $priority,
                'category_id' => $categoryId,
            ]);

            return response()->json([
                'success' => true,
                'case_type' => $caseType,
                'priority' => $priority,
                'category_id' => $categoryId,
                'message' => "Ihr {$caseType} wurde mit Priorität {$priority} kategorisiert.",
            ]);

        } catch (Exception $e) {
            Log::error('[ServiceDeskHandler] Error categorizing request', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to categorize request',
                'message' => 'Es gab ein Problem bei der Kategorisierung.',
            ], 500);
        }
    }

    /**
     * Create and route the ticket
     *
     * CRITICAL: Idempotency enforced via ServiceDeskLockService
     * CRITICAL: Multi-tenancy enforced via CallLifecycleService
     *
     * @param array $params Parameters from Retell
     * @param string $callId Retell call ID
     * @return JsonResponse
     */
    private function routeTicket(array $params, string $callId): JsonResponse
    {
        try {
            // IDEMPOTENCY CHECK (before lock)
            if ($this->lockService->isCaseAlreadyCreated($callId)) {
                $caseId = $this->lockService->getExistingCaseId($callId);

                Log::info('[ServiceDeskHandler] Case already created (idempotent)', [
                    'call_id' => $callId,
                    'case_id' => $caseId,
                ]);

                return response()->json([
                    'success' => true,
                    'case_id' => $caseId,
                    'message' => 'Ihr Anliegen wurde bereits erfasst.',
                    'idempotent' => true,
                ]);
            }

            // ACQUIRE LOCK
            return $this->lockService->withCaseLock($callId, function($lock) use ($params, $callId) {
                // Double-check after lock acquisition
                if ($this->lockService->isCaseAlreadyCreated($callId)) {
                    $caseId = $this->lockService->getExistingCaseId($callId);

                    Log::info('[ServiceDeskHandler] Case already created (double-check after lock)', [
                        'call_id' => $callId,
                        'case_id' => $caseId,
                    ]);

                    return response()->json([
                        'success' => true,
                        'case_id' => $caseId,
                        'message' => 'Ihr Anliegen wurde bereits erfasst.',
                        'idempotent' => true,
                    ]);
                }

                // Get call context (multi-tenancy - CRIT-002)
                $callContext = $this->callLifecycle->getCallContext($callId);

                if (!$callContext) {
                    Log::error('[ServiceDeskHandler] CRIT-002: Call context not found', [
                        'call_id' => $callId,
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'CRIT-002: Tenant context required',
                        'message' => 'Es gab ein Problem bei der Zuordnung Ihres Anliegens.',
                    ], 400);
                }

                if (!$callContext->company_id) {
                    Log::error('[ServiceDeskHandler] CRIT-002: company_id missing', [
                        'call_id' => $callId,
                        'call_context' => $callContext->toArray(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'CRIT-002: company_id required',
                        'message' => 'Es gab ein Problem bei der Zuordnung Ihres Anliegens.',
                    ], 400);
                }

                // Retrieve cached issue and category data
                $issueData = cache()->get("service_desk:issue:{$callId}", []);
                $categoryData = cache()->get("service_desk:category:{$callId}", []);

                // Merge with params (params take precedence)
                $subject = $params['subject'] ?? $issueData['subject'] ?? 'Neues Anliegen';
                $description = $params['description'] ?? $issueData['description'] ?? '';
                $caseType = $params['case_type'] ?? $categoryData['case_type'] ?? 'inquiry';
                $priority = $params['priority'] ?? $categoryData['priority'] ?? 'normal';
                $categoryId = $params['category_id'] ?? $categoryData['category_id'] ?? null;

                // Validate required fields
                if (!$categoryId) {
                    Log::error('[ServiceDeskHandler] Missing category_id', [
                        'call_id' => $callId,
                        'params' => $params,
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Missing category_id',
                        'message' => 'Bitte geben Sie eine Kategorie für Ihr Anliegen an.',
                    ], 400);
                }

                // Create case in transaction
                $case = DB::transaction(function() use (
                    $callContext,
                    $callId,
                    $subject,
                    $description,
                    $caseType,
                    $priority,
                    $categoryId,
                    $params,
                    $issueData
                ) {
                    return ServiceCase::create([
                        'company_id' => $callContext->company_id,
                        'call_id' => $callContext->id,
                        'customer_id' => $callContext->customer_id ?? null,
                        'category_id' => $categoryId,
                        'case_type' => $caseType,
                        'priority' => $priority,
                        'urgency' => $params['urgency'] ?? $priority,
                        'impact' => $params['impact'] ?? $priority,
                        'subject' => $subject,
                        'description' => $description,
                        'structured_data' => array_merge(
                            $issueData['additional_info'] ?? [],
                            $params['structured_data'] ?? []
                        ),
                        'ai_metadata' => [
                            'source' => 'voice',
                            'call_id' => $callId,
                            'retell_call_id' => $callContext->retell_call_id,
                            'customer_name' => $issueData['customer_name'] ?? null,
                            'customer_email' => $issueData['customer_email'] ?? null,
                            'customer_phone' => $issueData['customer_phone'] ?? null,
                        ],
                        'status' => ServiceCase::STATUS_NEW,
                        'output_status' => ServiceCase::OUTPUT_PENDING,
                    ]);
                });

                // Mark as created (idempotency)
                $this->lockService->markCaseCreated($callId, $case->id);

                // Dispatch output delivery job (async)
                DeliverCaseOutputJob::dispatch($case);

                // Clean up cached data
                cache()->forget("service_desk:issue:{$callId}");
                cache()->forget("service_desk:category:{$callId}");

                Log::info('[ServiceDeskHandler] Case created successfully', [
                    'call_id' => $callId,
                    'case_id' => $case->id,
                    'case_type' => $caseType,
                    'priority' => $priority,
                    'company_id' => $callContext->company_id,
                ]);

                return response()->json([
                    'success' => true,
                    'case_id' => $case->id,
                    'status' => 'pending',
                    'message' => 'Ihr Anliegen wurde erfasst. Sie erhalten eine Bestätigung per E-Mail.',
                ]);
            });

        } catch (Exception $e) {
            Log::error('[ServiceDeskHandler] Error routing ticket', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to route ticket',
                'message' => 'Es gab ein Problem beim Erstellen Ihres Anliegens.',
            ], 500);
        }
    }

    /**
     * Finalize and create ticket from voice call
     *
     * Similar to routeTicket() but optimized for IT support scenarios.
     * Stores customer data in ai_metadata (not direct columns).
     *
     * PARAMETERS from Retell:
     * - problem_description (required): Issue description
     * - customer_name (optional): Caller name
     * - customer_phone (optional): Callback number
     * - customer_location (optional): Location/Standort
     * - others_affected (optional): bool - sets high priority if true
     *
     * @param array $params Function parameters from Retell
     * @param string $callId Retell call ID
     * @return JsonResponse
     */
    private function finalizeTicket(array $params, string $callId): JsonResponse
    {
        try {
            // IDEMPOTENCY CHECK (before lock)
            if ($this->lockService->isCaseAlreadyCreated($callId)) {
                $caseId = $this->lockService->getExistingCaseId($callId);
                $case = ServiceCase::find($caseId);

                Log::info('[ServiceDeskHandler] Ticket already finalized (idempotent)', [
                    'call_id' => $callId,
                    'case_id' => $caseId,
                    'ticket_id' => $case?->formatted_id,
                ]);

                return response()->json([
                    'success' => true,
                    'ticket_id' => $case?->formatted_id ?? "TKT-{$caseId}",
                    'message' => 'Ihr Ticket wurde bereits erstellt.',
                    'idempotent' => true,
                ]);
            }

            // ACQUIRE LOCK
            return $this->lockService->withCaseLock($callId, function($lock) use ($params, $callId) {
                // Double-check after lock acquisition
                if ($this->lockService->isCaseAlreadyCreated($callId)) {
                    $caseId = $this->lockService->getExistingCaseId($callId);
                    $case = ServiceCase::find($caseId);

                    return response()->json([
                        'success' => true,
                        'ticket_id' => $case?->formatted_id ?? "TKT-{$caseId}",
                        'message' => 'Ihr Ticket wurde bereits erstellt.',
                        'idempotent' => true,
                    ]);
                }

                // Get call context (multi-tenancy - CRIT-002)
                $callContext = $this->callLifecycle->getCallContext($callId);

                if (!$callContext || !$callContext->company_id) {
                    Log::error('[ServiceDeskHandler] CRIT-002: Tenant context missing for finalize_ticket', [
                        'call_id' => $callId,
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'CRIT-002: Tenant context required',
                        'message' => 'Es gab ein Problem bei der Zuordnung Ihres Tickets.',
                    ], 400);
                }

                // Validate required field
                $problemDescription = $params['problem_description'] ?? '';
                if (empty($problemDescription)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Missing problem_description',
                        'message' => 'Bitte beschreiben Sie Ihr Problem.',
                    ], 400);
                }

                // Auto-classify category from description
                $categoryId = $this->classifyCategory($problemDescription, $callContext->company_id);

                // Determine priority (high if others affected)
                $priority = ($params['others_affected'] ?? false)
                    ? ServiceCase::PRIORITY_HIGH
                    : ServiceCase::PRIORITY_NORMAL;

                // Create case in transaction
                $case = DB::transaction(function() use ($callContext, $callId, $params, $problemDescription, $categoryId, $priority) {
                    return ServiceCase::create([
                        'company_id' => $callContext->company_id,
                        'call_id' => $callContext->id,  // INTERNAL Call ID
                        'customer_id' => $callContext->customer_id ?? null,
                        'category_id' => $categoryId,
                        'case_type' => ServiceCase::TYPE_INCIDENT,
                        'priority' => $priority,
                        'urgency' => $priority,
                        'impact' => $priority,
                        'subject' => mb_substr($problemDescription, 0, 80),
                        'description' => $problemDescription,
                        'ai_metadata' => [
                            'source' => 'voice_finalize_ticket',
                            'retell_call_id' => $callId,  // EXTERNAL Retell ID for audit
                            'customer_name' => $params['customer_name'] ?? null,
                            'customer_phone' => $params['customer_phone'] ?? null,
                            'customer_email' => $params['customer_email'] ?? null,
                            'customer_location' => $params['customer_location'] ?? null,
                            'others_affected' => $params['others_affected'] ?? false,
                            'problem_since' => $params['problem_since'] ?? null,
                            'finalized_at' => now()->toIso8601String(),
                        ],
                        'status' => ServiceCase::STATUS_NEW,
                        'output_status' => ServiceCase::OUTPUT_PENDING,
                    ]);
                });

                // Mark as created (idempotency)
                $this->lockService->markCaseCreated($callId, $case->id);

                // Dispatch output delivery job (async)
                DeliverCaseOutputJob::dispatch($case);

                Log::info('[ServiceDeskHandler] Ticket finalized successfully', [
                    'call_id' => $callId,
                    'case_id' => $case->id,
                    'ticket_id' => $case->formatted_id,
                    'priority' => $priority,
                    'company_id' => $callContext->company_id,
                ]);

                return response()->json([
                    'success' => true,
                    'ticket_id' => $case->formatted_id,
                    'message' => "Ihr Ticket {$case->formatted_id} wurde erstellt. Ein Techniker wird sich bei Ihnen melden.",
                ]);
            });

        } catch (Exception $e) {
            Log::error('[ServiceDeskHandler] Error finalizing ticket', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to finalize ticket',
                'message' => 'Es gab ein Problem beim Erstellen Ihres Tickets.',
            ], 500);
        }
    }

    /**
     * Auto-classify category from problem description using keyword matching.
     *
     * Searches ServiceCaseCategory models for the company and scores
     * based on keyword matches in the description.
     *
     * @param string $description Problem description from caller
     * @param int $companyId Company ID for category lookup
     * @return int|null Category ID or null if no match
     */
    private function classifyCategory(string $description, int $companyId): ?int
    {
        $description = mb_strtolower($description);

        $categories = \App\Models\ServiceCaseCategory::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('intent_keywords')
            ->get();

        $bestMatch = null;
        $bestScore = 0;

        foreach ($categories as $category) {
            $score = 0;
            foreach ($category->intent_keywords as $keyword) {
                if (str_contains($description, mb_strtolower($keyword))) {
                    $score += mb_strlen($keyword); // Longer keywords = higher score
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $category;
            }
        }

        if ($bestMatch) {
            Log::debug('[ServiceDeskHandler] Category auto-classified', [
                'category_id' => $bestMatch->id,
                'category_name' => $bestMatch->name,
                'score' => $bestScore,
            ]);
            return $bestMatch->id;
        }

        // Fallback to default category
        $defaultCategory = \App\Models\ServiceCaseCategory::where('company_id', $companyId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($defaultCategory) {
            Log::debug('[ServiceDeskHandler] Using default category', [
                'category_id' => $defaultCategory->id,
                'category_name' => $defaultCategory->name,
            ]);
            return $defaultCategory->id;
        }

        Log::warning('[ServiceDeskHandler] No category found for company', [
            'company_id' => $companyId,
        ]);

        return null;
    }

    /**
     * Detect intent from user utterance
     *
     * Uses IntentDetectionService to classify caller intent and recommend routing.
     * This function can be called by Retell.ai agent during hybrid mode to
     * dynamically detect whether the call is appointment or service-related.
     *
     * PARAMETERS:
     * - utterance (required): User's spoken text to analyze
     * - text (optional): Alternative parameter name for utterance
     *
     * RESPONSE:
     * - success: Whether detection was successful
     * - intent: 'appointment' | 'service_desk' | 'unknown'
     * - confidence: 0.0-1.0 confidence score
     * - detected_keywords: Array of matched keywords
     * - explanation: Human-readable explanation
     * - recommended_mode: 'appointment' | 'service_desk' | 'clarify'
     *
     * @param array $params Function parameters from Retell
     * @param string $callId Retell call ID
     * @return JsonResponse
     */
    private function detectIntent(array $params, string $callId): JsonResponse
    {
        try {
            // Get utterance from parameters (support both 'utterance' and 'text')
            $utterance = $params['utterance'] ?? $params['text'] ?? '';

            if (empty($utterance)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing utterance parameter',
                    'message' => 'Bitte wiederholen Sie Ihr Anliegen.',
                ], 400);
            }

            // Get company context for category-specific keywords
            $callContext = $this->callLifecycle->getCallContext($callId);
            $companyId = $callContext?->company_id;

            // Perform intent detection
            $intentService = app(IntentDetectionService::class);
            $result = $intentService->detectIntent($utterance, $companyId);

            // Determine recommended mode based on confidence threshold
            $threshold = config('gateway.hybrid.intent_confidence_threshold', 0.75);
            $recommendedMode = $result['confidence'] >= $threshold ? $result['intent'] : 'clarify';

            Log::info('[ServiceDeskHandler] Intent detected', [
                'call_id' => $callId,
                'intent' => $result['intent'],
                'confidence' => $result['confidence'],
                'recommended_mode' => $recommendedMode,
                'utterance_preview' => substr($utterance, 0, 100),
            ]);

            return response()->json([
                'success' => true,
                'intent' => $result['intent'],
                'confidence' => $result['confidence'],
                'detected_keywords' => $result['detected_keywords'],
                'explanation' => $result['explanation'],
                'recommended_mode' => $recommendedMode,
                'message' => $this->getIntentConfirmationMessage($result['intent'], $recommendedMode),
            ]);

        } catch (Exception $e) {
            Log::error('[ServiceDeskHandler] Error detecting intent', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to detect intent',
                'message' => 'Es gab ein Problem bei der Erkennung Ihres Anliegens.',
            ], 500);
        }
    }

    /**
     * Get confirmation message for detected intent
     *
     * @param string $intent Detected intent
     * @param string $recommendedMode Recommended routing mode
     * @return string Confirmation message in German
     */
    private function getIntentConfirmationMessage(string $intent, string $recommendedMode): string
    {
        if ($recommendedMode === 'clarify') {
            return 'Ich bin mir nicht sicher, was Ihr Anliegen ist. Könnten Sie das bitte genauer beschreiben?';
        }

        return match($intent) {
            'appointment' => 'Ich verstehe, Sie möchten einen Termin vereinbaren.',
            'service_desk' => 'Ich verstehe, Sie haben eine Frage oder benötigen Unterstützung.',
            default => 'Ich höre Ihnen zu. Bitte beschreiben Sie Ihr Anliegen.',
        };
    }

    /**
     * Handle unknown function calls
     *
     * @param string $functionName Function name
     * @param array $parameters Function parameters
     * @param string $callId Retell call ID
     * @return JsonResponse
     */
    private function handleUnknownFunction(string $functionName, array $parameters, string $callId): JsonResponse
    {
        Log::warning('[ServiceDeskHandler] Unknown function called', [
            'function' => $functionName,
            'call_id' => $callId,
            'parameters' => $parameters,
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Unknown function',
            'function' => $functionName,
            'message' => 'Diese Funktion ist nicht verfügbar.',
        ], 400);
    }
}
