<?php

namespace App\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    protected int $retryAfter;
    
    public function __construct(string $message = "", int $retryAfter = 60, int $code = 429, Exception $previous = null)
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get the number of seconds until the rate limit resets
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
    
    /**
     * Render the exception as an HTTP response
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => $this->getMessage(),
                'retry_after' => $this->retryAfter,
            ], $this->getCode())
            ->header('Retry-After', $this->retryAfter);
        }
        
        return response()->view('errors.429', [
            'message' => $this->getMessage(),
            'retry_after' => $this->retryAfter,
        ], $this->getCode())
        ->header('Retry-After', $this->retryAfter);
    }
}