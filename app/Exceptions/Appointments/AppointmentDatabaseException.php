<?php

namespace App\Exceptions\Appointments;

/**
 * Appointment Database Exception
 *
 * Raised when database operations fail during appointment creation/sync
 * May be retryable depending on error type (deadlock, timeout) vs permanent (constraint violation)
 */
class AppointmentDatabaseException extends AppointmentException
{
    /**
     * Type of database error
     */
    private string $errorType;

    /**
     * SQL error code (if available)
     */
    private ?string $sqlErrorCode = null;

    public function __construct(
        string $message = "Database operation failed",
        string $errorType = "unknown", // deadlock, timeout, constraint_violation, connection_lost, etc
        ?string $sqlErrorCode = null,
        string $correlationId = "",
        array $context = [],
        bool $retryable = false
    ) {
        $this->errorType = $errorType;
        $this->sqlErrorCode = $sqlErrorCode;

        // Determine if retryable based on error type
        $retryable = $this->isTransientDatabaseError($errorType, $sqlErrorCode);

        parent::__construct(
            message: $message,
            code: 500,
            previous: null,
            correlationId: $correlationId,
            context: array_merge($context, [
                'error_type' => $errorType,
                'sql_error_code' => $sqlErrorCode,
            ]),
            retryable: $retryable
        );
    }

    /**
     * Determine if database error is transient (retryable)
     *
     * Transient errors:
     * - Deadlock: Auto-retry, usually succeeds on retry
     * - Timeout: May recover if service becomes responsive
     * - Connection lost: May recover if connection reestablished
     *
     * Permanent errors:
     * - Constraint violation: Won't resolve by retrying
     * - Syntax error: Code problem, not transient
     * - Table not found: Schema issue, won't fix by retry
     */
    private function isTransientDatabaseError(string $errorType, ?string $sqlErrorCode): bool
    {
        $transientErrors = ['deadlock', 'timeout', 'connection_lost', 'too_many_connections'];

        if (in_array($errorType, $transientErrors)) {
            return true;
        }

        // Check SQL error codes (MySQL error codes)
        if ($sqlErrorCode) {
            // 1213: Deadlock
            // 2006: MySQL server has gone away
            // 2013: Lost connection to server
            // 1205: Lock wait timeout
            $transientCodes = ['1213', '2006', '2013', '1205'];
            return in_array($sqlErrorCode, $transientCodes);
        }

        return false;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getSqlErrorCode(): ?string
    {
        return $this->sqlErrorCode;
    }

    public function isDeadlock(): bool
    {
        return $this->errorType === 'deadlock' || $this->sqlErrorCode === '1213';
    }

    public function isConnectionLost(): bool
    {
        return $this->errorType === 'connection_lost' ||
               in_array($this->sqlErrorCode, ['2006', '2013']);
    }

    public function isTimeout(): bool
    {
        return $this->errorType === 'timeout' || $this->sqlErrorCode === '1205';
    }
}
