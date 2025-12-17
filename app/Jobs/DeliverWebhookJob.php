<?php

namespace App\Jobs;

use App\Models\WebhookConfiguration;
use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * DeliverWebhookJob
 *
 * Queued job for delivering webhook payloads to external endpoints
 * with retry logic, timeout handling, and comprehensive logging.
 *
 * Features:
 * - HMAC signature authentication
 * - Configurable timeout and retries
 * - Detailed logging via WebhookLog model
 * - Idempotency key support
 * - Custom headers support
 */
class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout;

    /**
     * @param WebhookConfiguration $webhookConfig
     * @param string $event Event type (e.g., 'callback.created')
     * @param array $payload Event data
     * @param string $idempotencyKey Unique key to prevent duplicate processing
     */
    public function __construct(
        public WebhookConfiguration $webhookConfig,
        public string $event,
        public array $payload,
        public string $idempotencyKey
    ) {
        $this->tries = $webhookConfig->max_retry_attempts;
        $this->timeout = $webhookConfig->timeout_seconds + 5; // +5s for processing overhead
    }

    /**
     * Execute the webhook delivery.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        // Skip if webhook is inactive
        if (!$this->webhookConfig->is_active) {
            Log::info('[Webhook] Skipping delivery - webhook is inactive', [
                'webhook_id' => $this->webhookConfig->id,
                'event' => $this->event,
            ]);
            return;
        }

        // Check if this webhook is subscribed to the event
        if (!$this->webhookConfig->isSubscribedTo($this->event)) {
            Log::warning('[Webhook] Event not subscribed', [
                'webhook_id' => $this->webhookConfig->id,
                'event' => $this->event,
                'subscribed_events' => $this->webhookConfig->subscribed_events,
            ]);
            return;
        }

        // Prepare payload
        $fullPayload = [
            'event' => $this->event,
            'idempotency_key' => $this->idempotencyKey,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->payload,
        ];

        $jsonPayload = json_encode($fullPayload);
        $signature = $this->webhookConfig->generateSignature($jsonPayload);

        // Prepare headers
        $headers = array_merge(
            [
                'Content-Type' => 'application/json',
                'User-Agent' => 'AskProAI-Webhooks/1.0',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $this->event,
                'X-Webhook-Idempotency-Key' => $this->idempotencyKey,
                'X-Webhook-Delivery-Attempt' => $this->attempts(),
            ],
            $this->webhookConfig->headers ?? []
        );

        // Create webhook log entry
        $webhookLog = WebhookLog::create([
            'company_id' => $this->webhookConfig->company_id,
            'source' => 'outgoing',
            'endpoint' => $this->webhookConfig->url,
            'method' => 'POST',
            'headers' => $headers,
            'payload' => $fullPayload,
            'event_type' => $this->event,
            'event_id' => $this->idempotencyKey,
            'status' => 'pending',
        ]);

        try {
            // Send webhook request
            $response = Http::timeout($this->webhookConfig->timeout_seconds)
                ->withHeaders($headers)
                ->post($this->webhookConfig->url, $fullPayload);

            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                // Success (2xx status code)
                $webhookLog->markAsProcessed($response->status(), $processingTime);
                $this->webhookConfig->recordDelivery(true);

                Log::info('[Webhook] Delivery successful', [
                    'webhook_id' => $this->webhookConfig->id,
                    'event' => $this->event,
                    'status_code' => $response->status(),
                    'processing_time_ms' => $processingTime,
                ]);
            } else {
                // HTTP error (4xx, 5xx)
                $errorMessage = "HTTP {$response->status()}: " . $response->body();
                $webhookLog->markAsFailed($errorMessage, $response->status());
                $this->webhookConfig->recordDelivery(false);

                Log::error('[Webhook] Delivery failed (HTTP error)', [
                    'webhook_id' => $this->webhookConfig->id,
                    'event' => $this->event,
                    'status_code' => $response->status(),
                    'error' => $errorMessage,
                    'attempts' => $this->attempts(),
                ]);

                // Retry if possible
                if ($this->attempts() < $this->tries) {
                    $this->release(60); // Retry after 60 seconds
                }
            }
        } catch (Exception $e) {
            // Connection error, timeout, or other exception
            $processingTime = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            $webhookLog->markAsFailed($errorMessage, 0);
            $this->webhookConfig->recordDelivery(false);

            Log::error('[Webhook] Delivery exception', [
                'webhook_id' => $this->webhookConfig->id,
                'event' => $this->event,
                'error' => $errorMessage,
                'exception' => get_class($e),
                'attempts' => $this->attempts(),
                'processing_time_ms' => $processingTime,
            ]);

            // Retry if possible
            if ($this->attempts() < $this->tries) {
                $this->release(60); // Retry after 60 seconds
            } else {
                // Final failure - mark webhook as failed in log
                Log::critical('[Webhook] Final delivery failure after all retries', [
                    'webhook_id' => $this->webhookConfig->id,
                    'webhook_name' => $this->webhookConfig->name,
                    'event' => $this->event,
                    'total_attempts' => $this->attempts(),
                    'error' => $errorMessage,
                ]);
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::critical('[Webhook] Job permanently failed', [
            'webhook_id' => $this->webhookConfig->id,
            'event' => $this->event,
            'exception' => $exception->getMessage(),
        ]);
    }
}
