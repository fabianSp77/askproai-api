<?php

namespace App\Exceptions\Appointments;

use Exception;

/**
 * Base Exception for Appointment-related errors
 *
 * Provides foundation for domain-specific exceptions with structured logging
 */
class AppointmentException extends Exception
{
    /**
     * @var string Correlation ID for tracing related errors
     */
    protected string $correlationId;

    /**
     * @var array Additional context for logging
     */
    protected array $context = [];

    /**
     * @var bool Whether this error is retryable
     */
    protected bool $retryable = false;

    public function __construct(
        string $message = "",
        int $code = 0,
        Exception $previous = null,
        string $correlationId = "",
        array $context = [],
        bool $retryable = false
    ) {
        parent::__construct($message, $code, $previous);
        $this->correlationId = $correlationId ?: uniqid('appointment_');
        $this->context = $context;
        $this->retryable = $retryable;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * Get structured log data for this exception
     */
    public function toLogContext(): array
    {
        return [
            'exception' => static::class,
            'message' => $this->message,
            'correlation_id' => $this->correlationId,
            'code' => $this->code,
            'context' => $this->context,
            'retryable' => $this->retryable,
            'trace' => $this->getTraceAsString(),
        ];
    }
}
