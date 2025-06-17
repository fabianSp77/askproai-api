<?php

namespace App\Services\Webhooks;

use App\Jobs\ProcessStripeWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookStrategy implements WebhookStrategy
{
    public function validateSignature(Request $request): bool
    {
        $signature = $request->header('Stripe-Signature');
        if (!$signature) {
            return false;
        }
        
        $secret = config('services.stripe.webhook_secret');
        if (!$secret) {
            Log::warning('Stripe webhook secret not configured');
            return false;
        }
        
        try {
            Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $secret
            );
            return true;
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe signature verification failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function process(array $payload): void
    {
        $event = $payload['type'] ?? null;
        
        Log::info('Processing Stripe webhook', [
            'event' => $event,
            'object_id' => $payload['data']['object']['id'] ?? null
        ]);
        
        ProcessStripeWebhookJob::dispatch($payload)
            ->onQueue('webhooks');
    }
    
    public function getSource(): string
    {
        return 'stripe';
    }
    
    public function canHandle(Request $request): bool
    {
        // Stripe webhooks have specific headers
        return $request->hasHeader('Stripe-Signature');
    }
}