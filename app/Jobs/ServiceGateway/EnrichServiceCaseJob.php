<?php

declare(strict_types=1);

namespace App\Jobs\ServiceGateway;

use App\Constants\ServiceGatewayConstants;
use App\Models\Call;
use App\Models\Customer;
use App\Models\RetellCallSession;
use App\Models\ServiceCase;
use App\Models\ServiceGatewayExchangeLog;
use App\Services\DeterministicCustomerMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EnrichServiceCaseJob
 *
 * Enriches a ServiceCase with transcript/audio data after a call ends.
 * Part of the 2-Phase Delivery-Gate Pattern.
 *
 * Phase 1 (During Call): ServiceCase created with enrichment_status='pending'
 * Phase 2 (After Call): This job links transcript stats and triggers audio processing
 *
 * Reliability:
 * - ShouldBeUnique: Prevents duplicate enrichment
 * - Idempotent: Skips if already enriched
 * - Bounded retries: Max 5 attempts with 30s backoff
 * - ExchangeLog: Records success/failure for debugging
 *
 * @package App\Jobs\ServiceGateway
 * @see /root/.claude/plans/zippy-skipping-lobster.md
 */
class EnrichServiceCaseJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts.
     */
    public int $tries = ServiceGatewayConstants::ENRICHMENT_MAX_ATTEMPTS;

    /**
     * Backoff between retries in seconds.
     */
    public int $backoff = ServiceGatewayConstants::ENRICHMENT_BACKOFF_SECONDS;

    /**
     * Time in seconds this job is unique for.
     */
    public int $uniqueFor = ServiceGatewayConstants::ENRICHMENT_UNIQUE_FOR_SECONDS;

    /**
     * The internal call ID (from calls table).
     */
    public int $callId;

    /**
     * The Retell call ID (external identifier).
     */
    public string $retellCallId;

    /**
     * Create a new job instance.
     *
     * @param int $callId Internal call ID from calls table
     * @param string $retellCallId External Retell call ID
     */
    public function __construct(int $callId, string $retellCallId)
    {
        $this->callId = $callId;
        $this->retellCallId = $retellCallId;
        $this->onQueue('enrichment');
    }

    /**
     * Get the unique ID for this job.
     */
    public function uniqueId(): string
    {
        return 'enrich-case-' . $this->callId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        Log::info('[EnrichServiceCaseJob] Starting enrichment', [
            'call_id' => $this->callId,
            'retell_call_id' => $this->retellCallId,
            'attempt' => $this->attempts(),
        ]);

        // Find ServiceCase by call_id
        $case = ServiceCase::where('call_id', $this->callId)->first();

        if (!$case) {
            Log::warning('[EnrichServiceCaseJob] No ServiceCase found for call', [
                'call_id' => $this->callId,
                'retell_call_id' => $this->retellCallId,
            ]);
            return;
        }

        // Idempotency check: Skip if already enriched
        if ($case->isEnriched()) {
            Log::info('[EnrichServiceCaseJob] Already enriched, skipping', [
                'case_id' => $case->id,
                'enriched_at' => $case->enriched_at,
            ]);
            return;
        }

        try {
            // Find RetellCallSession by call_id (the external retell call id)
            $session = RetellCallSession::where('call_id', $this->retellCallId)->first();

            if (!$session) {
                // Session not ready yet, retry later
                if ($this->attempts() < $this->tries) {
                    Log::info('[EnrichServiceCaseJob] Session not ready, will retry', [
                        'case_id' => $case->id,
                        'retell_call_id' => $this->retellCallId,
                        'next_attempt' => $this->attempts() + 1,
                    ]);
                    $this->release($this->backoff);
                    return;
                }

                // Max retries reached, mark as timeout
                Log::warning('[EnrichServiceCaseJob] Session never arrived, timing out', [
                    'case_id' => $case->id,
                    'retell_call_id' => $this->retellCallId,
                    'attempts' => $this->attempts(),
                ]);
                $case->markEnrichmentTimeout();
                $this->logExchange($case, 'timeout', 'Session not available after max retries');
                return;
            }

            // Check if session is completed
            if ($session->call_status !== 'completed') {
                // Session not completed yet, retry later
                if ($this->attempts() < $this->tries) {
                    Log::info('[EnrichServiceCaseJob] Session not completed, will retry', [
                        'case_id' => $case->id,
                        'session_status' => $session->call_status,
                        'next_attempt' => $this->attempts() + 1,
                    ]);
                    $this->release($this->backoff);
                    return;
                }

                // Proceed anyway after max retries
                Log::warning('[EnrichServiceCaseJob] Session still not completed, proceeding anyway', [
                    'case_id' => $case->id,
                    'session_status' => $session->call_status,
                ]);
            }

            // Calculate transcript char count
            $transcriptCharCount = DB::table('retell_transcript_segments')
                ->where('call_session_id', $session->id)
                ->sum(DB::raw('LENGTH(text)'));

            // Mark as enriched with session data
            $case->update([
                'enrichment_status' => ServiceCase::ENRICHMENT_ENRICHED,
                'enriched_at' => now(),
                'retell_call_session_id' => $session->id,
                'transcript_segment_count' => $session->transcript_segment_count,
                'transcript_char_count' => $transcriptCharCount,
            ]);

            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            // Dispatch audio processing job
            ProcessCallRecordingJob::dispatch($case);

            // Customer Matching (Phase 3): Link customer to case if not already set
            $this->matchCustomerToCase($case);

            Log::info('[EnrichServiceCaseJob] Enrichment complete', [
                'case_id' => $case->id,
                'session_id' => $session->id,
                'transcript_segments' => $session->transcript_segment_count,
                'transcript_chars' => $transcriptCharCount,
                'processing_time_ms' => $processingTime,
            ]);

            $this->logExchange($case, 'success', null, [
                'session_id' => $session->id,
                'transcript_segments' => $session->transcript_segment_count,
                'transcript_chars' => $transcriptCharCount,
            ]);

        } catch (\Exception $e) {
            Log::error('[EnrichServiceCaseJob] Enrichment failed', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->logExchange($case, 'failed', $e->getMessage());

            // Re-throw for retry
            throw $e;
        }
    }

    /**
     * Log enrichment exchange for debugging.
     */
    private function logExchange(ServiceCase $case, string $status, ?string $error = null, array $responseData = []): void
    {
        try {
            ServiceGatewayExchangeLog::create([
                'company_id' => $case->company_id,
                'service_case_id' => $case->id,
                'direction' => 'internal',
                'endpoint' => 'enrichment',
                'http_method' => 'PROCESS',
                'status_code' => $status === 'success' ? 200 : ($status === 'timeout' ? 408 : 500),
                'request_body_redacted' => json_encode([
                    'call_id' => $this->callId,
                    'retell_call_id' => $this->retellCallId,
                ]),
                'response_body_redacted' => json_encode(array_merge(
                    ['status' => $status],
                    $error ? ['error' => $error] : [],
                    $responseData
                )),
                'duration_ms' => 0,
            ]);
        } catch (\Exception $e) {
            Log::warning('[EnrichServiceCaseJob] Failed to create exchange log', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Match customer to ServiceCase using hierarchical matching.
     *
     * Matching Hierarchy:
     * 1. Phone exact match (100% confidence) - via DeterministicCustomerMatcher
     * 2. Email match (85% confidence) - direct Customer query
     * 3. Name fuzzy match (70% confidence) - based on call analysis data
     * 4. Unknown placeholder (0% confidence) - creates placeholder customer
     *
     * Security: All queries are scoped by company_id for multi-tenancy isolation.
     *
     * @param ServiceCase $case
     * @return void
     */
    private function matchCustomerToCase(ServiceCase $case): void
    {
        // If customer is already linked, try to update the name if we have a better one
        if ($case->customer_id) {
            $this->updateCustomerNameIfBetter($case);
            Log::debug('[EnrichServiceCaseJob] Customer already linked, checked for name update', [
                'case_id' => $case->id,
                'customer_id' => $case->customer_id,
            ]);
            return;
        }

        try {
            $call = Call::find($this->callId);

            if (!$call) {
                Log::warning('[EnrichServiceCaseJob] Call not found for customer matching', [
                    'case_id' => $case->id,
                    'call_id' => $this->callId,
                ]);
                return;
            }

            $customer = null;
            $matchMethod = null;
            $confidence = 0;

            // Step 1: Phone exact match (100% confidence)
            if ($call->from_number) {
                $matchResult = DeterministicCustomerMatcher::matchCustomer(
                    $call->from_number,
                    $call->to_number,
                    null
                );

                if ($matchResult['customer']) {
                    $customer = $matchResult['customer'];
                    $matchMethod = $matchResult['match_method'];
                    $confidence = $matchResult['confidence'];

                    Log::info('[EnrichServiceCaseJob] Customer matched by phone', [
                        'case_id' => $case->id,
                        'customer_id' => $customer->id,
                        'method' => $matchMethod,
                        'confidence' => $confidence,
                    ]);
                }
            }

            // Step 2: Email match (85% confidence) - if no phone match
            if (!$customer && $case->structured_data) {
                $email = $case->structured_data['email']
                    ?? $case->structured_data['caller_email']
                    ?? null;

                if ($email) {
                    $customer = Customer::where('company_id', $case->company_id)
                        ->where('email', $email)
                        ->first();

                    if ($customer) {
                        $matchMethod = 'email_match';
                        $confidence = ServiceGatewayConstants::MATCH_CONFIDENCE_EMAIL;

                        Log::info('[EnrichServiceCaseJob] Customer matched by email', [
                            'case_id' => $case->id,
                            'customer_id' => $customer->id,
                            'email' => $email,
                        ]);
                    }
                }
            }

            // Step 3: Name fuzzy match (70% confidence) - if still no match
            if (!$customer && $case->structured_data) {
                $callerName = $case->structured_data['caller_name']
                    ?? $case->structured_data['name']
                    ?? null;

                if ($callerName && strlen($callerName) >= ServiceGatewayConstants::MATCH_NAME_MIN_LENGTH) {
                    // Simple fuzzy match: search for customers with similar name
                    // Use LIKE for MySQL/MariaDB compatibility (case-insensitive with default utf8 collation)
                    // Escape LIKE wildcards to prevent unexpected matches (%, _, \)
                    $escapedName = addcslashes($callerName, '%_\\');
                    $customer = Customer::where('company_id', $case->company_id)
                        ->where(function ($query) use ($escapedName) {
                            $query->where('name', 'LIKE', '%' . $escapedName . '%')
                                ->orWhere('company_name', 'LIKE', '%' . $escapedName . '%');
                        })
                        ->first();

                    if ($customer) {
                        $matchMethod = 'name_fuzzy';
                        $confidence = ServiceGatewayConstants::MATCH_CONFIDENCE_NAME;

                        Log::info('[EnrichServiceCaseJob] Customer matched by name', [
                            'case_id' => $case->id,
                            'customer_id' => $customer->id,
                            'caller_name' => $callerName,
                        ]);
                    }
                }
            }

            // Step 4: Create unknown placeholder (0% confidence)
            if (!$customer && $call->from_number) {
                $customer = DeterministicCustomerMatcher::handleUnknownCustomer(
                    $call->from_number,
                    $case->company_id,
                    $case->structured_data ?? []
                );

                if ($customer) {
                    $matchMethod = 'unknown_placeholder';
                    $confidence = ServiceGatewayConstants::MATCH_CONFIDENCE_UNKNOWN;

                    Log::info('[EnrichServiceCaseJob] Created unknown customer placeholder', [
                        'case_id' => $case->id,
                        'customer_id' => $customer->id,
                        'phone' => $call->from_number,
                    ]);
                }
            }

            // Link customer to case if found
            if ($customer) {
                // Note: The Observer will automatically log the customer_linked action
                $case->update([
                    'customer_id' => $customer->id,
                ]);

                // Also update the call's customer_id if not set
                if (!$call->customer_id) {
                    $call->forceFill(['customer_id' => $customer->id]);
                    $call->saveQuietly();
                }

                Log::info('[EnrichServiceCaseJob] Customer linked to case', [
                    'case_id' => $case->id,
                    'customer_id' => $customer->id,
                    'match_method' => $matchMethod,
                    'confidence' => $confidence,
                ]);
            }

        } catch (\Throwable $e) {
            // Customer matching failures should NOT block enrichment
            // This is a non-critical enhancement
            Log::warning('[EnrichServiceCaseJob] Customer matching failed (non-blocking)', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update customer name if we have a better name from the call.
     *
     * This handles the case where:
     * - A customer was created as "Unbekannt #XXXX" in a previous call
     * - In a subsequent call, the caller provides their actual name
     * - We should update the customer record with the real name
     *
     * @param ServiceCase $case
     * @return void
     */
    private function updateCustomerNameIfBetter(ServiceCase $case): void
    {
        try {
            $customer = $case->customer;
            if (!$customer) {
                return;
            }

            // Only update if current name starts with "Unbekannt"
            if (!str_starts_with($customer->name, 'Unbekannt')) {
                return;
            }

            // Get the name from ai_metadata
            $aiMetadata = $case->ai_metadata ?? [];
            $newName = $aiMetadata['customer_name'] ?? null;

            // Validate the new name
            if (!$newName || strlen(trim($newName)) < 2) {
                return;
            }

            // Don't update if the new name is also a placeholder
            $lowerName = strtolower(trim($newName));
            if (in_array($lowerName, ['unbekannt', 'unknown', 'anonym', 'anonymous', 'n/a', '-'])) {
                return;
            }

            // Update the customer name
            $oldName = $customer->name;
            $customer->name = trim($newName);
            $customer->customer_type = 'individual'; // Upgrade from 'unknown'
            $customer->status = 'active'; // Upgrade from 'pending_verification'
            $customer->save();

            Log::info('[EnrichServiceCaseJob] Customer name updated from call data', [
                'case_id' => $case->id,
                'customer_id' => $customer->id,
                'old_name' => $oldName,
                'new_name' => $customer->name,
            ]);

        } catch (\Throwable $e) {
            // Name update failures should NOT block enrichment
            Log::warning('[EnrichServiceCaseJob] Customer name update failed (non-blocking)', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('[EnrichServiceCaseJob] Job permanently failed', [
            'call_id' => $this->callId,
            'retell_call_id' => $this->retellCallId,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Horizon tags for job monitoring.
     */
    public function tags(): array
    {
        return [
            'enrichment',
            'call:' . $this->callId,
            'retell:' . $this->retellCallId,
        ];
    }
}
