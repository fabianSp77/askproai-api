<?php

namespace App\Jobs;

use App\Services\MCP\WebhookMCPServer;
use App\Services\CircuitBreaker\CircuitBreaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Optimized job for async webhook processing
 * Implements circuit breaker pattern and connection pooling
 */
class OptimizedProcessRetellWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Exponential backoff
    public $timeout = 120;
    public $failOnTimeout = false;
    
    private array $payload;
    private array $headers;
    private string $correlationId;
    
    public function __construct(array $payload, array $headers, string $correlationId)
    {
        $this->payload = $payload;
        $this->headers = $headers;
        $this->correlationId = $correlationId;
        
        // Set queue based on event priority
        $event = $payload['event'] ?? 'unknown';
        $this->onQueue($this->determineQueue($event));
    }
    
    /**
     * Execute the job with circuit breaker protection
     */
    public function handle()
    {
        $startTime = microtime(true);
        
        try {
            // Get circuit breaker for webhook processing
            $circuitBreaker = app(CircuitBreaker::class, [
                'name' => 'webhook-processor',
                'failureThreshold' => 5,
                'recoveryTime' => 60,
                'timeout' => 30
            ]);
            
            // Process through circuit breaker
            $result = $circuitBreaker->call(function() {
                return $this->processWebhook();
            });
            
            // Track success metrics
            $this->trackMetrics('success', microtime(true) - $startTime);
            
            Log::info('Webhook processed successfully', [
                'correlation_id' => $this->correlationId,
                'event' => $this->payload['event'] ?? 'unknown',
                'duration_ms' => (microtime(true) - $startTime) * 1000
            ]);
            
        } catch (\Exception $e) {
            $this->trackMetrics('failure', microtime(true) - $startTime);
            
            Log::error('Webhook processing failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);
            
            // Check if we should retry
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);
            } else {
                // Move to DLQ for manual inspection
                $this->fail($e);
            }
        }
    }
    
    /**
     * Process webhook through MCP server
     */
    private function processWebhook()
    {
        // Use connection pooling for MCP server
        $mcpServer = Cache::remember('mcp-webhook-server', 60, function() {
            return app(WebhookMCPServer::class);
        });
        
        // Process based on event type
        $event = $this->payload['event'] ?? 'unknown';
        
        switch ($event) {
            case 'call_ended':
            case 'call_analyzed':
                return $mcpServer->processCallEnded($this->payload);
                
            case 'call_started':
                return $mcpServer->processCallStarted($this->payload);
                
            default:
                Log::warning('Unknown webhook event type', [
                    'event' => $event,
                    'correlation_id' => $this->correlationId
                ]);
                return ['status' => 'ignored'];
        }
    }
    
    /**
     * Determine queue based on event priority
     */
    private function determineQueue(string $event): string
    {
        $queueMap = [
            'call_ended' => 'webhooks-high-priority',
            'call_analyzed' => 'webhooks-high-priority',
            'call_started' => 'webhooks-medium-priority',
            'call_failed' => 'webhooks-low-priority',
        ];
        
        return $queueMap[$event] ?? 'webhooks-default';
    }
    
    /**
     * Track performance metrics
     */
    private function trackMetrics(string $status, float $duration)
    {
        $key = "webhook_metrics:" . date('Y-m-d:H');
        
        Cache::increment("{$key}:total");
        Cache::increment("{$key}:{$status}");
        Cache::increment("{$key}:duration", $duration * 1000);
        
        // Keep metrics for 7 days
        Cache::put("{$key}:ttl", true, now()->addDays(7));
    }
    
    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Webhook job permanently failed', [
            'correlation_id' => $this->correlationId,
            'event' => $this->payload['event'] ?? 'unknown',
            'error' => $exception->getMessage(),
            'payload' => $this->payload
        ]);
        
        // Store in dead letter queue table
        \DB::table('webhook_dead_letter_queue')->insert([
            'correlation_id' => $this->correlationId,
            'event_type' => $this->payload['event'] ?? 'unknown',
            'payload' => json_encode($this->payload),
            'headers' => json_encode($this->headers),
            'error' => $exception->getMessage(),
            'failed_at' => now(),
        ]);
    }
    
    /**
     * Determine if job should retry after failure
     */
    public function shouldRetryAfterException(\Throwable $exception): bool
    {
        // Don't retry validation errors
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return false;
        }
        
        // Don't retry if circuit breaker is open
        if ($exception instanceof \App\Exceptions\CircuitBreakerOpenException) {
            return false;
        }
        
        return true;
    }
}