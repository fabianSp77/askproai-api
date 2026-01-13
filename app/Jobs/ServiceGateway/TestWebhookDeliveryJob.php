<?php

declare(strict_types=1);

namespace App\Jobs\ServiceGateway;

use App\Models\ServiceOutputConfiguration;
use App\Services\ServiceGateway\ExchangeLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TestWebhookDeliveryJob
 *
 * Sends a test webhook to validate configuration before going live.
 * Used by admin panel "Test Webhook" button.
 *
 * Features:
 * - HMAC-SHA256 signature if secret configured
 * - Standard AskPro headers
 * - Test payload with clear indicators
 * - Logs results to database via ExchangeLogService
 * - Visible in Delivery Historie UI
 *
 * @package App\Jobs\ServiceGateway
 */
class TestWebhookDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;
    public int $timeout = 30;

    /**
     * Track when job was created for staleness detection.
     */
    public int $createdAt;

    public function __construct(
        public int $configurationId
    ) {
        $this->queue = config('gateway.output.queue', 'default');
        $this->createdAt = time();
    }

    /**
     * Handle job failure - notify via database so it's visible in UI.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[TestWebhookDeliveryJob] Job failed permanently', [
            'configuration_id' => $this->configurationId,
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
            'job_age_seconds' => time() - $this->createdAt,
        ]);

        // Create a failed log entry so it's visible in the UI
        try {
            $config = ServiceOutputConfiguration::find($this->configurationId);
            if ($config) {
                \App\Models\ServiceGatewayExchangeLog::create([
                    'event_id' => (string) \Illuminate\Support\Str::uuid(),
                    'direction' => 'outbound',
                    'endpoint' => $config->webhook_url ?? '[unknown]',
                    'http_method' => 'POST',
                    'request_body_redacted' => ['error' => 'Job failed before execution'],
                    'status_code' => null,
                    'duration_ms' => null,
                    'company_id' => $config->company_id,
                    'output_configuration_id' => $config->id,
                    'correlation_id' => 'test-failed-' . now()->format('YmdHis'),
                    'attempt_no' => 1,
                    'max_attempts' => 1,
                    'error_class' => get_class($exception),
                    'error_message' => substr($exception->getMessage(), 0, 500),
                    'is_test' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[TestWebhookDeliveryJob] Could not log failure to database', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute the job - send test webhook.
     */
    public function handle(ExchangeLogService $exchangeLogService): void
    {
        $jobAge = time() - $this->createdAt;

        // Warn if job is stale (>30 seconds old means queue processing is slow)
        if ($jobAge > 30) {
            Log::warning('[TestWebhookDeliveryJob] Stale job detected - queue may be backed up', [
                'configuration_id' => $this->configurationId,
                'job_age_seconds' => $jobAge,
            ]);
        }

        $config = ServiceOutputConfiguration::find($this->configurationId);

        if (!$config) {
            Log::error('[TestWebhookDeliveryJob] Configuration not found', [
                'configuration_id' => $this->configurationId,
                'job_age_seconds' => $jobAge,
            ]);
            return;
        }

        if (empty($config->webhook_url)) {
            Log::warning('[TestWebhookDeliveryJob] No webhook URL configured', [
                'configuration_id' => $this->configurationId,
                'config_name' => $config->name,
                'job_age_seconds' => $jobAge,
            ]);
            return;
        }

        Log::info('[TestWebhookDeliveryJob] Sending test webhook', [
            'configuration_id' => $config->id,
            'config_name' => $config->name,
            'webhook_url' => $config->webhook_url,
            'job_age_seconds' => $jobAge,
        ]);

        // Build test payload
        $payload = $this->buildTestPayload($config);

        // Build headers with HMAC if secret configured
        $headers = $this->buildHeaders($config, $payload);

        // Track execution time
        $startTime = microtime(true);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($config->webhook_url, $payload);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Parse response body if JSON
            $responseBody = null;
            if ($response->header('Content-Type') && str_contains($response->header('Content-Type'), 'application/json')) {
                $responseBody = $response->json();
            } else {
                $responseBody = ['raw_response' => substr($response->body(), 0, 1000)];
            }

            // Log to database - visible in UI
            $exchangeLogService->logOutbound(
                endpoint: $config->webhook_url,
                method: 'POST',
                requestBody: $payload,
                responseBody: $responseBody,
                statusCode: $response->status(),
                durationMs: $durationMs,
                callId: null,
                serviceCaseId: null,
                companyId: $config->company_id,
                correlationId: 'test-' . now()->format('YmdHis'),
                attemptNo: 1,
                maxAttempts: 1,
                errorClass: $response->successful() ? null : 'HttpError',
                errorMessage: $response->successful() ? null : 'HTTP ' . $response->status(),
                parentEventId: null,
                headers: $this->redactSensitiveHeaders($headers),
                isTest: true,
                outputConfigurationId: $config->id
            );

            if ($response->successful()) {
                Log::info('[TestWebhookDeliveryJob] Test webhook successful', [
                    'configuration_id' => $config->id,
                    'status' => $response->status(),
                    'duration_ms' => $durationMs,
                ]);
            } else {
                Log::error('[TestWebhookDeliveryJob] Test webhook failed', [
                    'configuration_id' => $config->id,
                    'status' => $response->status(),
                    'duration_ms' => $durationMs,
                ]);
            }

        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log exception to database - visible in UI
            $exchangeLogService->logOutbound(
                endpoint: $config->webhook_url,
                method: 'POST',
                requestBody: $payload,
                responseBody: null,
                statusCode: null,
                durationMs: $durationMs,
                callId: null,
                serviceCaseId: null,
                companyId: $config->company_id,
                correlationId: 'test-' . now()->format('YmdHis'),
                attemptNo: 1,
                maxAttempts: 1,
                errorClass: get_class($e),
                errorMessage: $e->getMessage(),
                parentEventId: null,
                headers: $this->redactSensitiveHeaders($headers),
                isTest: true,
                outputConfigurationId: $config->id
            );

            Log::error('[TestWebhookDeliveryJob] Test webhook exception', [
                'configuration_id' => $config->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'duration_ms' => $durationMs,
            ]);
        }
    }

    /**
     * Redact sensitive headers before logging.
     */
    private function redactSensitiveHeaders(array $headers): array
    {
        $redacted = [];
        foreach ($headers as $key => $value) {
            if (str_contains(strtolower($key), 'signature') || str_contains(strtolower($key), 'secret')) {
                $redacted[$key] = '[REDACTED]';
            } else {
                $redacted[$key] = $value;
            }
        }
        return $redacted;
    }

    /**
     * Build test payload.
     */
    private function buildTestPayload(ServiceOutputConfiguration $config): array
    {
        return [
            'fields' => [
                'summary' => '[TEST] AskProAI Webhook Verbindungstest',
                'description' => 'Dies ist ein Testaufruf von AskProAI Service Gateway. Wenn Sie diese Nachricht sehen, funktioniert die Webhook-Verbindung korrekt.',
                'issuetype' => ['name' => 'Task'],
                'priority' => ['name' => 'Low'],
                'labels' => ['askpro', 'test', 'webhook-test'],
                'customfield_10000' => 0,
            ],
            'meta' => [
                'source' => 'askpro_service_gateway',
                'version' => '1.2',
                'case_id' => 0,
                'company_id' => $config->company_id,
                'call_id' => null,
                'customer_id' => null,
                'created_at' => now()->toIso8601String(),
                'urgency' => 'low',
                'impact' => 'low',
                'is_test' => true,
            ],
            'enrichment' => [
                'status' => 'test',
                'enriched_at' => now()->toIso8601String(),
                'transcript_available' => false,
                'transcript_segment_count' => 0,
                'transcript_char_count' => 0,
                'audio_available' => false,
                'audio_url' => null,
                'audio_url_expires_at' => null,
            ],
            'test' => [
                'is_test' => true,
                'test_timestamp' => now()->toIso8601String(),
                'configuration_id' => $config->id,
                'configuration_name' => $config->name,
                'message' => 'This is a test webhook from AskProAI Service Gateway. If you receive this, the webhook connection is working correctly.',
            ],
        ];
    }

    /**
     * Build HTTP headers with HMAC signature.
     */
    private function buildHeaders(ServiceOutputConfiguration $config, array $payload): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'AskProServiceGateway/1.0',
            'X-AskPro-Event' => 'webhook.test',
            'X-AskPro-Company-Id' => (string) $config->company_id,
            'X-AskPro-Timestamp' => now()->toIso8601String(),
            'X-AskPro-Test' => 'true',
        ];

        // Add HMAC signature if secret configured
        // IMPORTANT: Use 'X-Signature' to match WebhookOutputHandler (not X-AskPro-Signature)
        if (!empty($config->webhook_secret)) {
            $jsonPayload = json_encode($payload);
            $signature = hash_hmac('sha256', $jsonPayload, $config->webhook_secret);
            $headers['X-Signature'] = $signature;

            Log::debug('[TestWebhookDeliveryJob] HMAC signature generated', [
                'configuration_id' => $config->id,
                'payload_size' => strlen($jsonPayload),
            ]);
        }

        // Merge custom headers
        if (!empty($config->webhook_headers) && is_array($config->webhook_headers)) {
            $headers = array_merge($headers, $config->webhook_headers);
        }

        return $headers;
    }
}
