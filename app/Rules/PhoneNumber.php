<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Security\InputValidator;

class PhoneNumber implements ValidationRule
{
    private InputValidator $validator;

    public function __construct()
    {
        $this->validator = new InputValidator();
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        // Check for injection attempts first
        if ($this->validator->containsSqlInjection($value) || 
            $this->validator->containsXss($value)) {
            $fail('The :attribute contains invalid characters.');
            return;
        }

        // Validate phone number format
        if (!$this->validator->isValidPhoneNumber($value)) {
            $fail('The :attribute must be a valid phone number.');
            return;
        }
    }
}