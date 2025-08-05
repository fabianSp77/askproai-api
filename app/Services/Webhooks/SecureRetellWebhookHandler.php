<?php

namespace App\Services\Webhooks;

use App\Models\Call;
use App\Models\Company;
use App\Models\Branch;
use App\Models\WebhookEvent;
use App\Services\SecurePhoneNumberResolver;
use App\Jobs\SecureProcessRetellCallEndedJob;
use App\Jobs\SecureProcessRetellCallStartedJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SECURE VERSION: Retell Webhook Handler with proper tenant isolation
 * 
 * This handler processes Retell.ai webhooks while maintaining strict
 * tenant boundaries. It validates company context before any operations.
 */
class SecureRetellWebhookHandler
{
    protected SecurePhoneNumberResolver $phoneResolver;
    
    public function __construct(SecurePhoneNumberResolver $phoneResolver)
    {
        $this->phoneResolver = $phoneResolver;
    }
    
    /**
     * Handle incoming webhook with security validation
     */
    public function handle(array $payload): array
    {
        // Generate idempotency key for duplicate prevention
        $idempotencyKey = $this->generateIdempotencyKey($payload);
        
        // Check for duplicate processing
        if ($this->isDuplicateWebhook($idempotencyKey)) {
            Log::info('SecureRetellWebhookHandler: Duplicate webhook ignored', [
                'idempotency_key' => $idempotencyKey
            ]);
            return ['status' => 'duplicate', 'message' => 'Already processed'];
        }
        
        // Record webhook event
        $webhookEvent = $this->recordWebhookEvent($payload, $idempotencyKey);
        
        try {
            // Process based on event type
            $eventType = $payload['event'] ?? 'unknown';
            $callData = $payload['call'] ?? $payload;
            
            // Resolve company context securely
            $context = $this->phoneResolver->resolveWebhookData($callData);
            
            if (!$context || !$context['company_id']) {
                Log::warning('SecureRetellWebhookHandler: Cannot resolve company context', [
                    'event' => $eventType,
                    'call_id' => $callData['call_id'] ?? 'unknown'
                ]);
                
                // Update webhook status
                $this->updateWebhookStatus($webhookEvent, 'failed_no_context');
                
                return ['status' => 'error', 'message' => 'Cannot resolve company context'];
            }
            
            // Audit webhook processing
            $this->auditWebhookProcessing($eventType, $context);
            
            switch ($eventType) {
                case 'call_started':
                    $this->handleCallStarted($callData, $idempotencyKey);
                    break;
                    
                case 'call_ended':
                    $this->handleCallEnded($callData, $idempotencyKey);
                    break;
                    
                case 'call_analyzed':
                    $this->handleCallAnalyzed($callData, $context);
                    break;
                    
                default:
                    Log::warning('SecureRetellWebhookHandler: Unknown event type', [
                        'event' => $eventType
                    ]);
                    $this->updateWebhookStatus($webhookEvent, 'unknown_event');
                    return ['status' => 'error', 'message' => 'Unknown event type'];
            }
            
            return ['status' => 'success', 'message' => 'Webhook processed'];
            
        } catch (\Exception $e) {
            Log::error('SecureRetellWebhookHandler: Processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->updateWebhookStatus($webhookEvent, 'failed_exception');
            
            throw $e;
        }
    }
    
    /**
     * Handle call started event
     */
    protected function handleCallStarted(array $callData, string $idempotencyKey): void
    {
        // Dispatch secure job
        dispatch(new SecureProcessRetellCallStartedJob($callData, $idempotencyKey))
            ->onQueue('webhooks');
    }
    
    /**
     * Handle call ended event
     */
    protected function handleCallEnded(array $callData, string $idempotencyKey): void
    {
        // Dispatch secure job
        dispatch(new SecureProcessRetellCallEndedJob($callData, $idempotencyKey))
            ->onQueue('webhooks');
    }
    
    /**
     * Handle call analyzed event (synchronous processing)
     */
    protected function handleCallAnalyzed(array $callData, array $context): void
    {
        $callId = $callData['call_id'] ?? null;
        
        if (!$callId) {
            Log::error('SecureRetellWebhookHandler: No call_id in analyzed event');
            return;
        }
        
        // Find call within company context
        $call = Call::where('retell_call_id', $callId)
            ->where('company_id', $context['company_id'])
            ->first();
            
        if (!$call) {
            Log::warning('SecureRetellWebhookHandler: Call not found for analysis', [
                'call_id' => $callId,
                'company_id' => $context['company_id']
            ]);
            return;
        }
        
        // Update call with analysis data
        if (isset($callData['call_analysis'])) {
            $call->analysis = $callData['call_analysis'];
            $call->summary = $callData['call_analysis']['call_summary'] ?? null;
            $call->sentiment = $callData['call_analysis']['user_sentiment'] ?? null;
            
            // Extract custom analysis data if available
            if (isset($callData['custom_analysis_data'])) {
                $call->custom_analysis_data = $callData['custom_analysis_data'];
            }
            
            $call->save();
            
            Log::info('SecureRetellWebhookHandler: Call analysis updated', [
                'call_id' => $call->id,
                'has_summary' => !empty($call->summary)
            ]);
        }
    }
    
    /**
     * Generate idempotency key for webhook
     */
    protected function generateIdempotencyKey(array $payload): string
    {
        $eventType = $payload['event'] ?? 'unknown';
        $callId = $payload['call']['call_id'] ?? $payload['call_id'] ?? '';
        $timestamp = $payload['timestamp'] ?? time();
        
        return md5("{$eventType}:{$callId}:{$timestamp}");
    }
    
    /**
     * Check if webhook is duplicate
     */
    protected function isDuplicateWebhook(string $idempotencyKey): bool
    {
        return WebhookEvent::where('idempotency_key', $idempotencyKey)
            ->where('status', '!=', 'failed')
            ->exists();
    }
    
    /**
     * Record webhook event
     */
    protected function recordWebhookEvent(array $payload, string $idempotencyKey): WebhookEvent
    {
        return WebhookEvent::create([
            'type' => 'retell',
            'event' => $payload['event'] ?? 'unknown',
            'payload' => $payload,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
            'metadata' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String()
            ]
        ]);
    }
    
    /**
     * Update webhook event status
     */
    protected function updateWebhookStatus(WebhookEvent $webhookEvent, string $status): void
    {
        $webhookEvent->update([
            'status' => $status,
            'processed_at' => now()
        ]);
    }
    
    /**
     * Audit webhook processing for security
     */
    protected function auditWebhookProcessing(string $eventType, array $context): void
    {
        if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
            DB::table('security_audit_logs')->insert([
                'event_type' => 'webhook_processed',
                'company_id' => $context['company_id'] ?? null,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'url' => request()->fullUrl() ?? 'webhook',
                'metadata' => json_encode([
                    'webhook_type' => 'retell',
                    'event' => $eventType,
                    'resolution_method' => $context['resolution_method'] ?? 'unknown',
                    'confidence' => $context['confidence'] ?? 0,
                    'branch_id' => $context['branch_id'] ?? null
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}