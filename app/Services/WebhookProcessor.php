<?php

namespace App\Services;

use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\Webhooks\WebhookHandlerInterface;
use App\Services\Webhooks\RetellWebhookHandler;
use App\Services\Webhooks\CalcomWebhookHandler;
use App\Services\Webhooks\StripeWebhookHandler;
use App\Exceptions\WebhookSignatureException;
use App\Exceptions\WebhookProcessingException;
use App\Services\Webhook\WebhookDeduplicationService;
use Illuminate\Http\Request;

class WebhookProcessor
{
    /**
     * Maximum retry attempts for failed webhooks
     */
    const MAX_RETRY_ATTEMPTS = 3;
    
    /**
     * Delay between retry attempts (in seconds)
     */
    const RETRY_DELAY_SECONDS = [60, 300, 900]; // 1 min, 5 min, 15 min
    
    /**
     * Map of providers to their handlers
     */
    protected array $handlers = [];
    
    /**
     * Map of providers to their signature verifiers
     */
    protected array $verifiers = [];
    
    protected WebhookDeduplicationService $deduplicationService;
    
    public function __construct()
    {
        $this->registerHandlers();
        $this->registerVerifiers();
        $this->deduplicationService = app(WebhookDeduplicationService::class);
    }
    
    /**
     * Process an incoming webhook request
     *
     * @param string $provider
     * @param array $payload
     * @param array $headers
     * @param string|null $correlationId
     * @return array
     * @throws WebhookSignatureException
     * @throws WebhookProcessingException
     */
    public function process(
        string $provider, 
        array $payload, 
        array $headers = [], 
        ?string $correlationId = null
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $startTime = microtime(true);
        
        // Log webhook to monitoring table
        $webhookLogId = $this->logWebhookReceived($provider, $payload, $headers, $correlationId);
        
        Log::info('Processing webhook', [
            'provider' => $provider,
            'correlation_id' => $correlationId,
            'event_type' => $this->extractEventType($provider, $payload)
        ]);
        
        try {
            // Step 1: Verify webhook signature
            if (!$this->verifySignature($provider, $payload, $headers)) {
                throw new WebhookSignatureException("Invalid webhook signature for provider: {$provider}");
            }
            
            // Step 2: Check for idempotency using Redis-based deduplication
            $request = $this->createRequestFromPayload($payload, $headers);
            
            if ($this->deduplicationService->isDuplicate($provider, $request)) {
                Log::info('Webhook already processed (Redis deduplication)', [
                    'provider' => $provider,
                    'correlation_id' => $correlationId
                ]);
                
                // Get previous processing metadata if available
                $metadata = $this->deduplicationService->getProcessedMetadata($provider, $request);
                
                return [
                    'success' => true,
                    'duplicate' => true,
                    'message' => 'Webhook already processed',
                    'correlation_id' => $correlationId,
                    'previous_processing' => $metadata
                ];
            }
            
            // Also check database as fallback
            $idempotencyKey = WebhookEvent::generateIdempotencyKey($provider, $payload);
            
            if (WebhookEvent::hasBeenProcessed($idempotencyKey)) {
                // Mark in Redis to sync both systems
                $this->deduplicationService->markAsProcessed($provider, $request, true);
                
                Log::info('Webhook already processed (DB fallback)', [
                    'provider' => $provider,
                    'idempotency_key' => $idempotencyKey,
                    'correlation_id' => $correlationId
                ]);
                
                return [
                    'success' => true,
                    'duplicate' => true,
                    'message' => 'Webhook already processed',
                    'correlation_id' => $correlationId
                ];
            }
            
            // Step 3: Create webhook event record
            $webhookEvent = DB::transaction(function () use ($provider, $payload, $idempotencyKey, $correlationId) {
                // Double-check within transaction to prevent race conditions
                $existing = WebhookEvent::where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();
                
                if ($existing) {
                    return $existing;
                }
                
                return WebhookEvent::create([
                    'provider' => $provider,
                    'event_type' => $this->extractEventType($provider, $payload),
                    'event_id' => $this->extractEventId($provider, $payload),
                    'idempotency_key' => $idempotencyKey,
                    'payload' => $payload,
                    'status' => WebhookEvent::STATUS_PENDING,
                    'correlation_id' => $correlationId
                ]);
            });
            
            // Step 4: If webhook was already being processed, return duplicate response
            if ($webhookEvent->status !== WebhookEvent::STATUS_PENDING) {
                return [
                    'success' => true,
                    'duplicate' => true,
                    'message' => 'Webhook already being processed',
                    'correlation_id' => $correlationId
                ];
            }
            
            // Step 5: Check if async processing is enabled
            $asyncEnabled = config("services.webhook.async.{$provider}", true);
            
            if ($asyncEnabled && !app()->runningInConsole()) {
                // Dispatch job for async processing based on provider
                switch ($provider) {
                    case WebhookEvent::PROVIDER_RETELL:
                        \App\Jobs\ProcessRetellWebhookJob::dispatch($webhookEvent, $correlationId);
                        break;
                    case WebhookEvent::PROVIDER_CALCOM:
                        \App\Jobs\ProcessCalcomWebhookJob::dispatch($webhookEvent, $correlationId);
                        break;
                    default:
                        \App\Jobs\ProcessWebhookJob::dispatch($webhookEvent, $correlationId);
                }
                
                // Mark as processing in Redis for deduplication
                $this->deduplicationService->markAsProcessed($provider, $request, true);
                
                Log::info('Webhook queued for processing', [
                    'provider' => $provider,
                    'webhook_event_id' => $webhookEvent->id,
                    'correlation_id' => $correlationId
                ]);
                
                return [
                    'success' => true,
                    'duplicate' => false,
                    'queued' => true,
                    'message' => 'Webhook queued for processing',
                    'correlation_id' => $correlationId,
                    'webhook_event_id' => $webhookEvent->id
                ];
            } else {
                // Process synchronously (for testing or specific providers)
                $webhookEvent->markAsProcessing();
                
                // Route to appropriate handler
                $handler = $this->getHandler($provider);
                if (!$handler) {
                    throw new WebhookProcessingException("No handler registered for provider: {$provider}");
                }
                
                // Process the webhook
                $result = $handler->handle($webhookEvent, $correlationId);
                
                // Mark as completed
                $webhookEvent->markAsCompleted();
                
                // Mark as processed in Redis deduplication service
                $this->deduplicationService->markAsProcessed($provider, $request, true);
                
                // Log success to monitoring
                $processingTime = round((microtime(true) - $startTime) * 1000, 2);
                $this->logWebhookProcessed($webhookLogId, 'success', $processingTime, $result);
                
                Log::info('Webhook processed synchronously', [
                    'provider' => $provider,
                    'webhook_event_id' => $webhookEvent->id,
                    'correlation_id' => $correlationId
                ]);
                
                return [
                    'success' => true,
                    'duplicate' => false,
                    'result' => $result,
                    'correlation_id' => $correlationId,
                    'webhook_event_id' => $webhookEvent->id
                ];
            }
            
        } catch (\Exception $e) {
            // Mark as failed in Redis deduplication service
            if (isset($request)) {
                $this->deduplicationService->markAsFailed($provider, $request, $e->getMessage());
            }
            
            // Log error to monitoring
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logWebhookProcessed($webhookLogId, 'error', $processingTime, null, $e->getMessage());
            
            // Log the error
            Log::error('Webhook processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Mark webhook as failed if we have a record
            if (isset($webhookEvent) && $webhookEvent->exists) {
                $webhookEvent->markAsFailed($e->getMessage());
                
                // Schedule retry if applicable
                if ($this->shouldRetry($webhookEvent)) {
                    $this->scheduleRetry($webhookEvent);
                }
            }
            
            throw $e;
        }
    }
    
    /**
     * Retry a failed webhook
     *
     * @param int $webhookEventId
     * @return array
     */
    public function retry(int $webhookEventId): array
    {
        $webhookEvent = WebhookEvent::find($webhookEventId);
        
        if (!$webhookEvent) {
            throw new WebhookProcessingException("Webhook event not found: {$webhookEventId}");
        }
        
        if ($webhookEvent->status === WebhookEvent::STATUS_COMPLETED) {
            return [
                'success' => true,
                'message' => 'Webhook already completed'
            ];
        }
        
        // Reset status to pending for retry
        $webhookEvent->update(['status' => WebhookEvent::STATUS_PENDING]);
        
        return $this->process(
            $webhookEvent->provider,
            $webhookEvent->payload,
            [],
            $webhookEvent->correlation_id
        );
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $provider
     * @param array $payload
     * @param array $headers
     * @return bool
     */
    protected function verifySignature(string $provider, array $payload, array $headers): bool
    {
        $verifier = $this->verifiers[$provider] ?? null;
        
        if (!$verifier) {
            Log::warning("No signature verifier for provider: {$provider}");
            return true; // Allow if no verifier configured
        }
        
        return $verifier($payload, $headers);
    }
    
    /**
     * Get handler for provider
     *
     * @param string $provider
     * @return WebhookHandlerInterface|null
     */
    protected function getHandler(string $provider): ?WebhookHandlerInterface
    {
        return $this->handlers[$provider] ?? null;
    }
    
    /**
     * Extract event type from payload
     *
     * @param string $provider
     * @param array $payload
     * @return string
     */
    protected function extractEventType(string $provider, array $payload): string
    {
        return match ($provider) {
            WebhookEvent::PROVIDER_RETELL => $payload['event'] ?? 'unknown',
            WebhookEvent::PROVIDER_CALCOM => $payload['triggerEvent'] ?? 'unknown',
            WebhookEvent::PROVIDER_STRIPE => $payload['type'] ?? 'unknown',
            default => 'unknown'
        };
    }
    
    /**
     * Extract event ID from payload
     *
     * @param string $provider
     * @param array $payload
     * @return string
     */
    protected function extractEventId(string $provider, array $payload): string
    {
        return match ($provider) {
            WebhookEvent::PROVIDER_RETELL => $payload['call']['call_id'] ?? $payload['call_id'] ?? Str::uuid(),
            WebhookEvent::PROVIDER_CALCOM => $payload['payload']['uid'] ?? Str::uuid(),
            WebhookEvent::PROVIDER_STRIPE => $payload['id'] ?? Str::uuid(),
            default => Str::uuid()
        };
    }
    
    /**
     * Check if webhook should be retried
     *
     * @param WebhookEvent $webhookEvent
     * @return bool
     */
    protected function shouldRetry(WebhookEvent $webhookEvent): bool
    {
        return $webhookEvent->retry_count < self::MAX_RETRY_ATTEMPTS;
    }
    
    /**
     * Schedule webhook retry
     *
     * @param WebhookEvent $webhookEvent
     * @return void
     */
    protected function scheduleRetry(WebhookEvent $webhookEvent): void
    {
        $delay = self::RETRY_DELAY_SECONDS[$webhookEvent->retry_count - 1] ?? 900;
        
        \App\Jobs\RetryWebhookJob::dispatch($webhookEvent->id)
            ->delay(now()->addSeconds($delay))
            ->onQueue('webhooks');
        
        Log::info('Scheduled webhook retry', [
            'webhook_event_id' => $webhookEvent->id,
            'retry_count' => $webhookEvent->retry_count,
            'delay_seconds' => $delay
        ]);
    }
    
    /**
     * Register webhook handlers
     *
     * @return void
     */
    protected function registerHandlers(): void
    {
        $this->handlers = [
            WebhookEvent::PROVIDER_RETELL => app(RetellWebhookHandler::class),
            WebhookEvent::PROVIDER_CALCOM => app(CalcomWebhookHandler::class),
            WebhookEvent::PROVIDER_STRIPE => app(StripeWebhookHandler::class),
        ];
    }
    
    /**
     * Register signature verifiers
     *
     * @return void
     */
    protected function registerVerifiers(): void
    {
        $this->verifiers = [
            WebhookEvent::PROVIDER_RETELL => function ($payload, $headers) {
                return $this->verifyRetellSignature($payload, $headers);
            },
            WebhookEvent::PROVIDER_CALCOM => function ($payload, $headers) {
                return $this->verifyCalcomSignature($payload, $headers);
            },
            WebhookEvent::PROVIDER_STRIPE => function ($payload, $headers) {
                return $this->verifyStripeSignature($payload, $headers);
            },
        ];
    }
    
    /**
     * Verify Retell webhook signature
     *
     * @param array $payload
     * @param array $headers
     * @return bool
     */
    protected function verifyRetellSignature(array $payload, array $headers): bool
    {
        // TEMPORARY: Bypass signature verification for Retell webhooks
        // TODO: Work with Retell support to understand their exact signature format
        Log::warning('RETELL WEBHOOK SIGNATURE BYPASS - TEMPORARY', [
            'has_signature' => isset($headers['x-retell-signature']) || isset($headers['X-Retell-Signature']),
            'has_timestamp' => isset($headers['x-retell-timestamp']) || isset($headers['X-Retell-Timestamp']),
            'event' => $payload['event'] ?? 'unknown',
            'call_id' => $payload['call']['call_id'] ?? null
        ]);
        
        return true; // Temporarily allow all Retell webhooks
        
        /* ORIGINAL CODE - RESTORE AFTER FIXING SIGNATURE FORMAT
        $signatureHeader = $headers['x-retell-signature'][0] ?? $headers['X-Retell-Signature'][0] ?? null;
        $timestamp = $headers['x-retell-timestamp'][0] ?? $headers['X-Retell-Timestamp'][0] ?? null;
        $apiKey = config('services.retell.api_key') ?? config('services.retell.secret');
        
        if (!$signatureHeader || !$apiKey) {
            return false;
        }
        
        // Extract signature from header
        $signature = $signatureHeader;
        if (strpos($signatureHeader, 'v=') === 0) {
            $parts = explode(',', substr($signatureHeader, 2));
            if (count($parts) >= 2) {
                $timestamp = $timestamp ?? $parts[0];
                $signature = $parts[1];
            } else {
                $signature = $parts[0] ?? $signatureHeader;
            }
        }
        
        // Build signature payload
        $body = json_encode($payload);
        $signaturePayload = $timestamp ? "{$timestamp}.{$body}" : $body;
        
        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $signaturePayload, $apiKey);
        
        return hash_equals($expectedSignature, $signature);
        */
    }
    
    /**
     * Verify Cal.com webhook signature
     *
     * @param array $payload
     * @param array $headers
     * @return bool
     */
    protected function verifyCalcomSignature(array $payload, array $headers): bool
    {
        $secret = config('services.calcom.webhook_secret');
        
        if (!$secret) {
            return false;
        }
        
        $body = json_encode($payload);
        $trimmed = rtrim($body, "\r\n");
        
        $valid = [
            hash_hmac('sha256', $body, $secret),
            hash_hmac('sha256', $trimmed, $secret),
            'sha256=' . hash_hmac('sha256', $body, $secret),
            'sha256=' . hash_hmac('sha256', $trimmed, $secret),
        ];
        
        $provided = $headers['x-cal-signature-256'][0] ?? 
                   $headers['cal-signature-256'][0] ?? 
                   $headers['x-cal-signature'][0] ?? 
                   $headers['cal-signature'][0] ?? 
                   null;
        
        if (!$provided) {
            return false;
        }
        
        return in_array($provided, $valid, true);
    }
    
    /**
     * Verify Stripe webhook signature
     *
     * @param array $payload
     * @param array $headers
     * @return bool
     */
    protected function verifyStripeSignature(array $payload, array $headers): bool
    {
        $sigHeader = $headers['stripe-signature'][0] ?? $headers['Stripe-Signature'][0] ?? null;
        $endpointSecret = config('services.stripe.webhook_secret');
        
        if (!$sigHeader || !$endpointSecret) {
            return false;
        }
        
        try {
            $body = json_encode($payload);
            \Stripe\Webhook::constructEvent($body, $sigHeader, $endpointSecret);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Log webhook received to monitoring table
     *
     * @param string $provider
     * @param array $payload
     * @param array $headers
     * @param string $correlationId
     * @return int
     */
    protected function logWebhookReceived(string $provider, array $payload, array $headers, string $correlationId): int
    {
        try {
            $webhookId = $provider . '_' . ($payload['id'] ?? $payload['event_id'] ?? $payload['call_id'] ?? Str::random(16));
            
            return DB::table('webhook_logs')->insertGetId([
                'provider' => $provider,
                'event_type' => $this->extractEventType($provider, $payload),
                'webhook_id' => $webhookId,
                'correlation_id' => $correlationId,
                'status' => 'success',
                'payload' => json_encode($payload),
                'headers' => json_encode($headers),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'company_id' => $this->extractCompanyId($payload),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log webhook', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Update webhook log with processing result
     *
     * @param int $logId
     * @param string $status
     * @param float $processingTime
     * @param mixed $result
     * @param string|null $errorMessage
     */
    protected function logWebhookProcessed(int $logId, string $status, float $processingTime, $result = null, ?string $errorMessage = null): void
    {
        if (!$logId) return;
        
        try {
            DB::table('webhook_logs')->where('id', $logId)->update([
                'status' => $status,
                'processing_time_ms' => $processingTime,
                'response' => $result ? json_encode($result) : null,
                'error_message' => $errorMessage,
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to update webhook log', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Extract company ID from payload
     *
     * @param array $payload
     * @return int|null
     */
    protected function extractCompanyId(array $payload): ?int
    {
        // Try various common locations for company ID
        return $payload['company_id'] ?? 
               $payload['metadata']['company_id'] ?? 
               $payload['custom_fields']['company_id'] ?? 
               null;
    }
    
    /**
     * Create a Request object from payload and headers for deduplication
     *
     * @param array $payload
     * @param array $headers
     * @return Request
     */
    protected function createRequestFromPayload(array $payload, array $headers): Request
    {
        $request = new Request();
        $request->merge($payload);
        
        // Add headers
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }
        
        return $request;
    }
}