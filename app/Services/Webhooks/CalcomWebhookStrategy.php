<?php

namespace App\Services\Webhooks;

use App\Jobs\ProcessCalcomWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalcomWebhookStrategy implements WebhookStrategy
{
    public function validateSignature(Request $request): bool
    {
        $signature = $request->header('X-Cal-Signature-256');
        if (!$signature) {
            return false;
        }
        
        $secret = config('services.calcom.webhook_secret');
        if (!$secret) {
            Log::warning('Cal.com webhook secret not configured');
            return false;
        }
        
        $payload = $request->getContent();
        $calculated = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($calculated, $signature);
    }
    
    public function process(array $payload): void
    {
        Log::info('Processing Cal.com webhook', [
            'event' => $payload['triggerEvent'] ?? 'unknown',
            'booking_id' => $payload['payload']['id'] ?? null
        ]);
        
        ProcessCalcomWebhookJob::dispatch($payload)
            ->onQueue('webhooks');
    }
    
    public function getSource(): string
    {
        return 'calcom';
    }
    
    public function canHandle(Request $request): bool
    {
        // Cal.com webhooks have specific headers
        return $request->hasHeader('X-Cal-Signature-256') ||
               $request->hasHeader('X-Webhook-Id');
    }
}