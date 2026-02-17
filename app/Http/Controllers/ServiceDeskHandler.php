<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\ServiceCase;
use App\Models\ServiceCaseCategory;
use App\Services\ServiceDesk\ServiceDeskLockService;
use App\Services\Retell\CallLifecycleService;
use App\Services\Gateway\IntentDetectionService;
use App\Services\RelativeTimeParser;
use App\Jobs\ServiceGateway\DeliverCaseOutputJob;
use App\Jobs\ServiceGateway\ProcessCallRecordingJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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
     * Cache TTL for issue/category data (H-001 fix: reduced from 3600s)
     */
    private const CACHE_TTL_SECONDS = 300;

    /**
     * Rate limiting: Max operations per call per minute
     * Prevents DoS and abuse of service desk functions
     */
    private const RATE_LIMIT_PER_CALL = 20;

    /**
     * Rate limiting: Decay time in seconds (1 minute)
     */
    private const RATE_LIMIT_DECAY_SECONDS = 60;

    /**
     * @param ServiceDeskLockService $lockService Lock and idempotency service
     * @param CallLifecycleService $callLifecycle Call context resolution
     */
    public function __construct(
        private ServiceDeskLockService $lockService,
        private CallLifecycleService $callLifecycle
    ) {}

    /**
     * H-002: API Authorization Guard
     *
     * IMPORTANT: ServiceDeskHandler uses API-based authorization, NOT Laravel Gate.
     * Traditional Laravel authorization requires an authenticated User model.
     * For Retell webhooks, the "actor" is the Retell system, authenticated via:
     *
     * 1. VerifyRetellWebhookSignature middleware (validates signature)
     * 2. Call context lookup (validates call exists in our system)
     * 3. Company-scoped operations (validates tenant isolation)
     * 4. CRIT-003 category validation (validates category belongs to company)
     *
     * This method provides explicit validation for defense-in-depth.
     *
     * @param string $callId The Retell call ID
     * @param string $operation The operation being performed
     * @return object|null The validated call context, or null if unauthorized
     */
    private function validateApiContext(string $callId, string $operation): ?object
    {
        // Get call context - this is our "authentication"
        $callContext = $this->callLifecycle->getCallContext($callId);

        if (!$callContext) {
            Log::warning('[ServiceDeskHandler] H-002: Unauthorized - no call context', [
                'call_id' => $callId,
                'operation' => $operation,
            ]);
            return null;
        }

        if (!$callContext->company_id) {
            Log::warning('[ServiceDeskHandler] H-002: Unauthorized - no company_id', [
                'call_id' => $callId,
                'operation' => $operation,
            ]);
            return null;
        }

        // Additional validation: Ensure the call is in a valid state
        if (isset($callContext->status) && in_array($callContext->status, ['cancelled', 'rejected'])) {
            Log::warning('[ServiceDeskHandler] H-002: Unauthorized - call in invalid state', [
                'call_id' => $callId,
                'operation' => $operation,
                'status' => $callContext->status,
            ]);
            return null;
        }

        return $callContext;
    }

    /**
     * Build tenant-isolated cache key (H-001: Multi-Tenancy Guard)
     *
     * CRITICAL: Cache keys MUST include company_id to prevent cross-tenant data leakage.
     * If company_id cannot be determined, falls back to call_id only but logs a warning.
     *
     * @param string $type Cache key type (issue, category)
     * @param string $callId Retell call ID
     * @param int|null $companyId Optional company ID (will be looked up if not provided)
     * @return string Tenant-isolated cache key
     */
    private function buildCacheKey(string $type, string $callId, ?int $companyId = null): string
    {
        // If company_id not provided, try to resolve it from call context
        if ($companyId === null) {
            $callContext = $this->callLifecycle->getCallContext($callId);
            $companyId = $callContext?->company_id;
        }

        // H-001: Include company_id in cache key for tenant isolation
        if ($companyId) {
            return "service_desk:{$companyId}:{$type}:{$callId}";
        }

        // Fallback: Use call_id only (but log warning - should not happen in production)
        Log::warning('[ServiceDeskHandler] H-001: Cache key without company_id', [
            'call_id' => $callId,
            'type' => $type,
        ]);

        return "service_desk:{$type}:{$callId}";
    }

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

        // H-002: Authorization Guard - validate API context before processing
        // Skip for non-critical operations (detect_intent is exploratory)
        $criticalOperations = ['route_ticket', 'finalize_ticket'];
        if (in_array($functionName, $criticalOperations)) {
            $callContext = $this->validateApiContext($callId, $functionName);
            if (!$callContext) {
                return response()->json([
                    'success' => false,
                    'error' => 'H-002: Unauthorized - invalid call context',
                    'message' => 'Der Anrufkontext konnte nicht validiert werden.',
                ], 403);
            }
        }

        // Rate Limiting: Prevent DoS and abuse
        $rateLimitKey = "service_desk:{$callId}:ops";
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::RATE_LIMIT_PER_CALL)) {
            $availableIn = RateLimiter::availableIn($rateLimitKey);
            Log::warning('[ServiceDeskHandler] Rate limit exceeded', [
                'call_id' => $callId,
                'function' => $functionName,
                'available_in_seconds' => $availableIn,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'message' => 'Zu viele Anfragen. Bitte warten Sie einen Moment.',
                'retry_after' => $availableIn,
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_DECAY_SECONDS);

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
            // H-001: Use tenant-isolated cache key with company_id
            $cacheKey = $this->buildCacheKey('issue', $callId);
            $issueData = [
                'subject' => $params['subject'],
                'description' => $params['description'],
                'customer_name' => $params['customer_name'] ?? null,
                'customer_email' => $params['customer_email'] ?? null,
                'customer_phone' => $params['customer_phone'] ?? null,
                'additional_info' => $params['additional_info'] ?? [],
                'collected_at' => now()->toIso8601String(),
            ];

            cache()->put($cacheKey, $issueData, self::CACHE_TTL_SECONDS);

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
            // H-001: Use tenant-isolated cache key with company_id
            $cacheKey = $this->buildCacheKey('category', $callId);
            $categoryData = [
                'case_type' => $caseType,
                'priority' => $priority,
                'category_id' => $categoryId,
                'urgency' => $params['urgency'] ?? $priority,
                'impact' => $params['impact'] ?? $priority,
                'categorized_at' => now()->toIso8601String(),
            ];

            cache()->put($cacheKey, $categoryData, self::CACHE_TTL_SECONDS);

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
                // H-001: Use tenant-isolated cache keys with company_id
                $issueData = cache()->get($this->buildCacheKey('issue', $callId, $callContext->company_id), []);
                $categoryData = cache()->get($this->buildCacheKey('category', $callId, $callContext->company_id), []);

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

                // CRIT-003: Validate category belongs to same company (Multi-Tenancy Guard)
                $category = ServiceCaseCategory::find($categoryId);
                if (!$category || $category->company_id !== $callContext->company_id) {
                    Log::critical('[ServiceDeskHandler] CRIT-003: Category-Company mismatch detected', [
                        'call_id' => $callId,
                        'requested_category_id' => $categoryId,
                        'case_company_id' => $callContext->company_id,
                        'category_company_id' => $category?->company_id,
                        'category_name' => $category?->name,
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'CRIT-003: Invalid category for this company',
                        'message' => 'Die angegebene Kategorie gehört nicht zu diesem Unternehmen.',
                    ], 403);
                }

                // Persist gateway_mode to Call record (2025-12-22)
                // This call is definitively service_desk since it's using route_ticket
                try {
                    $callContext->update([
                        'gateway_mode' => 'service_desk',
                        'detected_intent' => 'service_desk',
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[ServiceDeskHandler] Failed to persist gateway_mode', [
                        'call_id' => $callId,
                        'error' => $e->getMessage(),
                    ]);
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
                        'ai_metadata' => $this->buildRouteTicketAiMetadata(
                            $callId,
                            $callContext,
                            $issueData
                        ),
                        'status' => ServiceCase::STATUS_NEW,
                        'output_status' => ServiceCase::OUTPUT_PENDING,
                    ]);
                });

                // Mark as created (idempotency)
                $this->lockService->markCaseCreated($callId, $case->id);

                // Dispatch output delivery job (async)
                // 2-Phase Delivery-Gate: Delay dispatch if wait_for_enrichment is enabled
                $delaySeconds = $case->category?->outputConfiguration?->wait_for_enrichment ? 90 : 0;
                DeliverCaseOutputJob::dispatch($case->id)->delay(now()->addSeconds($delaySeconds));

                // Dispatch audio processing job (async, downloads recording to S3)
                // Delay 30s to ensure recording is available from Retell
                ProcessCallRecordingJob::dispatch($case)->delay(now()->addSeconds(30));

                // Clean up cached data
                // H-001: Use tenant-isolated cache keys with company_id
                cache()->forget($this->buildCacheKey('issue', $callId, $callContext->company_id));
                cache()->forget($this->buildCacheKey('category', $callId, $callContext->company_id));

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

                // Prefer flow-provided category over keyword classification
                $categoryId = null;
                $flowCategory = $params['use_case_category'] ?? null;
                if ($flowCategory && is_string($flowCategory)) {
                    $categoryId = $this->findCategoryByFlowSlug($flowCategory, $callContext->company_id);
                }
                if (!$categoryId) {
                    $categoryId = $this->classifyCategory($problemDescription, $callContext->company_id);
                    Log::info('[ServiceDeskHandler] Flow category not matched, falling back to keyword classification', [
                        'flow_category' => $flowCategory,
                        'call_id' => $callId,
                    ]);
                }

                // Determine priority (high if others affected)
                // Parse German "ja"/"nein" strings to boolean
                $othersAffectedForPriority = $this->parseGermanBoolean($params['others_affected'] ?? false);
                $priority = $othersAffectedForPriority
                    ? ServiceCase::PRIORITY_HIGH
                    : ServiceCase::PRIORITY_NORMAL;

                // Security escalation: override priority to critical when
                // Retell classify node detects security-critical keywords
                // (ransomware, erpressung, datenleck, kompromittiert, etc.)
                $useCaseDetail = $params['use_case_detail'] ?? '';
                if (is_string($useCaseDetail) && str_contains($useCaseDetail, 'escalation=critical')) {
                    $priority = ServiceCase::PRIORITY_CRITICAL;

                    Log::warning('[ServiceDeskHandler] Security escalation triggered', [
                        'call_id' => $callId,
                        'use_case_detail' => mb_substr($useCaseDetail, 0, 200),
                        'priority' => $priority,
                    ]);
                }

                // Persist gateway_mode to Call record (2025-12-22)
                // This call is definitively service_desk since it's using finalize_ticket
                try {
                    $callContext->update([
                        'gateway_mode' => 'service_desk',
                        'detected_intent' => 'service_desk',
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[ServiceDeskHandler] Failed to persist gateway_mode', [
                        'call_id' => $callId,
                        'error' => $e->getMessage(),
                    ]);
                }

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
                        'ai_metadata' => $this->buildAiMetadata($callId, $params, $callContext),
                        'status' => ServiceCase::STATUS_NEW,
                        'output_status' => ServiceCase::OUTPUT_PENDING,
                    ]);
                });

                // Mark as created (idempotency)
                $this->lockService->markCaseCreated($callId, $case->id);

                // Dispatch output delivery job (async)
                // 2-Phase Delivery-Gate: Delay dispatch if wait_for_enrichment is enabled
                $delaySeconds = $case->category?->outputConfiguration?->wait_for_enrichment ? 90 : 0;
                DeliverCaseOutputJob::dispatch($case->id)->delay(now()->addSeconds($delaySeconds));

                // Dispatch audio processing job (async, downloads recording to S3)
                // Delay 30s to ensure recording is available from Retell
                ProcessCallRecordingJob::dispatch($case)->delay(now()->addSeconds(30));

                // Update Call with customer_name from function call params
                // This ensures the name appears on the Call overview page in Filament
                if (!empty($params['customer_name']) && $callContext) {
                    $existingName = $callContext->customer_name;
                    $isPlaceholder = $existingName && (
                        str_starts_with($existingName, 'Unbekannt #') ||
                        str_starts_with($existingName, 'Unknown #')
                    );

                    // Only update if no name set or it's a placeholder
                    if (empty($existingName) || $isPlaceholder) {
                        $callContext->update([
                            'customer_name' => $params['customer_name'],
                            'customer_name_verified' => false,
                            'verification_confidence' => 50,
                            'verification_method' => 'phone_match', // ENUM: phone_match, anonymous_name, manual, unknown
                        ]);

                        Log::info('[ServiceDeskHandler] Updated Call with customer_name from finalize_ticket', [
                            'call_id' => $callContext->id,
                            'customer_name' => $params['customer_name'],
                            'previous_name' => $existingName,
                        ]);
                    }
                }

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

            // K2: Error-Handler Fallback — send backup email with collected data
            $this->sendErrorFallbackEmail($callId, $params, $e);

            return response()->json([
                'success' => false,
                'error' => 'Failed to finalize ticket',
                'message' => 'Es gab ein Problem beim Erstellen Ihres Tickets.',
            ], 500);
        }
    }

    /**
     * Auto-classify category from problem description using improved keyword matching.
     *
     * Algorithm:
     * 1. Normalize text (lowercase, German umlaut handling)
     * 2. Word boundary matching (primary, higher weight)
     * 3. Compound word matching (secondary, lower weight)
     * 4. Support for weighted keywords (keyword:weight format)
     *
     * @param string $description Problem description from caller
     * @param int $companyId Company ID for category lookup
     * @return int|null Category ID or null if no match
     */
    private function classifyCategory(string $description, int $companyId): ?int
    {
        $normalizedDescription = $this->normalizeGermanText($description);

        $categories = \App\Models\ServiceCaseCategory::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('intent_keywords')
            ->get();

        $bestMatch = null;
        $bestScore = 0;
        $matchedKeywords = [];

        foreach ($categories as $category) {
            $score = 0;
            $categoryMatches = [];

            foreach ($category->intent_keywords as $keywordEntry) {
                // Parse keyword and optional weight
                $parsed = $this->parseKeywordWeight($keywordEntry);
                $keyword = $parsed['keyword'];
                $weight = $parsed['weight'];

                $normalizedKeyword = $this->normalizeGermanText($keyword);
                $matchResult = $this->matchKeyword($normalizedDescription, $normalizedKeyword);

                if ($matchResult['matched']) {
                    $keywordScore = $weight * $matchResult['multiplier'];
                    $score += $keywordScore;
                    $categoryMatches[] = [
                        'keyword' => $keyword,
                        'type' => $matchResult['type'],
                        'score' => $keywordScore,
                    ];
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $category;
                $matchedKeywords = $categoryMatches;
            }
        }

        if ($bestMatch) {
            Log::debug('[ServiceDeskHandler] Category auto-classified', [
                'category_id' => $bestMatch->id,
                'category_name' => $bestMatch->name,
                'score' => round($bestScore, 2),
                'matched_keywords' => $matchedKeywords,
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
     * Find category by flow-provided slug.
     *
     * Maps Retell flow category slugs (network, m365, endpoint, etc.)
     * to ServiceCaseCategory records in the database.
     *
     * @param string $slug Flow category slug from Retell classify node
     * @param int $companyId Company ID for tenant isolation
     * @return int|null Category ID or null if no match
     */
    private function findCategoryByFlowSlug(string $slug, int $companyId): ?int
    {
        $slug = strtolower(trim($slug));

        // 'other' maps to default category (handled by fallback in classifyCategory)
        if ($slug === 'other' || $slug === '') {
            return null;
        }

        $categoryId = ServiceCaseCategory::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                  ->orWhere('name', 'ILIKE', "%{$slug}%");
            })
            ->value('id');

        if ($categoryId) {
            Log::debug('[ServiceDeskHandler] Category matched by flow slug', [
                'slug' => $slug,
                'category_id' => $categoryId,
                'company_id' => $companyId,
            ]);
        }

        return $categoryId;
    }

    /**
     * Normalize German text for keyword matching.
     *
     * Handles:
     * - Lowercase conversion
     * - German umlaut normalization (ä->ae, ö->oe, ü->ue)
     * - Eszett normalization (ß->ss)
     * - Punctuation removal while preserving word boundaries
     *
     * Note: Only actual umlauts are expanded, not regular a/o/u letters.
     * The matching algorithm handles both forms (drucker matches Drucker AND Drücker).
     *
     * @param string $text Input text
     * @return string Normalized text
     */
    private function normalizeGermanText(string $text): string
    {
        $text = mb_strtolower($text);

        // Convert German special characters to ASCII equivalents
        $replacements = [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
        ];

        $text = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $text
        );

        // Remove punctuation but preserve word boundaries (replace with space)
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Parse keyword entry for optional weight.
     *
     * Supports formats:
     * - "drucker" -> keyword: "drucker", weight: 0.7 (length/10)
     * - "drucker:1.5" -> keyword: "drucker", weight: 1.5
     * - {"keyword": "drucker", "weight": 1.5} -> direct object
     *
     * @param mixed $keywordEntry Keyword string or object
     * @return array{keyword: string, weight: float}
     */
    private function parseKeywordWeight(mixed $keywordEntry): array
    {
        // Handle object/array format
        if (is_array($keywordEntry)) {
            return [
                'keyword' => $keywordEntry['keyword'] ?? '',
                'weight' => (float) ($keywordEntry['weight'] ?? 1.0),
            ];
        }

        // Handle string:weight format
        if (is_string($keywordEntry) && str_contains($keywordEntry, ':')) {
            $parts = explode(':', $keywordEntry, 2);
            $keyword = trim($parts[0]);
            $weight = is_numeric($parts[1]) ? (float) $parts[1] : 1.0;
            return ['keyword' => $keyword, 'weight' => $weight];
        }

        // Default: weight based on length (longer = more specific)
        $keyword = (string) $keywordEntry;
        $weight = max(0.3, mb_strlen($keyword) / 10);

        return ['keyword' => $keyword, 'weight' => $weight];
    }

    /**
     * Match keyword against text with word boundary awareness.
     *
     * Matching types (in order of precedence):
     * 1. Exact word match (word boundaries) - multiplier: 1.0
     * 2. Word start match (compound words) - multiplier: 0.7
     * 3. Word end match (compound words) - multiplier: 0.7
     * 4. Substring match (legacy fallback) - multiplier: 0.3
     *
     * @param string $text Normalized text
     * @param string $keyword Normalized keyword
     * @return array{matched: bool, type: string, multiplier: float}
     */
    private function matchKeyword(string $text, string $keyword): array
    {
        if (empty($keyword)) {
            return ['matched' => false, 'type' => 'none', 'multiplier' => 0];
        }

        // Escape regex special characters in keyword
        $escapedKeyword = preg_quote($keyword, '/');

        // 1. Exact word boundary match (highest priority)
        // Uses \b for word boundaries, works with German characters
        if (preg_match('/(?:^|\s)' . $escapedKeyword . '(?:\s|$)/u', ' ' . $text . ' ')) {
            return ['matched' => true, 'type' => 'exact', 'multiplier' => 1.0];
        }

        // 2. Word start match (German compound words like "Netzwerkdrucker")
        // Keyword appears at start of a word
        if (preg_match('/(?:^|\s)' . $escapedKeyword . '/u', ' ' . $text . ' ')) {
            return ['matched' => true, 'type' => 'word_start', 'multiplier' => 0.7];
        }

        // 3. Word end match (German compound words like "Arbeitsplatz-PC")
        // Keyword appears at end of a word
        if (preg_match('/' . $escapedKeyword . '(?:\s|$)/u', ' ' . $text . ' ')) {
            return ['matched' => true, 'type' => 'word_end', 'multiplier' => 0.7];
        }

        // 4. Substring match (legacy fallback, low weight)
        // Only for keywords longer than 3 characters to avoid false positives
        if (mb_strlen($keyword) > 3 && str_contains($text, $keyword)) {
            return ['matched' => true, 'type' => 'substring', 'multiplier' => 0.3];
        }

        return ['matched' => false, 'type' => 'none', 'multiplier' => 0];
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

            // Persist intent data to Call record (2025-12-22)
            if ($callContext) {
                try {
                    $callContext->update([
                        'detected_intent' => $result['intent'],
                        'intent_confidence' => $result['confidence'],
                        'intent_keywords' => $result['detected_keywords'],
                    ]);

                    Log::debug('[ServiceDeskHandler] Intent persisted to call', [
                        'call_id' => $callId,
                        'intent' => $result['intent'],
                    ]);
                } catch (\Exception $persistError) {
                    // Don't fail the request if persistence fails
                    Log::warning('[ServiceDeskHandler] Failed to persist intent', [
                        'call_id' => $callId,
                        'error' => $persistError->getMessage(),
                    ]);
                }
            }

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

    /**
     * Build AI metadata with enriched timestamps.
     *
     * Converts relative time expressions like "seit fünfzehn Minuten"
     * to include absolute timestamps in German format.
     *
     * Phone number fallback chain (2025-12-30):
     * 1. Agent-provided customer_phone (highest priority - caller explicitly stated)
     * 2. Call's from_number (fallback - Caller ID from phone system)
     *
     * @param string $callId Retell call ID
     * @param array $params Function parameters
     * @param Call|null $callContext Optional Call context for phone fallback
     * @return array AI metadata
     */
    private function buildAiMetadata(string $callId, array $params, ?Call $callContext = null): array
    {
        $now = now();
        $problemSince = $params['problem_since'] ?? null;
        $problemSinceFormatted = $problemSince;
        $problemSinceAbsolute = null;

        // Enrich relative time with absolute timestamp
        if ($problemSince) {
            $parser = new RelativeTimeParser();
            $parsed = $parser->parse($problemSince, $now);

            $problemSinceFormatted = $parsed['formatted'];
            $problemSinceAbsolute = $parsed['absolute'];
        }

        // Phone number fallback: Agent-provided > Call.from_number
        $agentProvidedPhone = $params['customer_phone'] ?? null;
        $callFromNumber = $callContext?->from_number;

        // Don't use anonymous caller IDs as fallback
        if ($callFromNumber && in_array(strtolower($callFromNumber), ['anonymous', 'unknown', 'private', 'withheld'])) {
            $callFromNumber = null;
        }

        // Determine effective phone and source for audit trail
        $effectivePhone = $agentProvidedPhone ?: $callFromNumber;
        $phoneSource = $agentProvidedPhone ? 'agent' : ($callFromNumber ? 'call_record' : null);

        // Convert German "ja"/"nein" strings to boolean for others_affected
        $othersAffected = $this->parseGermanBoolean($params['others_affected'] ?? false);

        return [
            'source' => 'voice_finalize_ticket',
            'retell_call_id' => $callId,  // EXTERNAL Retell ID for audit
            'customer_name' => $params['customer_name'] ?? null,
            'customer_company' => $params['customer_company'] ?? null,
            'customer_phone' => $effectivePhone,
            'customer_phone_source' => $phoneSource,  // Audit: 'agent' or 'call_record'
            'call_from_number' => $callFromNumber,    // Original Caller ID for audit
            'customer_email' => $params['customer_email'] ?? null,
            'customer_location' => $params['customer_location'] ?? null,
            'others_affected' => $othersAffected,
            'problem_since' => $problemSinceFormatted,
            'problem_since_original' => $problemSince,
            'problem_since_absolute' => $problemSinceAbsolute,
            'use_case_detail' => $params['use_case_detail'] ?? null,
            'use_case_category' => $params['use_case_category'] ?? null,
            'finalized_at' => $now->toIso8601String(),
        ];
    }

    /**
     * Build AI metadata for routeTicket with phone fallback.
     *
     * Phone number fallback chain (2025-12-30):
     * 1. Agent-provided customer_phone from issueData (highest priority)
     * 2. Call's from_number (fallback - Caller ID from phone system)
     *
     * @param string $callId Retell call ID
     * @param Call $callContext Call context with from_number
     * @param array $issueData Cached issue data from collect_issue_details
     * @return array AI metadata
     */
    private function buildRouteTicketAiMetadata(string $callId, Call $callContext, array $issueData): array
    {
        // Phone number fallback: Agent-provided > Call.from_number
        $agentProvidedPhone = $issueData['customer_phone'] ?? null;
        $callFromNumber = $callContext->from_number;

        // Don't use anonymous caller IDs as fallback
        if ($callFromNumber && in_array(strtolower($callFromNumber), ['anonymous', 'unknown', 'private', 'withheld'])) {
            $callFromNumber = null;
        }

        // Determine effective phone and source for audit trail
        $effectivePhone = $agentProvidedPhone ?: $callFromNumber;
        $phoneSource = $agentProvidedPhone ? 'agent' : ($callFromNumber ? 'call_record' : null);

        return [
            'source' => 'voice',
            'call_id' => $callId,
            'retell_call_id' => $callContext->retell_call_id,
            'customer_name' => $issueData['customer_name'] ?? null,
            'customer_email' => $issueData['customer_email'] ?? null,
            'customer_phone' => $effectivePhone,
            'customer_phone_source' => $phoneSource,  // Audit: 'agent' or 'call_record'
            'call_from_number' => $callFromNumber,    // Original Caller ID for audit
        ];
    }

    /**
     * Parse German boolean strings ("ja"/"nein") to actual boolean values.
     *
     * Retell agents may return German strings instead of boolean values.
     * This method normalizes various representations to a boolean.
     *
     * @param mixed $value The value to parse
     * @return bool The boolean result
     */
    private function parseGermanBoolean(mixed $value): bool
    {
        // Already boolean
        if (is_bool($value)) {
            return $value;
        }

        // Handle string values
        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            // German "yes" variants
            if (in_array($normalized, ['ja', 'yes', 'true', '1', 'wahr'])) {
                return true;
            }

            // German "no" variants (and empty string)
            if (in_array($normalized, ['nein', 'no', 'false', '0', 'falsch', ''])) {
                return false;
            }
        }

        // Fallback: PHP's default boolean cast
        return (bool) $value;
    }

    /**
     * K2: Send error fallback email when ticket creation fails.
     *
     * Ensures caller data is not lost when finalize_ticket encounters an error.
     * Sends a plain-text backup email to the company's configured backup address
     * with all collected parameters from the voice call.
     *
     * @param string $callId Retell call ID
     * @param array $params Collected function call parameters
     * @param Exception $error The exception that caused the failure
     */
    private function sendErrorFallbackEmail(string $callId, array $params, Exception $error): void
    {
        try {
            // Resolve company context for backup email address
            $callContext = $this->callLifecycle->getCallContext($callId);
            $companyId = $callContext?->company_id;

            // Find backup email from ServiceOutputConfiguration via category
            $backupEmail = null;
            if ($companyId) {
                $outputConfigId = \App\Models\ServiceCaseCategory::where('company_id', $companyId)
                    ->whereNotNull('output_configuration_id')
                    ->value('output_configuration_id');

                if ($outputConfigId) {
                    $config = \App\Models\ServiceOutputConfiguration::find($outputConfigId);
                    $recipients = $config?->email_recipients;

                    // email_recipients is cast to array
                    if (is_array($recipients) && !empty($recipients)) {
                        $backupEmail = $recipients[0];
                    }
                }
            }

            // Fallback: Send to admin if no company-specific backup found
            if (empty($backupEmail)) {
                $backupEmail = config('mail.admin_address', config('mail.from.address'));
            }

            if (empty($backupEmail) || !filter_var($backupEmail, FILTER_VALIDATE_EMAIL)) {
                Log::warning('[ServiceDeskHandler] K2: No valid fallback email address available', [
                    'call_id' => $callId,
                    'backup_email' => $backupEmail,
                ]);
                return;
            }

            // Build plain-text email body with all collected data
            $body = "⚠️ FEHLER BEI TICKET-ERSTELLUNG — BACKUP\n";
            $body .= str_repeat('=', 50) . "\n\n";
            $body .= "Zeitpunkt: " . now()->timezone('Europe/Berlin')->format('d.m.Y H:i:s') . "\n";
            $body .= "Call ID: {$callId}\n";
            $body .= "Fehler: {$error->getMessage()}\n\n";
            $body .= "--- KUNDENDATEN ---\n";
            $body .= "Name: " . ($params['customer_name'] ?? 'Nicht angegeben') . "\n";
            $body .= "Firma: " . ($params['customer_company'] ?? 'Nicht angegeben') . "\n";
            $body .= "Telefon: " . ($params['customer_phone'] ?? 'Nicht angegeben') . "\n";
            $body .= "E-Mail: " . ($params['customer_email'] ?? 'Nicht angegeben') . "\n";
            $body .= "Standort: " . ($params['customer_location'] ?? 'Nicht angegeben') . "\n\n";
            $body .= "--- PROBLEMBESCHREIBUNG ---\n";
            $body .= ($params['problem_description'] ?? 'Keine Beschreibung') . "\n\n";
            $body .= "Kategorie: " . ($params['use_case_category'] ?? 'Nicht klassifiziert') . "\n";
            $body .= "Detail: " . ($params['use_case_detail'] ?? 'Nicht verfügbar') . "\n";
            $body .= "Andere betroffen: " . ($params['others_affected'] ?? 'Unbekannt') . "\n";
            $body .= "Problem seit: " . ($params['problem_since'] ?? 'Nicht angegeben') . "\n\n";
            $body .= "--- AKTION ERFORDERLICH ---\n";
            $body .= "Bitte manuell ein Ticket erstellen und den Kunden kontaktieren.\n";

            Mail::raw($body, function ($message) use ($backupEmail, $params) {
                $name = $params['customer_name'] ?? 'Unbekannt';
                $message->to($backupEmail)
                    ->subject("⚠️ Fehler-Backup: Ticket für {$name} konnte nicht erstellt werden");
            });

            Log::info('[ServiceDeskHandler] K2: Error fallback email sent', [
                'call_id' => $callId,
                'backup_email' => $backupEmail,
                'customer_name' => $params['customer_name'] ?? null,
            ]);

        } catch (Exception $emailError) {
            // Don't let email failure mask the original error
            Log::error('[ServiceDeskHandler] K2: Failed to send error fallback email', [
                'call_id' => $callId,
                'email_error' => $emailError->getMessage(),
                'original_error' => $error->getMessage(),
            ]);
        }
    }
}
