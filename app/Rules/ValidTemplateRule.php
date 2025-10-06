<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validate notification template override for safe template syntax
 *
 * Prevents:
 * - Template injection attacks
 * - Malicious code execution
 * - Invalid template syntax
 */
class ValidTemplateRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // Nullable field
        }

        // Check for potentially dangerous patterns
        $dangerousPatterns = [
            '/@php\s/i',                    // Raw PHP code
            '/\{\{\s*\$_/',                 // Superglobals
            '/\{\{\s*exec\s*\(/i',          // Command execution
            '/\{\{\s*system\s*\(/i',        // System calls
            '/\{\{\s*shell_exec\s*\(/i',    // Shell execution
            '/\{\{\s*eval\s*\(/i',          // Code evaluation
            '/\<script/i',                  // Script tags
            '/javascript\:/i',              // JavaScript protocol
            '/on(click|load|error|mouseover)=/i', // Event handlers
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail("The {$attribute} contains potentially dangerous template syntax.");
                return;
            }
        }

        // Validate balanced template brackets
        $openCount = substr_count($value, '{{');
        $closeCount = substr_count($value, '}}');

        if ($openCount !== $closeCount) {
            $fail("The {$attribute} has unbalanced template brackets.");
            return;
        }

        // Check for valid variable pattern (only alphanumeric, dots, underscores)
        preg_match_all('/\{\{([^}]+)\}\}/', $value, $matches);

        foreach ($matches[1] ?? [] as $variable) {
            $trimmed = trim($variable);

            // Allow: simple variables, object properties, array access
            if (!preg_match('/^[\w\.\[\]\'\"]+$/', $trimmed)) {
                $fail("The {$attribute} contains invalid template variable: {{" . htmlspecialchars($trimmed) . "}}");
                return;
            }
        }

        // Length check (already handled by maxLength, but double-check)
        if (strlen($value) > 65535) {
            $fail("The {$attribute} exceeds maximum length of 65535 characters.");
            return;
        }
    }
}
