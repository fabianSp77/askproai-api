<?php

namespace App\Services\Calcom\Exceptions;

/**
 * Exception for authentication failures (401 errors)
 */
class CalcomAuthenticationException extends CalcomApiException
{
    public function __construct(string $message = "Authentication failed", int $statusCode = 401, array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, 'AUTHENTICATION_FAILED', $context, $previous);
    }
}