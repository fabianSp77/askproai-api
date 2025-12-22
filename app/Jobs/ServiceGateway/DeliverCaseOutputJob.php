<?php

namespace App\Jobs\ServiceGateway;

use App\Models\ServiceCase;
use App\Services\ServiceGateway\OutputHandlerFactory;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Deliver Case Output Job
 *
 * Queued job for delivering service case outputs via configured handlers.
 * Supports retry logic with exponential backoff and comprehensive error tracking.
 *
 * Pattern matches: DeliverWebhookJob
 * Queue: Configurable via gateway.output.queue
 * Retry: 3 attempts with 60s backoff
 */
class DeliverCaseOutputJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 60; // Retry after 60 seconds

    public function __construct(
        public ServiceCase $case
    ) {
        $this->queue = config('gateway.output.queue', 'default');
    }

    /**
     * Execute job - deliver case output via appropriate handler
     */
    public function handle(OutputHandlerFactory $factory): void
    {
        $startTime = microtime(true);

        Log::info('[DeliverCaseOutputJob] Starting delivery', [
            'case_id' => $this->case->id,
            'company_id' => $this->case->company_id,
            'category_id' => $this->case->category_id,
            'attempt' => $this->attempts(),
        ]);

        // Skip if already sent
        if ($this->case->output_status === 'sent') {
            Log::info('[DeliverCaseOutputJob] Already delivered', [
                'case_id' => $this->case->id,
                'output_sent_at' => $this->case->output_sent_at,
            ]);
            return;
        }

        // Delivery-Gate: Wait for enrichment if configured
        // Part of 2-Phase Delivery-Gate Pattern
        $config = $this->case->category?->outputConfiguration;
        $waitForEnrichment = $config?->wait_for_enrichment ?? false;

        if ($waitForEnrichment && $this->case->enrichment_status === ServiceCase::ENRICHMENT_PENDING) {
            $timeoutSeconds = $config?->enrichment_timeout_seconds ?? 180;
            $caseAge = now()->diffInSeconds($this->case->created_at);

            Log::info('[DeliverCaseOutputJob] Checking enrichment gate', [
                'case_id' => $this->case->id,
                'enrichment_status' => $this->case->enrichment_status,
                'case_age_seconds' => $caseAge,
                'timeout_seconds' => $timeoutSeconds,
                'attempt' => $this->attempts(),
            ]);

            if ($caseAge < $timeoutSeconds && $this->attempts() < $this->tries) {
                Log::info('[DeliverCaseOutputJob] Waiting for enrichment, releasing', [
                    'case_id' => $this->case->id,
                    'release_seconds' => 30,
                ]);
                $this->release(30); // Retry in 30s
                return;
            }

            // Timeout reached - proceed with partial data
            Log::warning('[DeliverCaseOutputJob] Enrichment timeout, proceeding with partial data', [
                'case_id' => $this->case->id,
                'case_age_seconds' => $caseAge,
            ]);
            $this->case->update(['enrichment_status' => ServiceCase::ENRICHMENT_TIMEOUT]);
        }

        try {
            // Get appropriate handler for this case
            $handler = $factory->makeForCase($this->case);

            Log::debug('[DeliverCaseOutputJob] Handler resolved', [
                'case_id' => $this->case->id,
                'handler_type' => $handler->getType(),
            ]);

            // Attempt delivery
            $success = $handler->deliver($this->case);

            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            if ($success) {
                $this->markSuccess($handler->getType(), $processingTime);
            } else {
                throw new Exception('Handler returned false - delivery failed');
            }

        } catch (Exception $e) {
            $this->handleFailure($e);

            // Retry if attempts remaining
            if ($this->attempts() < $this->tries) {
                Log::warning('[DeliverCaseOutputJob] Retrying delivery', [
                    'case_id' => $this->case->id,
                    'next_attempt' => $this->attempts() + 1,
                    'backoff_seconds' => $this->backoff,
                ]);

                $this->release($this->backoff);
            } else {
                // Final attempt failed - will trigger failed()
                throw $e;
            }
        }
    }

    /**
     * Mark delivery as successful
     */
    private function markSuccess(string $handlerType, int $processingTimeMs): void
    {
        $this->case->update([
            'output_status' => 'sent',
            'output_sent_at' => now(),
            'output_error' => null,
        ]);

        Log::info('[DeliverCaseOutputJob] Delivery successful', [
            'case_id' => $this->case->id,
            'handler' => $handlerType,
            'processing_time_ms' => $processingTimeMs,
            'attempt' => $this->attempts(),
        ]);
    }

    /**
     * Handle delivery failure
     */
    private function handleFailure(Exception $e): void
    {
        $this->case->update([
            'output_status' => 'failed',
            'output_error' => $e->getMessage(),
        ]);

        Log::error('[DeliverCaseOutputJob] Delivery failed', [
            'case_id' => $this->case->id,
            'company_id' => $this->case->company_id,
            'error' => $e->getMessage(),
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
        ]);
    }

    /**
     * Handle permanent job failure after all retries
     */
    public function failed(Exception $exception): void
    {
        Log::critical('[DeliverCaseOutputJob] Job permanently failed', [
            'case_id' => $this->case->id,
            'company_id' => $this->case->company_id,
            'category_id' => $this->case->category_id,
            'exception' => $exception->getMessage(),
            'total_attempts' => $this->attempts(),
        ]);

        $this->case->update([
            'output_status' => 'failed',
            'output_error' => 'Permanent failure after ' . $this->attempts() . ' attempts: ' . $exception->getMessage(),
        ]);
    }

    /**
     * Horizon tags for job monitoring
     */
    public function tags(): array
    {
        return [
            'service-case:' . $this->case->id,
            'company:' . $this->case->company_id,
            'category:' . ($this->case->category_id ?? 'none'),
        ];
    }
}
