<?php

namespace App\Exceptions;

use Exception;

class MCPException extends Exception
{
    // Error codes
    public const INVALID_PARAMS = 400;
    public const AUTHENTICATION_FAILED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const INVALID_RESPONSE = 422;
    public const CONNECTION_FAILED = 502;
    public const SERVICE_UNAVAILABLE = 503;
    public const TIMEOUT = 504;
    public const UNKNOWN_ERROR = 500;
    public const MAX_RETRIES_EXCEEDED = 509;
    
    protected array $context = [];
    
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
    
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }
}