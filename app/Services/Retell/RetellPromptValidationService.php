<?php

namespace App\Services\Retell;

use Illuminate\Support\Facades\Log;

/**
 * Retell Prompt Validation Service
 *
 * Validates Retell AI agent prompts and function configurations
 */
class RetellPromptValidationService
{
    // Retell API limits
    const MAX_PROMPT_LENGTH = 10000;
    const MAX_FUNCTIONS = 20;
    const REQUIRED_FUNCTION_FIELDS = ['name', 'description', 'parameters'];
    const VALID_LANGUAGES = [
        'en-US', 'en-IN', 'en-GB', 'en-AU', 'en-NZ',
        'de-DE', 'es-ES', 'es-419', 'hi-IN', 'fr-FR', 'fr-CA',
        'ja-JP', 'pt-PT', 'pt-BR', 'zh-CN', 'ru-RU', 'it-IT',
        'ko-KR', 'nl-NL', 'nl-BE', 'pl-PL', 'tr-TR', 'vi-VN',
        'ro-RO', 'bg-BG', 'ca-ES', 'th-TH', 'da-DK', 'fi-FI',
        'el-GR', 'hu-HU', 'id-ID', 'no-NO', 'sk-SK', 'sv-SE',
        'ms-MY', 'multi'
    ];

    /**
     * Validate prompt content
     */
    public function validatePromptContent(string $content): array
    {
        $errors = [];

        if (empty(trim($content))) {
            $errors[] = 'Prompt content cannot be empty';
        }

        if (strlen($content) > self::MAX_PROMPT_LENGTH) {
            $errors[] = "Prompt exceeds maximum length (" . self::MAX_PROMPT_LENGTH . " characters)";
        }

        // Check for required sections (optional but recommended)
        $requiredSections = ['WORKFLOW', 'FUNKTION'];
        foreach ($requiredSections as $section) {
            if (stripos($content, $section) === false) {
                $errors[] = "Recommended: Include '$section' section in prompt";
            }
        }

        return $errors;
    }

    /**
     * Validate functions configuration
     */
    public function validateFunctionsConfig(array $functions): array
    {
        $errors = [];

        if (empty($functions)) {
            $errors[] = 'Functions config cannot be empty';
            return $errors;
        }

        if (count($functions) > self::MAX_FUNCTIONS) {
            $errors[] = "Exceeds maximum function count (" . self::MAX_FUNCTIONS . ")";
        }

        $functionNames = [];
        foreach ($functions as $index => $function) {
            $functionErrors = $this->validateFunction($function, $index);
            $errors = array_merge($errors, $functionErrors);

            if (isset($function['name'])) {
                if (in_array($function['name'], $functionNames)) {
                    $errors[] = "Duplicate function name: {$function['name']}";
                }
                $functionNames[] = $function['name'];
            }
        }

        return $errors;
    }

    /**
     * Validate a single function definition
     */
    private function validateFunction(array $function, int $index): array
    {
        $errors = [];
        $functionName = $function['name'] ?? "Function #" . ($index + 1);

        // Check required fields
        foreach (self::REQUIRED_FUNCTION_FIELDS as $field) {
            if (empty($function[$field])) {
                $errors[] = "$functionName: Missing required field '$field'";
            }
        }

        // Validate function name
        if (isset($function['name'])) {
            if (!preg_match('/^[a-z_][a-z0-9_]*$/', $function['name'])) {
                $errors[] = "$functionName: Invalid function name (must be lowercase with underscores)";
            }
        }

        // Validate parameters structure
        if (isset($function['parameters'])) {
            $params = $function['parameters'];

            if (!is_array($params)) {
                $errors[] = "$functionName: Parameters must be an object/array";
            } elseif (empty($params['type']) || $params['type'] !== 'object') {
                $errors[] = "$functionName: Parameters type must be 'object'";
            } elseif (!isset($params['properties'])) {
                $errors[] = "$functionName: Parameters must have 'properties' defined";
            }
        }

        return $errors;
    }

    /**
     * Validate language code
     */
    public function validateLanguageCode(string $code): array
    {
        $errors = [];

        if (!in_array($code, self::VALID_LANGUAGES)) {
            $errors[] = "Invalid language code: $code. Valid codes: " . implode(', ', self::VALID_LANGUAGES);
        }

        return $errors;
    }

    /**
     * Comprehensive validation
     */
    public function validate(string $promptContent, array $functionsConfig, string $languageCode = 'de-DE'): array
    {
        $allErrors = [];

        $promptErrors = $this->validatePromptContent($promptContent);
        $allErrors = array_merge($allErrors, $promptErrors);

        $functionErrors = $this->validateFunctionsConfig($functionsConfig);
        $allErrors = array_merge($allErrors, $functionErrors);

        $languageErrors = $this->validateLanguageCode($languageCode);
        $allErrors = array_merge($allErrors, $languageErrors);

        return $allErrors;
    }

    /**
     * Get validation summary
     */
    public function getValidationSummary(string $promptContent, array $functionsConfig): array
    {
        $errors = $this->validate($promptContent, $functionsConfig);

        return [
            'is_valid' => empty($errors),
            'error_count' => count($errors),
            'errors' => $errors,
            'prompt_length' => strlen($promptContent),
            'function_count' => count($functionsConfig),
            'max_prompt_length' => self::MAX_PROMPT_LENGTH,
            'max_functions' => self::MAX_FUNCTIONS,
        ];
    }
}
