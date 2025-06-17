<?php

namespace App\Services\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Exceptions\WebhookException;

class WebhookProcessor
{
    private array $strategies = [];
    
    public function __construct()
    {
        // Register all webhook strategies
        $this->registerStrategy(new CalcomWebhookStrategy());
        $this->registerStrategy(new RetellWebhookStrategy());
        $this->registerStrategy(new StripeWebhookStrategy());
    }
    
    /**
     * Register a webhook strategy
     */
    public function registerStrategy(WebhookStrategy $strategy): void
    {
        $this->strategies[$strategy->getSource()] = $strategy;
    }
    
    /**
     * Process incoming webhook
     */
    public function process(Request $request): array
    {
        $startTime = microtime(true);
        
        // Find the appropriate strategy
        $strategy = $this->findStrategy($request);
        
        if (!$strategy) {
            Log::warning('No webhook strategy found for request', [
                'headers' => $request->headers->all(),
                'url' => $request->fullUrl()
            ]);
            throw new WebhookException('Unknown webhook source', 400);
        }
        
        // Validate signature
        if (!$strategy->validateSignature($request)) {
            Log::error('Webhook signature validation failed', [
                'source' => $strategy->getSource(),
                'ip' => $request->ip()
            ]);
            throw new WebhookException('Invalid webhook signature', 401);
        }
        
        // Parse payload
        $payload = $this->parsePayload($request);
        
        // Log webhook receipt
        $this->logWebhook($strategy->getSource(), $payload, $request);
        
        // Process webhook
        try {
            $strategy->process($payload);
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Webhook processed successfully', [
                'source' => $strategy->getSource(),
                'processing_time_ms' => $processingTime
            ]);
            
            return [
                'success' => true,
                'source' => $strategy->getSource(),
                'processing_time_ms' => $processingTime
            ];
            
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'source' => $strategy->getSource(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new WebhookException(
                'Webhook processing failed: ' . $e->getMessage(),
                500,
                $e
            );
        }
    }
    
    /**
     * Find the appropriate strategy for the request
     */
    private function findStrategy(Request $request): ?WebhookStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canHandle($request)) {
                return $strategy;
            }
        }
        
        return null;
    }
    
    /**
     * Parse webhook payload
     */
    private function parsePayload(Request $request): array
    {
        $content = $request->getContent();
        
        if (empty($content)) {
            throw new WebhookException('Empty webhook payload', 400);
        }
        
        $payload = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new WebhookException(
                'Invalid JSON payload: ' . json_last_error_msg(),
                400
            );
        }
        
        return $payload;
    }
    
    /**
     * Log webhook for debugging and analytics
     */
    private function logWebhook(string $source, array $payload, Request $request): void
    {
        Log::channel('webhooks')->info('Webhook received', [
            'source' => $source,
            'event' => $this->extractEventType($source, $payload),
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'payload_size' => strlen($request->getContent())
        ]);
        
        // Store webhook in database for replay/debugging
        \DB::table('webhook_logs')->insert([
            'source' => $source,
            'event' => $this->extractEventType($source, $payload),
            'payload' => json_encode($payload),
            'headers' => json_encode($request->headers->all()),
            'ip_address' => $request->ip(),
            'created_at' => now()
        ]);
    }
    
    /**
     * Extract event type from payload based on source
     */
    private function extractEventType(string $source, array $payload): string
    {
        return match($source) {
            'calcom' => $payload['triggerEvent'] ?? 'unknown',
            'retell' => $payload['event'] ?? 'unknown',
            'stripe' => $payload['type'] ?? 'unknown',
            default => 'unknown'
        };
    }
    
    /**
     * Get all registered strategies
     */
    public function getStrategies(): array
    {
        return array_keys($this->strategies);
    }
}