<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Retell\FunctionSchemaExtractor;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for Retell Function Schema Export
 *
 * Purpose: Provide real-time function schemas for documentation and automation
 * Use Case: Interactive documentation loads schemas dynamically from backend
 *
 * Endpoints:
 * - GET /api/admin/retell/functions/schema - Get all function schemas
 * - GET /api/admin/retell/functions/schema/{name} - Get single function schema
 * - GET /api/admin/retell/functions/statistics - Get schema statistics
 */
class RetellFunctionSchemaController extends Controller
{
    private FunctionSchemaExtractor $extractor;

    public function __construct(FunctionSchemaExtractor $extractor)
    {
        $this->extractor = $extractor;
    }

    /**
     * Get all function schemas
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $schemas = $this->extractor->extractAll();

            return response()->json([
                'success' => true,
                'data' => [
                    'functions' => $schemas,
                    'count' => count($schemas),
                    'generated_at' => now()->toIso8601String(),
                    'source' => 'RetellFunctionCallHandler.php (live extraction)',
                    'version' => 'V50'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'schema_extraction_failed',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get single function schema by name
     *
     * @param string $name Function name (e.g., check_availability)
     * @return JsonResponse
     */
    public function show(string $name): JsonResponse
    {
        try {
            $schema = $this->extractor->extractOne($name);

            if (!$schema) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'function_not_found',
                        'message' => "Function '{$name}' not found"
                    ]
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $schema
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'schema_extraction_failed',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get schema statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->extractor->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'statistics_generation_failed',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Export schemas in Retell AI compatible format
     * Format: Can be directly imported into Retell AI agent configuration
     *
     * @return JsonResponse
     */
    public function exportRetellFormat(): JsonResponse
    {
        try {
            $schemas = $this->extractor->extractAll();

            // Transform to Retell AI tool format
            $tools = array_map(function ($schema) {
                return [
                    'name' => $schema['name'],
                    'description' => $schema['description'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $this->transformParameters($schema['parameters']),
                        'required' => $this->extractRequiredParams($schema['parameters'])
                    ]
                ];
            }, $schemas);

            return response()->json([
                'success' => true,
                'data' => [
                    'tools' => $tools,
                    'format' => 'retell_ai_agent_tools',
                    'version' => 'V50',
                    'generated_at' => now()->toIso8601String()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'export_failed',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Transform parameters to Retell AI JSON Schema format
     */
    private function transformParameters(array $parameters): array
    {
        $properties = [];

        foreach ($parameters as $param) {
            $properties[$param['name']] = [
                'type' => $this->mapTypeToJsonSchema($param['type']),
                'description' => $param['description'] ?? ''
            ];
        }

        return $properties;
    }

    /**
     * Extract required parameter names
     */
    private function extractRequiredParams(array $parameters): array
    {
        return array_values(array_filter(
            array_column($parameters, 'name'),
            fn($name) => collect($parameters)->firstWhere('name', $name)['required'] ?? false
        ));
    }

    /**
     * Map PHP types to JSON Schema types
     */
    private function mapTypeToJsonSchema(string $phpType): string
    {
        return match ($phpType) {
            'int', 'integer' => 'integer',
            'float', 'double' => 'number',
            'bool', 'boolean' => 'boolean',
            'array' => 'array',
            'object', 'stdClass' => 'object',
            default => 'string'
        };
    }
}
