<?php

namespace App\Services\Webhooks;

use App\Jobs\ProcessRetellCallEndedJob;
use App\Jobs\ProcessRetellCallStartedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RetellWebhookStrategy implements WebhookStrategy
{
    public function validateSignature(Request $request): bool
    {
        $signature = $request->header('x-retell-signature');
        if (!$signature) {
            return false;
        }
        
        $secret = config('services.retell.webhook_secret');
        if (!$secret) {
            Log::warning('Retell webhook secret not configured');
            return false;
        }
        
        // Retell signature format: v=timestamp,d=signature
        // Example: v=1751207567675,d=1468b13b1163a38b19b92fe712b2909113a4c85f4de1916be9e00ceee605bcde
        if (preg_match('/v=(\d+),d=([a-f0-9]+)/', $signature, $matches)) {
            $timestamp = $matches[1];
            $providedSignature = $matches[2];
            
            $payload = $request->getContent();
            // Retell signs with payload + timestamp (not timestamp + payload)
            $signedContent = $payload . $timestamp;
            $expectedSignature = hash_hmac('sha256', $signedContent, $secret);
            
            return hash_equals($expectedSignature, $providedSignature);
        }
        
        // Fallback to old format for backward compatibility
        $payload = $request->getContent();
        $timestamp = $request->header('x-retell-timestamp', '');
        $signedContent = $timestamp . $payload;
        $expectedSignature = hash_hmac('sha256', $signedContent, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    public function process(array $payload): void
    {
        $event = $payload['event'] ?? null;
        
        Log::info('Processing Retell webhook', [
            'event' => $event,
            'call_id' => $payload['call']['call_id'] ?? null
        ]);
        
        // Resolve company context
        $companyResolver = app(\App\Services\Webhook\WebhookCompanyResolver::class);
        $companyId = $companyResolver->resolveFromWebhook($payload);
        
        match($event) {
            'call_started' => $this->dispatchWithCompany(ProcessRetellCallStartedJob::class, $payload, $companyId),
            'call_inbound' => $this->processCallInbound($payload, $companyId),
            'call_ended' => $this->dispatchWithCompany(ProcessRetellCallEndedJob::class, $payload, $companyId),
            'call_analyzed' => $this->processCallAnalyzed($payload),
            default => Log::warning('Unknown Retell event', ['event' => $event])
        };
    }
    
    private function processCallAnalyzed(array $payload): void
    {
        // Future: Process call analysis results
        Log::info('Call analyzed event received', ['payload' => $payload]);
    }
    
    /**
     * Process inbound call webhook
     * This is sent when an incoming call is received
     */
    private function processCallInbound(array $payload, ?int $companyId): void
    {
        Log::info('Processing call_inbound event', [
            'payload' => $payload,
            'company_id' => $companyId
        ]);
        
        // Transform call_inbound to call_started format for existing job
        $transformedPayload = [
            'event' => 'call_started',
            'call' => [
                'call_id' => 'inbound_' . uniqid(),
                'call_type' => 'inbound',
                'from_number' => $payload['call_inbound']['from_number'] ?? null,
                'to_number' => $payload['call_inbound']['to_number'] ?? null,
                'direction' => 'inbound',
                'call_status' => 'ongoing',
                'agent_id' => $payload['call_inbound']['agent_id'] ?? null,
                'agent_version' => $payload['call_inbound']['agent_version'] ?? null,
                'start_timestamp' => time() * 1000,
                'metadata' => [
                    'original_event' => 'call_inbound'
                ]
            ]
        ];
        
        // Use existing call started job with transformed payload
        $this->dispatchWithCompany(ProcessRetellCallStartedJob::class, $transformedPayload, $companyId);
    }
    
    public function getSource(): string
    {
        return 'retell';
    }
    
    public function canHandle(Request $request): bool
    {
        // Retell webhooks have specific headers
        return $request->hasHeader('x-retell-signature') &&
               $request->hasHeader('x-retell-timestamp');
    }
    
    /**
     * Dispatch job with company context
     */
    private function dispatchWithCompany(string $jobClass, array $payload, ?int $companyId): void
    {
        $job = new $jobClass($payload);
        
        if ($companyId && method_exists($job, 'setCompanyId')) {
            $job->setCompanyId($companyId);
        }
        
        dispatch($job)->onQueue('webhooks');
    }
}