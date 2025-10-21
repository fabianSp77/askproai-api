<?php

namespace App\Exceptions\Appointments;

/**
 * Cal.com API Booking Exception
 *
 * Raised when Cal.com booking fails (network, API error, etc.)
 * May be retryable depending on error type
 */
class CalcomBookingException extends AppointmentException
{
    /**
     * Cal.com HTTP status code
     */
    private ?int $httpStatus = null;

    /**
     * Cal.com error response
     */
    private ?array $calcomError = null;

    public function __construct(
        string $message = "Cal.com booking failed",
        int $httpStatus = 0,
        ?array $calcomError = null,
        string $correlationId = "",
        array $context = [],
        bool $retryable = false
    ) {
        $this->httpStatus = $httpStatus;
        $this->calcomError = $calcomError;

        // Determine if retryable based on HTTP status
        $retryable = $this->isTransientError($httpStatus, $calcomError);

        parent::__construct(
            message: $message,
            code: $httpStatus,
            previous: null,
            correlationId: $correlationId,
            context: array_merge($context, [
                'http_status' => $httpStatus,
                'calcom_error' => $calcomError,
            ]),
            retryable: $retryable
        );
    }

    /**
     * Determine if error is transient (retryable)
     *
     * Transient errors:
     * - 429 (Rate limit)
     * - 503 (Service unavailable)
     * - 504 (Gateway timeout)
     * - Timeout exceptions
     *
     * Permanent errors:
     * - 400 (Bad request)
     * - 401 (Unauthorized)
     * - 403 (Forbidden)
     * - 404 (Not found)
     */
    private function isTransientError(?int $status, ?array $error): bool
    {
        if (!$status) {
            return true; // Network errors are usually transient
        }

        return in_array($status, [429, 503, 504]);
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getCalcomError(): ?array
    {
        return $this->calcomError;
    }
}
