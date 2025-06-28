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