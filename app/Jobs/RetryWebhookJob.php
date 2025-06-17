<?php

namespace App\Jobs;

use App\Services\WebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The webhook event ID to retry
     *
     * @var int
     */
    protected int $webhookEventId;

    /**
     * Create a new job instance.
     *
     * @param int $webhookEventId
     */
    public function __construct(int $webhookEventId)
    {
        $this->webhookEventId = $webhookEventId;
    }

    /**
     * Execute the job.
     *
     * @param WebhookProcessor $processor
     * @return void
     */
    public function handle(WebhookProcessor $processor): void
    {
        Log::info('Retrying webhook', [
            'webhook_event_id' => $this->webhookEventId
        ]);
        
        try {
            $result = $processor->retry($this->webhookEventId);
            
            Log::info('Webhook retry completed', [
                'webhook_event_id' => $this->webhookEventId,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook retry failed', [
                'webhook_event_id' => $this->webhookEventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Let the job fail so it can be retried by the queue system
            throw $e;
        }
    }
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;
}