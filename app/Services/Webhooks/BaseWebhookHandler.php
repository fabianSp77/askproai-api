<?php

namespace App\Services\Webhooks;

use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;

abstract class BaseWebhookHandler implements WebhookHandlerInterface
{
    /**
     * Log context for this handler
     *
     * @var array
     */
    protected array $logContext = [];
    
    /**
     * Handle the webhook event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    public function handle(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $this->logContext = [
            'provider' => $webhookEvent->provider,
            'event_type' => $webhookEvent->event_type,
            'webhook_event_id' => $webhookEvent->id,
            'correlation_id' => $correlationId
        ];
        
        $this->logInfo('Processing webhook event');
        
        // Check if event type is supported
        if (!$this->supportsEvent($webhookEvent->event_type)) {
            $this->logWarning('Unsupported event type');
            return [
                'success' => true,
                'message' => 'Event type not supported by handler',
                'skipped' => true
            ];
        }
        
        // Route to specific event handler
        $method = $this->getHandlerMethod($webhookEvent->event_type);
        
        if (!method_exists($this, $method)) {
            $this->logError("Handler method not found: {$method}");
            throw new \RuntimeException("Handler method not found: {$method}");
        }
        
        return $this->$method($webhookEvent, $correlationId);
    }
    
    /**
     * Check if the handler supports a specific event type
     *
     * @param string $eventType
     * @return bool
     */
    public function supportsEvent(string $eventType): bool
    {
        return in_array($eventType, $this->getSupportedEvents());
    }
    
    /**
     * Get the handler method name for an event type
     *
     * @param string $eventType
     * @return string
     */
    protected function getHandlerMethod(string $eventType): string
    {
        // Convert event type to camelCase method name
        // e.g., "call_ended" -> "handleCallEnded"
        $parts = explode('_', $eventType);
        $method = 'handle' . implode('', array_map('ucfirst', $parts));
        
        return $method;
    }
    
    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, array_merge($this->logContext, $context));
    }
    
    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, array_merge($this->logContext, $context));
    }
    
    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge($this->logContext, $context));
    }
    
    /**
     * Track correlation ID across service calls
     *
     * @param string $correlationId
     * @param callable $callback
     * @return mixed
     */
    protected function withCorrelationId(string $correlationId, callable $callback)
    {
        // Store correlation ID in request context
        app()->instance('correlation_id', $correlationId);
        
        try {
            return $callback();
        } finally {
            // Clean up
            app()->forgetInstance('correlation_id');
        }
    }
}