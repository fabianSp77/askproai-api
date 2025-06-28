<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\WebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;
    
    /**
     * The number of seconds the job can run before timing out
     */
    public int $timeout = 120;
    
    /**
     * The number of seconds to wait before retrying the job
     */
    public int $backoff = 30;
    
    /**
     * Create a new job instance
     */
    public function __construct(
        public WebhookEvent $webhookEvent,
        public string $correlationId
    ) {
        // Set queue based on provider priority
        $this->onQueue($this->getQueueName());
    }
    
    /**
     * Execute the job
     */
    public function handle(WebhookProcessor $processor): void
    {
        $startTime = microtime(true);
        
        Log::info('Processing webhook job', [
            'webhook_event_id' => $this->webhookEvent->id,
            'provider' => $this->webhookEvent->provider,
            'event_type' => $this->webhookEvent->event_type,
            'correlation_id' => $this->correlationId,
            'attempt' => $this->attempts(),
        ]);
        
        try {
            // Mark as processing
            $this->webhookEvent->markAsProcessing();
            
            // Process the webhook using the processor service
            // The processor will handle routing to the appropriate handler
            $result = $processor->processWebhookEvent($this->webhookEvent, $this->correlationId);
            
            // Mark as completed
            $this->webhookEvent->markAsCompleted();
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Webhook processed successfully', [
                'webhook_event_id' => $this->webhookEvent->id,
                'correlation_id' => $this->correlationId,
                'processing_time_ms' => $processingTime,
                'result' => $result,
            ]);
            
        } catch (\Exception $e) {
            $this->handleFailure($e);
        }
    }
    
    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job failed permanently', [
            'webhook_event_id' => $this->webhookEvent->id,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        // Mark webhook as failed
        $this->webhookEvent->markAsFailed($exception->getMessage());
        
        // Send alert for critical webhooks
        if ($this->isCriticalWebhook()) {
            $this->sendFailureAlert($exception);
        }
    }
    
    /**
     * Handle failure within job execution
     */
    protected function handleFailure(\Exception $e): void
    {
        Log::error('Webhook processing error', [
            'webhook_event_id' => $this->webhookEvent->id,
            'correlation_id' => $this->correlationId,
            'error' => $e->getMessage(),
            'attempt' => $this->attempts(),
            'will_retry' => $this->attempts() < $this->tries,
        ]);
        
        // If this is the last attempt, mark as failed
        if ($this->attempts() >= $this->tries) {
            $this->webhookEvent->markAsFailed($e->getMessage());
        } else {
            // Otherwise mark as pending for retry
            $this->webhookEvent->update([
                'status' => WebhookEvent::STATUS_PENDING,
                'error_message' => $e->getMessage(),
                'retry_count' => $this->attempts(),
            ]);
        }
        
        // Re-throw to trigger retry
        throw $e;
    }
    
    /**
     * Get queue name based on provider
     */
    protected function getQueueName(): string
    {
        return match($this->webhookEvent->provider) {
            'retell' => 'webhooks-high',
            'calcom' => 'webhooks-high',
            'stripe' => 'webhooks-medium',
            default => 'webhooks-low',
        };
    }
    
    /**
     * Check if this is a critical webhook
     */
    protected function isCriticalWebhook(): bool
    {
        // Call ended events are critical for business
        if ($this->webhookEvent->provider === 'retell' && 
            $this->webhookEvent->event_type === 'call_ended') {
            return true;
        }
        
        // Booking created/cancelled are critical
        if ($this->webhookEvent->provider === 'calcom' && 
            in_array($this->webhookEvent->event_type, ['booking.created', 'booking.cancelled'])) {
            return true;
        }
        
        // Payment events are critical
        if ($this->webhookEvent->provider === 'stripe') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Send failure alert
     */
    protected function sendFailureAlert(\Throwable $exception): void
    {
        // TODO: Implement notification system
        Log::critical('Critical webhook failed', [
            'webhook_event_id' => $this->webhookEvent->id,
            'provider' => $this->webhookEvent->provider,
            'event_type' => $this->webhookEvent->event_type,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
    
    /**
     * Calculate backoff time with exponential increase
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // 30s, 1m, 2m
    }
    
    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return [
            'webhook',
            'provider:' . $this->webhookEvent->provider,
            'event:' . $this->webhookEvent->event_type,
        ];
    }
}