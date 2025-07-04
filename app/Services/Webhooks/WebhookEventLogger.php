<?php

namespace App\Services\Webhooks;

use App\Models\WebhookEvent;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookEventLogger
{
    /**
     * Log incoming webhook event
     */
    public function logIncoming(string $provider, array $payload, array $headers, ?string $correlationId = null): WebhookEvent
    {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $eventType = $this->extractEventType($provider, $payload);
        $eventId = $this->extractEventId($provider, $payload);
        $idempotencyKey = WebhookEvent::generateIdempotencyKey($provider, $payload);
        
        // Check for duplicate
        if (WebhookEvent::hasBeenProcessed($idempotencyKey)) {
            Log::info('Duplicate webhook detected', [
                'provider' => $provider,
                'event_type' => $eventType,
                'idempotency_key' => $idempotencyKey,
                'correlation_id' => $correlationId
            ]);
            
            return WebhookEvent::where('idempotency_key', $idempotencyKey)->first();
        }
        
        // Resolve company context
        $companyId = $this->resolveCompanyId($provider, $payload);
        
        // Create webhook event record
        $webhookEvent = WebhookEvent::create([
            'company_id' => $companyId,
            'provider' => $provider,
            'event_type' => $eventType,
            'event_id' => $eventId,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
            'headers' => $headers,
            'correlation_id' => $correlationId,
            'status' => WebhookEvent::STATUS_PENDING,
            'received_at' => now(),
            'retry_count' => 0
        ]);
        
        Log::info('Webhook event logged', [
            'webhook_event_id' => $webhookEvent->id,
            'provider' => $provider,
            'event_type' => $eventType,
            'correlation_id' => $correlationId,
            'company_id' => $companyId
        ]);
        
        return $webhookEvent;
    }
    
    /**
     * Log webhook processing result
     */
    public function logProcessed(WebhookEvent $webhookEvent, array $result, float $processingTime): void
    {
        $webhookEvent->markAsCompleted();
        
        Log::info('Webhook processed', [
            'webhook_event_id' => $webhookEvent->id,
            'provider' => $webhookEvent->provider,
            'event_type' => $webhookEvent->event_type,
            'processing_time_ms' => round($processingTime * 1000, 2),
            'result' => $result,
            'correlation_id' => $webhookEvent->correlation_id
        ]);
        
        // Log additional details for Stripe events
        if ($webhookEvent->provider === WebhookEvent::PROVIDER_STRIPE) {
            $this->logStripeDetails($webhookEvent, $result);
        }
    }
    
    /**
     * Log webhook processing error
     */
    public function logError(WebhookEvent $webhookEvent, \Exception $exception): void
    {
        $webhookEvent->markAsFailed($exception->getMessage());
        
        Log::error('Webhook processing failed', [
            'webhook_event_id' => $webhookEvent->id,
            'provider' => $webhookEvent->provider,
            'event_type' => $webhookEvent->event_type,
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'correlation_id' => $webhookEvent->correlation_id,
            'retry_count' => $webhookEvent->retry_count
        ]);
    }
    
    /**
     * Log webhook retry attempt
     */
    public function logRetry(WebhookEvent $webhookEvent, int $attempt, int $maxAttempts): void
    {
        Log::warning('Webhook retry attempt', [
            'webhook_event_id' => $webhookEvent->id,
            'provider' => $webhookEvent->provider,
            'event_type' => $webhookEvent->event_type,
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
            'will_retry' => $attempt < $maxAttempts,
            'correlation_id' => $webhookEvent->correlation_id
        ]);
    }
    
    /**
     * Extract event type from payload
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
     * Resolve company ID from webhook payload
     */
    protected function resolveCompanyId(string $provider, array $payload): ?int
    {
        // Check if company_id is directly in payload
        if (isset($payload['company_id'])) {
            return (int) $payload['company_id'];
        }
        
        // Check metadata for Stripe
        if ($provider === WebhookEvent::PROVIDER_STRIPE && isset($payload['data']['object']['metadata']['company_id'])) {
            return (int) $payload['data']['object']['metadata']['company_id'];
        }
        
        // For Stripe, also check customer
        if ($provider === WebhookEvent::PROVIDER_STRIPE && isset($payload['data']['object']['customer'])) {
            $company = Company::where('stripe_customer_id', $payload['data']['object']['customer'])->first();
            if ($company) {
                return $company->id;
            }
        }
        
        // For Retell, resolve from phone number
        if ($provider === WebhookEvent::PROVIDER_RETELL) {
            $phoneNumber = $payload['call']['to_number'] ?? $payload['to_number'] ?? null;
            if ($phoneNumber) {
                $resolver = app(\App\Services\PhoneNumberResolver::class);
                $result = $resolver->resolvePhoneNumber($phoneNumber);
                if ($result && isset($result['company_id'])) {
                    return $result['company_id'];
                }
            }
        }
        
        // Default to company 1 if we can't resolve
        Log::warning('Unable to resolve company ID for webhook', [
            'provider' => $provider,
            'event_type' => $this->extractEventType($provider, $payload)
        ]);
        
        return 1;
    }
    
    /**
     * Log additional Stripe-specific details
     */
    protected function logStripeDetails(WebhookEvent $webhookEvent, array $result): void
    {
        $payload = $webhookEvent->payload;
        $eventType = $webhookEvent->event_type;
        
        // Log invoice details
        if (str_starts_with($eventType, 'invoice.')) {
            $invoice = $payload['data']['object'] ?? [];
            Log::info('Stripe invoice event details', [
                'webhook_event_id' => $webhookEvent->id,
                'invoice_id' => $invoice['id'] ?? null,
                'customer_id' => $invoice['customer'] ?? null,
                'subscription_id' => $invoice['subscription'] ?? null,
                'amount_due' => isset($invoice['amount_due']) ? $invoice['amount_due'] / 100 : null,
                'currency' => $invoice['currency'] ?? null,
                'status' => $invoice['status'] ?? null,
                'billing_reason' => $invoice['billing_reason'] ?? null
            ]);
        }
        
        // Log subscription details
        if (str_starts_with($eventType, 'customer.subscription.')) {
            $subscription = $payload['data']['object'] ?? [];
            Log::info('Stripe subscription event details', [
                'webhook_event_id' => $webhookEvent->id,
                'subscription_id' => $subscription['id'] ?? null,
                'customer_id' => $subscription['customer'] ?? null,
                'status' => $subscription['status'] ?? null,
                'current_period_end' => isset($subscription['current_period_end']) 
                    ? date('Y-m-d H:i:s', $subscription['current_period_end']) 
                    : null,
                'cancel_at_period_end' => $subscription['cancel_at_period_end'] ?? false,
                'items_count' => isset($subscription['items']['data']) ? count($subscription['items']['data']) : 0
            ]);
        }
        
        // Log payment details
        if (str_starts_with($eventType, 'payment_intent.') || str_starts_with($eventType, 'charge.')) {
            $payment = $payload['data']['object'] ?? [];
            Log::info('Stripe payment event details', [
                'webhook_event_id' => $webhookEvent->id,
                'payment_id' => $payment['id'] ?? null,
                'customer_id' => $payment['customer'] ?? null,
                'amount' => isset($payment['amount']) ? $payment['amount'] / 100 : null,
                'currency' => $payment['currency'] ?? null,
                'status' => $payment['status'] ?? null,
                'payment_method' => $payment['payment_method'] ?? null
            ]);
        }
    }
    
    /**
     * Get webhook event statistics
     */
    public function getStatistics(string $provider = null, \DateTime $since = null): array
    {
        $query = WebhookEvent::query();
        
        if ($provider) {
            $query->where('provider', $provider);
        }
        
        if ($since) {
            $query->where('created_at', '>=', $since);
        }
        
        $stats = [
            'total' => $query->count(),
            'by_status' => $query->get()->groupBy('status')->map->count(),
            'by_provider' => $query->get()->groupBy('provider')->map->count(),
            'by_event_type' => $query->get()->groupBy('event_type')->map->count()->sortDesc()->take(10),
            'failed_count' => $query->where('status', WebhookEvent::STATUS_FAILED)->count(),
            'average_retry_count' => $query->where('retry_count', '>', 0)->avg('retry_count'),
            'duplicates_prevented' => $query->where('status', WebhookEvent::STATUS_DUPLICATE)->count()
        ];
        
        // Add success rate
        if ($stats['total'] > 0) {
            $stats['success_rate'] = round(
                ($stats['by_status'][WebhookEvent::STATUS_COMPLETED] ?? 0) / $stats['total'] * 100, 
                2
            ) . '%';
        }
        
        return $stats;
    }
}