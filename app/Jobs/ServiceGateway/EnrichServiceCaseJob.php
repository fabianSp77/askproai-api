<?php

declare(strict_types=1);

namespace App\Jobs\ServiceGateway;

use App\Models\RetellCallSession;
use App\Models\ServiceCase;
use App\Models\ServiceGatewayExchangeLog;
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
    public int $tries = 5;

    /**
     * Backoff between retries in seconds.
     */
    public int $backoff = 30;

    /**
     * Time in seconds this job is unique for.
     */
    public int $uniqueFor = 300; // 5 minutes

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
