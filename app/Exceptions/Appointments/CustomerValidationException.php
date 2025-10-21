<?php

namespace App\Exceptions\Appointments;

/**
 * Customer Validation Exception
 *
 * Raised when customer data validation fails during appointment booking
 * Typically permanent errors (invalid email, missing fields, etc.)
 */
class CustomerValidationException extends AppointmentException
{
    /**
     * Invalid field information
     */
    private ?array $validationErrors = null;

    public function __construct(
        string $message = "Customer validation failed",
        ?array $validationErrors = null,
        string $correlationId = "",
        array $context = [],
        bool $retryable = false
    ) {
        $this->validationErrors = $validationErrors;

        parent::__construct(
            message: $message,
            code: 422, // Unprocessable Entity
            previous: null,
            correlationId: $correlationId,
            context: array_merge($context, [
                'validation_errors' => $validationErrors,
            ]),
            retryable: $retryable // Validation errors are typically NOT retryable
        );
    }

    public function getValidationErrors(): ?array
    {
        return $this->validationErrors;
    }

    /**
     * Check if specific field is invalid
     */
    public function hasFieldError(string $fieldName): bool
    {
        return isset($this->validationErrors[$fieldName]);
    }

    /**
     * Get error for specific field
     */
    public function getFieldError(string $fieldName): ?string
    {
        return $this->validationErrors[$fieldName] ?? null;
    }
}
