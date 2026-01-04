<?php

declare(strict_types=1);

namespace App\Jobs\ServiceGateway;

use App\Constants\ServiceGatewayConstants;
use App\Models\ServiceGatewayExchangeLog;
use App\Models\ServiceCase;
use App\Services\Audio\AudioStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * ProcessCallRecordingJob
 *
 * Downloads audio recordings from Retell and stores them in S3/MinIO.
 *
 * Security:
 * - Recording URL is fetched from Call relationship, NEVER persisted
 * - Only the S3 object key is stored on ServiceCase
 * - Auto-expires after 60 days
 *
 * Reliability:
 * - ShouldBeUnique: Prevents duplicate processing
 * - Idempotent: Skips if already processed
 * - Bounded retries: Max 3 attempts with 60s backoff
 * - ExchangeLog: Records success/failure for debugging
 *
 * NOTE: Intentionally does NOT use SerializesModels trait to avoid
 * closure serialization issues with ServiceCase model relationships.
 * Instead, we serialize only the case_id and load fresh in handle().
 *
 * @package App\Jobs\ServiceGateway
 */
class ProcessCallRecordingJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Maximum number of retry attempts.
     */
    public int $tries = ServiceGatewayConstants::AUDIO_MAX_ATTEMPTS;

    /**
     * Backoff between retries in seconds.
     */
    public int $backoff = ServiceGatewayConstants::AUDIO_BACKOFF_SECONDS;

    /**
     * Time in seconds this job is unique for.
     */
    public int $uniqueFor = ServiceGatewayConstants::AUDIO_UNIQUE_FOR_SECONDS;

    /**
     * The service case ID - loaded fresh in handle() to avoid serialization issues.
     */
    public int $caseId;

    /**
     * The service case instance - loaded in handle().
     */
    protected ?ServiceCase $case = null;

    /**
     * Create a new job instance.
     */
    public function __construct(ServiceCase|int $case)
    {
        $this->caseId = $case instanceof ServiceCase ? $case->id : $case;
        $this->onQueue('audio-processing');
    }

    /**
     * Get the unique ID for this job.
     */
    public function uniqueId(): string
    {
        return 'audio-processing-' . $this->caseId;
    }

    /**
     * Execute the job.
     */
    public function handle(AudioStorageService $audioService): void
    {
        // Load case fresh to avoid serialization issues
        $this->case = ServiceCase::find($this->caseId);

        if (!$this->case) {
            Log::error('[ProcessCallRecording] Case not found', [
                'case_id' => $this->caseId,
            ]);
            return;
        }

        // Idempotency check: Skip if already processed
        if ($this->case->audio_object_key) {
            Log::info('[ProcessCallRecording] Already processed, skipping', [
                'case_id' => $this->case->id,
                'audio_object_key' => $this->case->audio_object_key,
            ]);
            return;
        }

        // Load call relationship
        if (!$this->case->relationLoaded('call')) {
            $this->case->load('call');
        }

        // Get recording URL from Call (NEVER persist this!)
        $recordingUrl = $this->case->call?->recording_url;

        if (!$recordingUrl) {
            $this->logSkipped('No recording URL available');
            return;
        }

        Log::info('[ProcessCallRecording] Starting audio download', [
            'case_id' => $this->case->id,
            'call_id' => $this->case->call_id,
        ]);

        try {
            // Download and store in S3
            $objectKey = $audioService->downloadAndStore($recordingUrl, $this->case);

            if (!$objectKey) {
                $this->logFailed('Download returned null');
                // Don't throw - might be transient, let retry handle it
                $this->release($this->backoff);
                return;
            }

            // Update case with object key and expiration
            $this->case->update([
                'audio_object_key' => $objectKey,
                'audio_expires_at' => now()->addDays($audioService->getRetentionDays()),
            ]);

            $this->logSuccess($objectKey, $audioService->getSizeMb($objectKey));

        } catch (\Exception $e) {
            $this->logFailed($e->getMessage());

            // Re-throw for retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Load case if not already loaded
        $case = $this->case ?? ServiceCase::find($this->caseId);

        Log::error('[ProcessCallRecording] Job failed permanently', [
            'case_id' => $this->caseId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        if ($case) {
            ServiceGatewayExchangeLog::create([
                'company_id' => $case->company_id,
                'service_case_id' => $case->id,
                'direction' => 'internal',
                'endpoint' => 'audio-storage',
                'http_method' => 'PUT',
                'status_code' => 500,
                'request_body_redacted' => [
                    'case_id' => $case->id,
                    'call_id' => $case->call_id,
                ],
                'response_body_redacted' => [
                    'status' => 'failed_permanently',
                    'error' => $exception->getMessage(),
                    'attempts' => $this->attempts(),
                ],
                'duration_ms' => 0,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Log successful audio processing.
     */
    private function logSuccess(string $objectKey, float $sizeMb): void
    {
        Log::info('[ProcessCallRecording] Audio stored successfully', [
            'case_id' => $this->case->id,
            'object_key' => $objectKey,
            'size_mb' => $sizeMb,
        ]);

        ServiceGatewayExchangeLog::create([
            'company_id' => $this->case->company_id,
            'service_case_id' => $this->case->id,
            'direction' => 'internal',
            'endpoint' => 'audio-storage',
            'http_method' => 'PUT',
            'status_code' => 200,
            'request_body_redacted' => [
                'case_id' => $this->case->id,
                'call_id' => $this->case->call_id,
            ],
            'response_body_redacted' => [
                'status' => 'success',
                'object_key' => $objectKey,
                'size_mb' => $sizeMb,
            ],
            'duration_ms' => 0,
        ]);
    }

    /**
     * Log skipped processing (no recording available).
     */
    private function logSkipped(string $reason): void
    {
        Log::info('[ProcessCallRecording] Skipped', [
            'case_id' => $this->case->id,
            'reason' => $reason,
        ]);

        ServiceGatewayExchangeLog::create([
            'company_id' => $this->case->company_id,
            'service_case_id' => $this->case->id,
            'direction' => 'internal',
            'endpoint' => 'audio-storage',
            'http_method' => 'PUT',
            'status_code' => 204,
            'request_body_redacted' => [
                'case_id' => $this->case->id,
            ],
            'response_body_redacted' => [
                'status' => 'skipped',
                'reason' => $reason,
            ],
            'duration_ms' => 0,
        ]);
    }

    /**
     * Log failed processing attempt.
     */
    private function logFailed(string $error): void
    {
        Log::warning('[ProcessCallRecording] Attempt failed', [
            'case_id' => $this->case->id,
            'error' => $error,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
        ]);

        ServiceGatewayExchangeLog::create([
            'company_id' => $this->case->company_id,
            'service_case_id' => $this->case->id,
            'direction' => 'internal',
            'endpoint' => 'audio-storage',
            'http_method' => 'PUT',
            'status_code' => 500,
            'request_body_redacted' => [
                'case_id' => $this->case->id,
                'call_id' => $this->case->call_id,
            ],
            'response_body_redacted' => [
                'status' => 'failed',
                'error' => $error,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ],
            'duration_ms' => 0,
            'error_message' => $error,
            'attempt_no' => $this->attempts(),
            'max_attempts' => $this->tries,
        ]);
    }
}
