<?php

namespace App\Services\Saga;

use Exception;

/**
 * Exception thrown when compensation (rollback) itself fails
 * This is a critical error - indicates data inconsistency
 */
class SagaCompensationException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $sagaId,
        public readonly array $failedCompensations = [],
        int $code = 0
    ) {
        parent::__construct($message, $code);
    }
}
