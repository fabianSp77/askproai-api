<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Security\InputValidator;

class SafeString implements ValidationRule
{
    private InputValidator $validator;
    private bool $allowHtml;

    public function __construct(bool $allowHtml = false)
    {
        $this->validator = new InputValidator();
        $this->allowHtml = $allowHtml;
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

        // Check for SQL injection
        if ($this->validator->containsSqlInjection($value)) {
            $fail('The :attribute contains potentially dangerous SQL characters.');
            return;
        }

        // Check for XSS if HTML is not allowed
        if (!$this->allowHtml && $this->validator->containsXss($value)) {
            $fail('The :attribute contains HTML or script tags which are not allowed.');
            return;
        }

        // Check for path traversal
        if ($this->validator->containsPathTraversal($value)) {
            $fail('The :attribute contains invalid path characters.');
            return;
        }

        // Check for null bytes
        if (strpos($value, chr(0)) !== false) {
            $fail('The :attribute contains invalid characters.');
            return;
        }
    }
}