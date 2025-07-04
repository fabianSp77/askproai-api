<?php

namespace App\Http\Controllers;

use App\Services\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnifiedWebhookController extends Controller
{
    private WebhookProcessor $processor;
    
    public function __construct(WebhookProcessor $processor)
    {
        $this->processor = $processor;
    }
    
    /**
     * Handle incoming webhook from any source
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            // Detect provider from request path or headers
            $provider = $this->detectProvider($request);
            
            if (!$provider) {
                return response()->json([
                    'error' => 'Unable to detect webhook provider',
                    'message' => 'Please use provider-specific endpoints like /webhooks/retell'
                ], 400);
            }
            
            // Generate correlation ID for tracking
            $correlationId = \Illuminate\Support\Str::uuid()->toString();
            
            // Log webhook receipt
            \Log::info('Webhook received', [
                'provider' => $provider,
                'event' => $request->input('event') ?? $request->input('type') ?? 'unknown',
                'correlation_id' => $correlationId,
                'ip' => $request->ip()
            ]);
            
            // Check for real-time webhooks that need immediate response
            if ($this->requiresSynchronousProcessing($provider, $request)) {
                // Process synchronously for real-time requirements
                $result = $this->processor->process(
                    $provider,
                    $request->all(),
                    $request->headers->all(),
                    $correlationId
                );
                return response()->json($result, 200);
            }
            
            // Generate idempotency key and event ID
            $payload = $request->all();
            $idempotencyKey = \App\Models\WebhookEvent::generateIdempotencyKey($provider, $payload);
            $eventId = $this->extractEventId($provider, $payload);
            
            // Resolve company context for webhook
            $companyId = $this->resolveCompanyId($provider, $payload);
            
            // Create webhook event record for async processing
            $webhookEvent = \App\Models\WebhookEvent::create([
                'provider' => $provider,
                'event_type' => $request->input('event') ?? $request->input('type') ?? 'unknown',
                'event_id' => $eventId,
                'idempotency_key' => $idempotencyKey,
                'payload' => $payload,
                'headers' => $request->headers->all(),
                'correlation_id' => $correlationId,
                'status' => \App\Models\WebhookEvent::STATUS_PENDING,
                'received_at' => now(),
                'company_id' => $companyId,
            ]);
            
            // Dispatch to appropriate queue based on provider and priority
            $job = new \App\Jobs\ProcessWebhookJob($webhookEvent, $correlationId);
            
            // Determine queue priority
            $queue = $this->determineQueue($provider, $webhookEvent->event_type);
            
            // Dispatch with timeout protection
            dispatch($job)
                ->onQueue($queue)
                ->afterCommit(); // Ensure database transaction completes first
            
            // Return immediate success response to prevent timeout
            return response()->json([
                'success' => true,
                'message' => 'Webhook accepted for processing',
                'correlation_id' => $correlationId,
                'webhook_id' => $webhookEvent->id
            ], 202); // 202 Accepted
            
        } catch (\App\Exceptions\WebhookSignatureException $e) {
            return response()->json([
                'error' => 'Invalid webhook signature',
                'message' => $e->getMessage(),
                'provider' => $provider ?? 'unknown'
            ], 401);
        } catch (\App\Exceptions\WebhookException $e) {
            // WebhookException handles its own response
            throw $e;
        } catch (\Exception $e) {
            // Log unexpected errors
            \Log::error('Unexpected webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId ?? 'unknown'
            ]);
            
            return response()->json([
                'error' => 'Internal server error',
                'message' => app()->environment('production') 
                    ? 'An error occurred processing the webhook' 
                    : $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Detect webhook provider from request
     */
    private function detectProvider(Request $request): ?string
    {
        // Check URL path
        if (str_contains($request->path(), 'retell')) {
            return 'retell';
        }
        if (str_contains($request->path(), 'calcom')) {
            return 'calcom';
        }
        if (str_contains($request->path(), 'stripe')) {
            return 'stripe';
        }
        
        // Check headers for provider signatures
        if ($request->hasHeader('x-retell-signature')) {
            return 'retell';
        }
        if ($request->hasHeader('x-cal-signature-256') || $request->hasHeader('cal-signature-256')) {
            return 'calcom';
        }
        if ($request->hasHeader('stripe-signature')) {
            return 'stripe';
        }
        
        // Check payload for provider-specific fields
        $payload = $request->all();
        if (isset($payload['event']) && isset($payload['call'])) {
            return 'retell';
        }
        if (isset($payload['triggerEvent'])) {
            return 'calcom';
        }
        if (isset($payload['type']) && str_starts_with($payload['id'] ?? '', 'evt_')) {
            return 'stripe';
        }
        
        return null;
    }
    
    /**
     * Check if webhook requires synchronous processing
     */
    private function requiresSynchronousProcessing(string $provider, Request $request): bool
    {
        // Retell inbound calls need immediate response for real-time interaction
        if ($provider === 'retell') {
            $eventType = $request->input('event');
            if (in_array($eventType, ['call_inbound', 'call_analyzed'])) {
                return true;
            }
        }
        
        // All other webhooks can be processed asynchronously
        return false;
    }
    
    /**
     * Determine queue priority based on provider and event type
     */
    private function determineQueue(string $provider, string $eventType): string
    {
        // High priority: Critical business events
        if ($provider === 'retell' && in_array($eventType, ['call_ended', 'call_failed'])) {
            return 'webhooks-high';
        }
        
        if ($provider === 'calcom' && in_array($eventType, ['booking.created', 'booking.cancelled', 'booking.rescheduled'])) {
            return 'webhooks-high';
        }
        
        // Medium priority: Payment events
        if ($provider === 'stripe') {
            return 'webhooks-medium';
        }
        
        // Low priority: Everything else
        return 'webhooks-low';
    }
    
    /**
     * Extract event ID from payload
     */
    private function extractEventId(string $provider, array $payload): string
    {
        return match ($provider) {
            'retell' => $payload['call']['call_id'] ?? $payload['call_id'] ?? \Illuminate\Support\Str::uuid(),
            'calcom' => $payload['payload']['uid'] ?? \Illuminate\Support\Str::uuid(),
            'stripe' => $payload['id'] ?? \Illuminate\Support\Str::uuid(),
            default => \Illuminate\Support\Str::uuid()
        };
    }
    
    /**
     * Resolve company ID from webhook payload
     */
    private function resolveCompanyId(string $provider, array $payload): ?int
    {
        try {
            // First check if company_id is directly in payload
            if (isset($payload['company_id'])) {
                return (int) $payload['company_id'];
            }
            
            // Check metadata
            if (isset($payload['metadata']['company_id'])) {
                return (int) $payload['metadata']['company_id'];
            }
            
            // For Retell, try to resolve from phone number
            if ($provider === 'retell') {
                $phoneNumber = null;
                
                // Check different payload structures
                if (isset($payload['call']['to_number'])) {
                    $phoneNumber = $payload['call']['to_number'];
                } elseif (isset($payload['call_inbound']['to_number'])) {
                    $phoneNumber = $payload['call_inbound']['to_number'];
                } elseif (isset($payload['to_number'])) {
                    $phoneNumber = $payload['to_number'];
                }
                
                if ($phoneNumber) {
                    // Use service to resolve company from phone number
                    $resolver = app(\App\Services\PhoneNumberResolver::class);
                    $result = $resolver->resolvePhoneNumber($phoneNumber);
                    
                    if ($result && isset($result['company_id'])) {
                        return $result['company_id'];
                    }
                }
            }
            
            // For Cal.com, try to resolve from event type
            if ($provider === 'calcom' && isset($payload['payload']['eventTypeId'])) {
                $eventType = \App\Models\CalcomEventType::where('calcom_id', $payload['payload']['eventTypeId'])->first();
                if ($eventType) {
                    return $eventType->company_id;
                }
            }
            
            // Default to company 1 if we can't resolve
            \Log::warning('Unable to resolve company ID for webhook', [
                'provider' => $provider,
                'event_type' => $payload['event'] ?? 'unknown'
            ]);
            
            return 1; // Default company
            
        } catch (\Exception $e) {
            \Log::error('Error resolving company ID for webhook', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            return 1; // Default company
        }
    }
    
    /**
     * Health check endpoint for webhook processing
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'providers' => ['retell', 'calcom', 'stripe'],
            'features' => [
                'deduplication' => true,
                'retry_logic' => true,
                'database_logging' => true,
                'mcp_architecture' => true,
                'async_processing' => true,
                'circuit_breaker' => true
            ],
            'timestamp' => now()->toIso8601String()
        ]);
    }
}