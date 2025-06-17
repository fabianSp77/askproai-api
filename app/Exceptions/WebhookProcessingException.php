<?php

namespace App\Exceptions;

use Exception;

class WebhookProcessingException extends Exception
{
    /**
     * The webhook event ID if available
     *
     * @var int|null
     */
    protected ?int $webhookEventId;
    
    /**
     * The correlation ID if available
     *
     * @var string|null
     */
    protected ?string $correlationId;
    
    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param int|null $webhookEventId
     * @param string|null $correlationId
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = "",
        ?int $webhookEventId = null,
        ?string $correlationId = null,
        int $code = 500,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->webhookEventId = $webhookEventId;
        $this->correlationId = $correlationId;
    }
    
    /**
     * Get the webhook event ID
     *
     * @return int|null
     */
    public function getWebhookEventId(): ?int
    {
        return $this->webhookEventId;
    }
    
    /**
     * Get the correlation ID
     *
     * @return string|null
     */
    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }
    
    /**
     * Report the exception
     *
     * @return bool|null
     */
    public function report(): ?bool
    {
        \Log::error('Webhook processing failed', [
            'webhook_event_id' => $this->webhookEventId,
            'correlation_id' => $this->correlationId,
            'message' => $this->getMessage(),
            'trace' => $this->getTraceAsString()
        ]);
        
        return false;
    }
    
    /**
     * Render the exception into an HTTP response
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'error' => 'Webhook processing failed',
            'message' => $this->getMessage(),
            'webhook_event_id' => $this->webhookEventId,
            'correlation_id' => $this->correlationId
        ], $this->code);
    }
}