<?php

namespace App\Jobs\ServiceGateway;

use App\Constants\ServiceGatewayConstants;
use App\Models\ServiceCase;
use App\Notifications\DeliveryFailedNotification;
use App\Services\Gateway\Config\GatewayConfigService;
use App\Services\ServiceGateway\OutputHandlerFactory;
use Exception;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Deliver Case Output Job
 *
 * Queued job for delivering service case outputs via configured handlers.
 * Supports retry logic with exponential backoff and comprehensive error tracking.
 *
 * Pattern matches: DeliverWebhookJob
 * Queue: Configurable via gateway.output.queue
 * Retry: 3 attempts with 60s backoff
 *
 * NOTE: Intentionally does NOT use SerializesModels trait to avoid
 * closure serialization issues with ServiceCase model relationships.
 * Instead, we serialize only the case_id and load fresh in handle().
 */
class DeliverCaseOutputJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = ServiceGatewayConstants::DELIVERY_MAX_ATTEMPTS;
    public int $timeout = ServiceGatewayConstants::DELIVERY_JOB_TIMEOUT_SECONDS;

    /**
     * Exponential backoff: 1min, 2min, 5min between retries
     * This gives time for transient issues to resolve while avoiding retry storms
     */
    public function backoff(): array
    {
        return ServiceGatewayConstants::DELIVERY_BACKOFF_DELAYS;
    }

    /**
     * Get the backoff delay for a specific attempt number
     */
    private function getBackoffForAttempt(int $attempt): int
    {
        $backoffs = $this->backoff();
        // Array is 0-indexed, attempts are 1-indexed
        $index = max(0, $attempt - 1);
        return $backoffs[$index] ?? end($backoffs);
    }

    /**
     * The case instance - loaded fresh in handle() to avoid serialization issues
     */
    protected ?ServiceCase $case = null;

    public function __construct(
        public int $caseId
    ) {
        $this->queue = config('gateway.output.queue', 'default');
    }

    /**
     * Execute job - deliver case output via appropriate handler
     */
    public function handle(OutputHandlerFactory $factory): void
    {
        $startTime = microtime(true);

        // Load the case fresh to avoid serialization issues
        $this->case = ServiceCase::find($this->caseId);

        if (!$this->case) {
            Log::error('[DeliverCaseOutputJob] Case not found', [
                'case_id' => $this->caseId,
            ]);
            return;
        }

        // Load call relationship for timeline metrics
        $this->case->load('call');
        $callStartedAt = $this->case->call?->started_at;
        $callEndedAt = $this->case->call?->ended_at;

        Log::info('[DeliverCaseOutputJob] 📤 Starting delivery', [
            'case_id' => $this->case->id,
            'company_id' => $this->case->company_id,
            'category_id' => $this->case->category_id,
            'attempt' => $this->attempts(),
            'timeline' => [
                'call_started_at' => $callStartedAt?->toIso8601String(),
                'call_ended_at' => $callEndedAt?->toIso8601String(),
                'case_created_at' => $this->case->created_at?->toIso8601String(),
                'seconds_since_call_end' => $callEndedAt ? now()->diffInSeconds($callEndedAt) : null,
                'seconds_since_case_created' => now()->diffInSeconds($this->case->created_at),
            ],
            'enrichment_status' => $this->case->enrichment_status,
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
            $timeoutSeconds = $config?->enrichment_timeout_seconds ?? ServiceGatewayConstants::DELIVERY_ENRICHMENT_DEFAULT_TIMEOUT;
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
                    'release_seconds' => ServiceGatewayConstants::DELIVERY_ENRICHMENT_GATE_RETRY_SECONDS,
                ]);
                $this->release(ServiceGatewayConstants::DELIVERY_ENRICHMENT_GATE_RETRY_SECONDS);
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
                $backoffSeconds = $this->getBackoffForAttempt($this->attempts());

                Log::warning('[DeliverCaseOutputJob] Retrying delivery', [
                    'case_id' => $this->case->id,
                    'next_attempt' => $this->attempts() + 1,
                    'backoff_seconds' => $backoffSeconds,
                ]);

                $this->release($backoffSeconds);
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

        // Calculate timeline metrics
        $caseCreatedAt = $this->case->created_at;
        $deliveryLatencySeconds = $caseCreatedAt ? now()->diffInSeconds($caseCreatedAt) : null;

        Log::info('[DeliverCaseOutputJob] ✅ Delivery successful', [
            'case_id' => $this->case->id,
            'handler' => $handlerType,
            'processing_time_ms' => $processingTimeMs,
            'attempt' => $this->attempts(),
            'timeline' => [
                'case_created_at' => $caseCreatedAt?->toIso8601String(),
                'output_sent_at' => now()->toIso8601String(),
                'total_latency_seconds' => $deliveryLatencySeconds,
                'enrichment_status' => $this->case->enrichment_status,
            ],
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
    public function failed(Throwable $exception): void
    {
        // Load case if not already loaded
        $case = $this->case ?? ServiceCase::find($this->caseId);

        Log::critical('[DeliverCaseOutputJob] Job permanently failed', [
            'case_id' => $this->caseId,
            'company_id' => $case?->company_id,
            'category_id' => $case?->category_id,
            'exception' => $exception->getMessage(),
            'total_attempts' => $this->attempts(),
        ]);

        if ($case) {
            $case->update([
                'output_status' => 'failed',
                'output_error' => 'Permanent failure after ' . $this->attempts() . ' attempts: ' . $exception->getMessage(),
            ]);

            // Send admin alert notification
            $this->notifyAdmins($case, $exception);
        }
    }

    /**
     * Send admin notifications on permanent failure.
     *
     * Uses company-specific alerts configuration if available,
     * otherwise falls back to global config/gateway.php settings.
     */
    private function notifyAdmins(ServiceCase $case, Throwable $exception): void
    {
        // Get alerts config - company-specific or global fallback
        $alertsConfig = $this->getAlertsConfig($case);

        // Check if alerts are enabled
        if (!($alertsConfig['enabled'] ?? true)) {
            Log::debug('[DeliverCaseOutputJob] Admin alerts disabled', [
                'case_id' => $case->id,
                'company_id' => $case->company_id,
            ]);
            return;
        }

        // Email notification
        $adminEmail = $alertsConfig['admin_email'] ?? null;
        if ($adminEmail) {
            try {
                // Support comma-separated emails
                $emails = array_filter(array_map('trim', explode(',', $adminEmail)));

                foreach ($emails as $email) {
                    Notification::route('mail', $email)
                        ->notify(new DeliveryFailedNotification($case, $exception, $this->attempts()));
                }

                Log::info('[DeliverCaseOutputJob] Admin notification sent', [
                    'case_id' => $case->id,
                    'recipients' => count($emails),
                    'source' => $alertsConfig['source'] ?? 'global',
                ]);
            } catch (Throwable $e) {
                Log::error('[DeliverCaseOutputJob] Failed to send admin notification', [
                    'case_id' => $case->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Slack webhook (optional)
        $slackWebhook = $alertsConfig['slack_webhook'] ?? null;
        if ($slackWebhook) {
            $this->sendSlackAlert($case, $exception, $slackWebhook);
        }
    }

    /**
     * Get alerts configuration for a case's company.
     *
     * Uses GatewayConfigService for layered config resolution:
     * 1. CompanyGatewayConfiguration (explicit)
     * 2. Global config/gateway.php (fallback)
     */
    private function getAlertsConfig(ServiceCase $case): array
    {
        try {
            if ($case->company) {
                $configService = app(GatewayConfigService::class);
                $config = $configService->getAlertsConfig($case->company);
                $config['source'] = 'company';
                return $config;
            }
        } catch (Throwable $e) {
            Log::warning('[DeliverCaseOutputJob] Failed to get company alerts config', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to global config
        return [
            'admin_email' => config('gateway.alerts.admin_email'),
            'enabled' => config('gateway.alerts.enabled', true),
            'slack_webhook' => config('gateway.alerts.slack_webhook'),
            'source' => 'global',
        ];
    }

    /**
     * Send Slack alert for critical failure
     */
    private function sendSlackAlert(ServiceCase $case, Throwable $exception, string $webhookUrl): void
    {
        try {
            $caseName = $case->formatted_id ?? "Case #{$case->id}";
            $companyName = $case->company?->name ?? 'Unknown';

            Http::timeout(ServiceGatewayConstants::SLACK_ALERT_TIMEOUT_SECONDS)->post($webhookUrl, [
                'text' => "🚨 *Delivery Failed*: {$caseName}",
                'attachments' => [
                    [
                        'color' => 'danger',
                        'fields' => [
                            ['title' => 'Company', 'value' => $companyName, 'short' => true],
                            ['title' => 'Category', 'value' => $case->category?->name ?? 'None', 'short' => true],
                            ['title' => 'Subject', 'value' => $case->subject ?? 'N/A', 'short' => false],
                            ['title' => 'Error', 'value' => substr($exception->getMessage(), 0, ServiceGatewayConstants::SLACK_ERROR_MAX_LENGTH), 'short' => false],
                        ],
                        'footer' => 'Service Gateway',
                        'ts' => now()->timestamp,
                    ],
                ],
            ]);

            Log::info('[DeliverCaseOutputJob] Slack alert sent', ['case_id' => $case->id]);
        } catch (Throwable $e) {
            Log::warning('[DeliverCaseOutputJob] Failed to send Slack alert', [
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Horizon tags for job monitoring
     */
    public function tags(): array
    {
        // Load case only if needed for tags (Horizon monitoring)
        $case = $this->case ?? ServiceCase::find($this->caseId);

        return [
            'service-case:' . $this->caseId,
            'company:' . ($case?->company_id ?? 'unknown'),
            'category:' . ($case?->category_id ?? 'none'),
        ];
    }
}
