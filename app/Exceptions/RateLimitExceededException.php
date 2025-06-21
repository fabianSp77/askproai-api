<?php

namespace App\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    protected $code = 429;
    
    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => $this->getMessage(),
            ], 429);
        }
        
        return response()->view('errors.429', [
            'message' => $this->getMessage()
        ], 429);
    }
}