<?php

namespace App\Services\Retell;

use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionMethod;
use App\Http\Controllers\RetellFunctionCallHandler;

/**
 * Extracts function schemas from RetellFunctionCallHandler via Reflection
 *
 * Purpose: Provide real-time, code-based documentation of all Retell AI functions
 * Use Case: Auto-generate documentation, validate agent configs, API schema exports
 */
class FunctionSchemaExtractor
{
    private ReflectionClass $reflector;
    private array $functionMetadata;

    public function __construct()
    {
        $this->reflector = new ReflectionClass(RetellFunctionCallHandler::class);
        $this->initializeMetadata();
    }

    /**
     * Initialize function metadata (status, priority, descriptions)
     * TODO: Move to database or config file for easier maintenance
     */
    private function initializeMetadata(): void
    {
        // Based on agent-v50-interactive-complete.html documentation
        $this->functionMetadata = [
            'check_customer' => [
                'status' => 'live',
                'priority' => 'critical',
                'description' => 'Check if customer exists by phone number',
                'category' => 'customer_management',
                'added_version' => 'V133',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'parse_date' => [
                'status' => 'live',
                'priority' => 'critical',
                'description' => 'Parse natural language date to ISO format',
                'category' => 'utility',
                'added_version' => 'V50',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'check_availability' => [
                'status' => 'live',
                'priority' => 'critical',
                'description' => 'Check available time slots for service and date',
                'category' => 'booking',
                'added_version' => 'V1',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'book_appointment' => [
                'status' => 'deprecated',
                'priority' => 'low',
                'description' => 'Book appointment (single-step, deprecated in favor of 2-step booking)',
                'category' => 'booking',
                'added_version' => 'V1',
                'deprecated_version' => 'V50',
                'replacement' => 'start_booking + confirm_booking',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'start_booking' => [
                'status' => 'live',
                'priority' => 'critical',
                'description' => 'Start booking process (step 1 of 2-step booking)',
                'category' => 'booking',
                'added_version' => 'V50',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'confirm_booking' => [
                'status' => 'live',
                'priority' => 'critical',
                'description' => 'Confirm and finalize booking (step 2 of 2-step booking)',
                'category' => 'booking',
                'added_version' => 'V50',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'query_appointment' => [
                'status' => 'live',
                'priority' => 'high',
                'description' => 'Query appointment by call_id or customer_phone',
                'category' => 'appointment_management',
                'added_version' => 'V1',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'query_appointment_by_name' => [
                'status' => 'live',
                'priority' => 'medium',
                'description' => 'Query appointment by customer name (for anonymous calls)',
                'category' => 'appointment_management',
                'added_version' => 'V85',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'get_alternatives' => [
                'status' => 'live',
                'priority' => 'high',
                'description' => 'Get alternative time slots when desired slot unavailable',
                'category' => 'booking',
                'added_version' => 'V1',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'list_services' => [
                'status' => 'live',
                'priority' => 'high',
                'description' => 'List all available services with prices and durations',
                'category' => 'service_management',
                'added_version' => 'V1',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'get_available_services' => [
                'status' => 'live',
                'priority' => 'high',
                'description' => 'Alias for list_services',
                'category' => 'service_management',
                'added_version' => 'V50',
                'alias_for' => 'list_services',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'cancel_appointment' => [
                'status' => 'live',
                'priority' => 'high',
                'description' => 'Cancel existing appointment',
                'category' => 'appointment_management',
                'added_version' => 'V1',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'reschedule_appointment' => [
                'status' => 'live',
                'priority' => 'high',
                'description' => 'Reschedule appointment to new date/time',
                'category' => 'appointment_management',
                'added_version' => 'V1',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'request_callback' => [
                'status' => 'deprecated',
                'priority' => 'low',
                'description' => 'Request callback (deprecated, no longer used)',
                'category' => 'utility',
                'added_version' => 'V1',
                'deprecated_version' => 'V50',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'find_next_available' => [
                'status' => 'live',
                'priority' => 'medium',
                'description' => 'Find next available slot for service',
                'category' => 'booking',
                'added_version' => 'V1',
                'endpoint' => '/api/webhooks/retell/function'
            ],
            'initialize_call' => [
                'status' => 'live',
                'priority' => 'high',
                'description' => 'Initialize call session and context',
                'category' => 'call_management',
                'added_version' => 'V39',
                'endpoint' => '/api/webhooks/retell/function'
            ],
        ];
    }

    /**
     * Extract all function schemas with parameters, returns, and metadata
     */
    public function extractAll(): array
    {
        $functions = [];

        foreach ($this->functionMetadata as $functionName => $metadata) {
            try {
                $schema = $this->extractFunctionSchema($functionName, $metadata);
                if ($schema) {
                    $functions[] = $schema;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to extract schema for function: {$functionName}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $functions;
    }

    /**
     * Extract schema for a single function
     */
    private function extractFunctionSchema(string $functionName, array $metadata): ?array
    {
        // Find the handler method
        $handlerMethod = $this->findHandlerMethod($functionName);

        if (!$handlerMethod) {
            Log::warning("Handler method not found for function: {$functionName}");
            return null;
        }

        // Extract parameters from method signature
        $parameters = $this->extractParameters($handlerMethod);

        // Extract return type from doc block
        $returnType = $this->extractReturnType($handlerMethod);

        // Extract description from doc block
        $description = $this->extractDescription($handlerMethod) ?? $metadata['description'];

        // Build complete schema
        return [
            'name' => $functionName,
            'status' => $metadata['status'],
            'priority' => $metadata['priority'],
            'category' => $metadata['category'],
            'description' => $description,
            'handler_method' => $handlerMethod->getName(),
            'handler_file' => 'app/Http/Controllers/RetellFunctionCallHandler.php',
            'handler_line' => $handlerMethod->getStartLine(),
            'endpoint' => $metadata['endpoint'],
            'parameters' => $parameters,
            'returns' => $returnType,
            'metadata' => array_filter([
                'added_version' => $metadata['added_version'] ?? null,
                'deprecated_version' => $metadata['deprecated_version'] ?? null,
                'replacement' => $metadata['replacement'] ?? null,
                'alias_for' => $metadata['alias_for'] ?? null,
            ])
        ];
    }

    /**
     * Find the private handler method for a function
     * Maps function names to method names (e.g., check_availability → checkAvailability)
     */
    private function findHandlerMethod(string $functionName): ?ReflectionMethod
    {
        // Convert snake_case to camelCase
        $methodName = lcfirst(str_replace('_', '', ucwords($functionName, '_')));

        // Special cases from the match statement
        $methodMap = [
            'cancel_appointment' => 'handleCancellationAttempt',
            'reschedule_appointment' => 'handleRescheduleAttempt',
            'request_callback' => 'handleCallbackRequest',
            'find_next_available' => 'handleFindNextAvailable',
            'parse_date' => 'handleParseDate',
            'initialize_call' => 'initializeCall',
            'query_appointment_by_name' => 'queryAppointmentByName',
            'get_available_services' => 'listServices', // Alias
            'start_booking' => 'startBooking',
            'confirm_booking' => 'confirmBooking',
        ];

        $actualMethodName = $methodMap[$functionName] ?? $methodName;

        try {
            return $this->reflector->getMethod($actualMethodName);
        } catch (\ReflectionException $e) {
            return null;
        }
    }

    /**
     * Extract parameters from method signature
     */
    private function extractParameters(ReflectionMethod $method): array
    {
        $params = [];

        foreach ($method->getParameters() as $param) {
            // Skip $callId parameter (always present, managed by framework)
            if ($param->getName() === 'callId') {
                continue;
            }

            // For array $params parameter, we need to infer structure from doc block
            if ($param->getName() === 'params' || $param->getName() === 'parameters') {
                // Extract from doc block @param
                $docParams = $this->extractParamsFromDocBlock($method);
                if (!empty($docParams)) {
                    return $docParams;
                }
            }

            $params[] = [
                'name' => $param->getName(),
                'type' => $param->getType() ? $param->getType()->getName() : 'mixed',
                'required' => !$param->isOptional(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }

        return $params;
    }

    /**
     * Extract parameter structure from method doc block
     * Looks for @param array comments describing the structure
     */
    private function extractParamsFromDocBlock(ReflectionMethod $method): array
    {
        $docComment = $method->getDocComment();
        if (!$docComment) {
            return [];
        }

        // Extract parameter descriptions from doc block
        // Format: @param array $params ['key' => 'description']

        // TODO: Implement more sophisticated doc block parsing
        // For now, return empty and rely on frontend hardcoded schemas

        return [];
    }

    /**
     * Extract return type from doc block
     */
    private function extractReturnType(ReflectionMethod $method): array
    {
        $returnType = $method->getReturnType();

        $type = [
            'type' => $returnType ? $returnType->getName() : 'mixed',
            'description' => 'JSON response with success/error status'
        ];

        // Extract @return from doc block for more details
        $docComment = $method->getDocComment();
        if ($docComment && preg_match('/@return\s+(\S+)\s+(.*)/', $docComment, $matches)) {
            $type['type'] = $matches[1];
            $type['description'] = trim($matches[2]);
        }

        return $type;
    }

    /**
     * Extract description from method doc block
     */
    private function extractDescription(ReflectionMethod $method): ?string
    {
        $docComment = $method->getDocComment();
        if (!$docComment) {
            return null;
        }

        // Extract first line of doc block (main description)
        $lines = explode("\n", $docComment);
        $description = '';

        foreach ($lines as $line) {
            $line = trim($line, "/* \t\n\r");

            // Skip empty lines and annotation lines
            if (empty($line) || str_starts_with($line, '@')) {
                continue;
            }

            $description .= $line . ' ';

            // Stop at first paragraph break
            if (str_contains($line, '.') || str_contains($line, '?')) {
                break;
            }
        }

        return trim($description) ?: null;
    }

    /**
     * Get schema for a single function by name
     */
    public function extractOne(string $functionName): ?array
    {
        if (!isset($this->functionMetadata[$functionName])) {
            return null;
        }

        return $this->extractFunctionSchema($functionName, $this->functionMetadata[$functionName]);
    }

    /**
     * Get summary statistics
     */
    public function getStatistics(): array
    {
        $all = $this->extractAll();

        $stats = [
            'total_functions' => count($all),
            'by_status' => [],
            'by_priority' => [],
            'by_category' => [],
        ];

        foreach ($all as $func) {
            $status = $func['status'];
            $priority = $func['priority'];
            $category = $func['category'];

            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;
            $stats['by_priority'][$priority] = ($stats['by_priority'][$priority] ?? 0) + 1;
            $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + 1;
        }

        return $stats;
    }
}
