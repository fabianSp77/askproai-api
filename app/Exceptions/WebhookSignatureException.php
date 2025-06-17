<?php

namespace App\Exceptions;

use Exception;

class WebhookSignatureException extends Exception
{
    /**
     * The provider that failed signature verification
     *
     * @var string
     */
    protected string $provider;
    
    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param string $provider
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = "",
        string $provider = "",
        int $code = 401,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->provider = $provider;
    }
    
    /**
     * Get the provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }
    
    /**
     * Report the exception
     *
     * @return bool|null
     */
    public function report(): ?bool
    {
        \Log::error('Webhook signature verification failed', [
            'provider' => $this->provider,
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
            'error' => 'Invalid webhook signature',
            'message' => $this->getMessage(),
            'provider' => $this->provider
        ], $this->code);
    }
}