<?php

namespace App\Exceptions;

use Exception;

class CircuitBreakerOpenException extends Exception
{
    public function __construct(string $message = "", int $code = 503, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Render the exception into an HTTP response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Service temporarily unavailable',
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
            ], $this->getCode());
        }
        
        return response()->view('errors.503', [
            'message' => $this->getMessage()
        ], $this->getCode());
    }
}