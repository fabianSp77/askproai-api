<?php

namespace App\Services\Saga;

use Exception;

/**
 * Exception thrown when a saga step fails
 * Includes context about which step failed and what compensations were executed
 */
class SagaException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $sagaId,
        public readonly string $failedStep,
        public readonly array $completedSteps,
        public readonly ?Exception $previousException = null,
        int $code = 0
    ) {
        parent::__construct($message, $code, $previousException);
    }
}
