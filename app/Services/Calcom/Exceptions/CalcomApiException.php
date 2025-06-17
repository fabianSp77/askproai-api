<?php

namespace App\Services\Calcom\Exceptions;

/**
 * Base exception for Cal.com API errors
 */
class CalcomApiException extends \Exception
{
    protected int $statusCode;
    protected ?string $errorCode;
    protected array $context;

    public function __construct(string $message = "", int $statusCode = 0, ?string $errorCode = null, array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
            'error_code' => $this->errorCode,
            'context' => $this->context,
        ];
    }
}