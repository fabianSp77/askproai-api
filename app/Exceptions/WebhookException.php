<?php

namespace App\Exceptions;

use Exception;

class WebhookException extends Exception
{
    public function __construct(string $message, int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Render the exception into an HTTP response
     */
    public function render($request)
    {
        return response()->json([
            'error' => $this->getMessage(),
            'code' => $this->getCode()
        ], $this->getCode());
    }
}