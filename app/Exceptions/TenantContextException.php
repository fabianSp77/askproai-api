<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown for tenant context related issues
 */
class TenantContextException extends Exception
{
    protected $code = 403;
    
    public function __construct(string $message = 'Tenant context error', int $code = 403, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Report the exception
     */
    public function report(): void
    {
        \Log::channel('security')->error('TenantContextException: ' . $this->getMessage(), [
            'exception' => $this,
            'trace' => $this->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip' => request()?->ip(),
            'url' => request()?->fullUrl()
        ]);
    }
    
    /**
     * Render the exception into an HTTP response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant access denied',
                'message' => 'You do not have permission to access this resource.',
                'code' => $this->getCode()
            ], $this->getCode());
        }
        
        return response()->view('errors.tenant-access-denied', [
            'message' => $this->getMessage()
        ], $this->getCode());
    }
}