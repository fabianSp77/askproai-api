<?php

namespace App\Services\Calcom\Exceptions;

/**
 * Exception for validation errors (422 errors)
 */
class CalcomValidationException extends CalcomApiException
{
    protected array $errors;

    public function __construct(string $message = "Validation failed", int $statusCode = 422, array $errors = [], array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, 'VALIDATION_FAILED', $context, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'errors' => $this->errors,
        ]);
    }

    /**
     * Get flattened list of all error messages
     */
    public function getErrorMessages(): array
    {
        $messages = [];
        
        foreach ($this->errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $error) {
                    $messages[] = "{$field}: {$error}";
                }
            } else {
                $messages[] = "{$field}: {$fieldErrors}";
            }
        }
        
        return $messages;
    }
}