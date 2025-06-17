<?php

namespace App\Services\Calcom\Exceptions;

/**
 * Exception for rate limit errors (429 errors)
 */
class CalcomRateLimitException extends CalcomApiException
{
    protected ?int $retryAfter;

    public function __construct(string $message = "Rate limit exceeded", int $statusCode = 429, ?int $retryAfter = null, array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, 'RATE_LIMIT_EXCEEDED', $context, $previous);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'retry_after' => $this->retryAfter,
        ]);
    }
}