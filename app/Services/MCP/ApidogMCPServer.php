<?php

namespace App\Services\MCP;

use App\Services\MCP\Contracts\ExternalMCPProvider;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ApidogMCPServer implements ExternalMCPProvider
{
    protected string $name = 'apidog';
    protected string $version = '1.0.0';
    protected array $capabilities = [
        'api_specification_management',
        'code_generation',
        'api_documentation',
        'request_validation',
        'endpoint_discovery',
        'schema_management'
    ];

    protected string $cachePrefix = 'apidog_specs_';

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Get tool definitions for Apidog operations
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'fetch_api_spec',
                'description' => 'Fetch API specification from Apidog project or OpenAPI URL',
                'category' => 'specification',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'source' => [
                            'type' => 'string',
                            'description' => 'Source URL (Apidog project URL or OpenAPI spec URL)'
                        ],
                        'project_id' => [
                            'type' => 'string',
                            'description' => 'Apidog project ID (optional)'
                        ],
                        'format' => [
                            'type' => 'string',
                            'enum' => ['openapi', 'swagger', 'apidog'],
                            'default' => 'openapi'
                        ]
                    ],
                    'required' => ['source']
                ]
            ],
            [
                'name' => 'list_endpoints',
                'description' => 'List all endpoints from cached API specification',
                'category' => 'discovery',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'spec_id' => [
                            'type' => 'string',
                            'description' => 'ID of cached specification'
                        ],
                        'tag' => [
                            'type' => 'string',
                            'description' => 'Filter by tag/category'
                        ]
                    ],
                    'required' => ['spec_id']
                ]
            ],
            [
                'name' => 'get_endpoint_details',
                'description' => 'Get detailed information about a specific endpoint',
                'category' => 'discovery',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'spec_id' => [
                            'type' => 'string',
                            'description' => 'ID of cached specification'
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'API endpoint path'
                        ],
                        'method' => [
                            'type' => 'string',
                            'enum' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS']
                        ]
                    ],
                    'required' => ['spec_id', 'path', 'method']
                ]
            ],
            [
                'name' => 'generate_code',
                'description' => 'Generate code based on API specification',
                'category' => 'generation',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'spec_id' => [
                            'type' => 'string',
                            'description' => 'ID of cached specification'
                        ],
                        'language' => [
                            'type' => 'string',
                            'enum' => ['javascript', 'typescript', 'php', 'python', 'java', 'go']
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['client', 'server', 'models', 'controllers', 'tests']
                        ],
                        'endpoints' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Specific endpoints to generate code for'
                        ]
                    ],
                    'required' => ['spec_id', 'language', 'type']
                ]
            ],
            [
                'name' => 'validate_request',
                'description' => 'Validate a request against API specification',
                'category' => 'validation',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'spec_id' => [
                            'type' => 'string',
                            'description' => 'ID of cached specification'
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'API endpoint path'
                        ],
                        'method' => [
                            'type' => 'string',
                            'description' => 'HTTP method'
                        ],
                        'request' => [
                            'type' => 'object',
                            'description' => 'Request to validate (headers, body, query params)'
                        ]
                    ],
                    'required' => ['spec_id', 'path', 'method', 'request']
                ]
            ],
            [
                'name' => 'list_cached_specs',
                'description' => 'List all cached API specifications',
                'category' => 'management',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ],
            [
                'name' => 'import_to_filament',
                'description' => 'Generate Filament resource from API specification',
                'category' => 'generation',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'spec_id' => [
                            'type' => 'string',
                            'description' => 'ID of cached specification'
                        ],
                        'resource_name' => [
                            'type' => 'string',
                            'description' => 'Name for the Filament resource'
                        ],
                        'endpoints' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Endpoints to include in resource'
                        ]
                    ],
                    'required' => ['spec_id', 'resource_name']
                ]
            ]
        ];
    }

    /**
     * Execute an Apidog operation
     */
    public function executeTool(string $tool, array $arguments): array
    {
        Log::debug("Executing Apidog tool: {$tool}", $arguments);

        try {
            switch ($tool) {
                case 'fetch_api_spec':
                    return $this->fetchApiSpec($arguments);
                
                case 'list_endpoints':
                    return $this->listEndpoints($arguments);
                
                case 'get_endpoint_details':
                    return $this->getEndpointDetails($arguments);
                
                case 'generate_code':
                    return $this->generateCode($arguments);
                
                case 'validate_request':
                    return $this->validateRequest($arguments);
                
                case 'list_cached_specs':
                    return $this->listCachedSpecs();
                
                case 'import_to_filament':
                    return $this->importToFilament($arguments);
                
                default:
                    return [
                        'success' => false,
                        'error' => "Unknown Apidog tool: {$tool}",
                        'data' => null
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Apidog operation failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Fetch API specification from source
     */
    protected function fetchApiSpec(array $arguments): array
    {
        $source = $arguments['source'];
        $projectId = $arguments['project_id'] ?? null;
        $format = $arguments['format'] ?? 'openapi';

        try {
            // Fetch the specification
            $response = Http::withHeaders([
                'Accept' => 'application/json, application/yaml, text/yaml',
                'User-Agent' => 'AskProAI-Apidog-MCP/1.0'
            ])->get($source);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch API spec: {$response->status()}");
            }

            $spec = $response->json();
            
            // Generate spec ID
            $specId = $projectId ?? md5($source);
            
            // Cache the specification
            Cache::put($this->cachePrefix . $specId, $spec, now()->addDays(7));
            
            // Also store in storage for persistence
            Storage::put("apidog/specs/{$specId}.json", json_encode($spec, JSON_PRETTY_PRINT));

            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'spec_id' => $specId,
                    'title' => $spec['info']['title'] ?? 'Unknown',
                    'version' => $spec['info']['version'] ?? '1.0.0',
                    'description' => $spec['info']['description'] ?? '',
                    'servers' => $spec['servers'] ?? [],
                    'paths' => array_keys($spec['paths'] ?? [])
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * List endpoints from cached specification
     */
    protected function listEndpoints(array $arguments): array
    {
        $specId = $arguments['spec_id'];
        $tag = $arguments['tag'] ?? null;

        $spec = Cache::get($this->cachePrefix . $specId);
        if (!$spec && Storage::exists("apidog/specs/{$specId}.json")) {
            $spec = json_decode(Storage::get("apidog/specs/{$specId}.json"), true);
            Cache::put($this->cachePrefix . $specId, $spec, now()->addDays(7));
        }

        if (!$spec) {
            return [
                'success' => false,
                'error' => 'Specification not found',
                'data' => null
            ];
        }

        $endpoints = [];
        
        foreach ($spec['paths'] ?? [] as $path => $pathItem) {
            foreach ($pathItem as $method => $operation) {
                if ($method === 'parameters') continue;
                
                if (!$tag || (isset($operation['tags']) && in_array($tag, $operation['tags']))) {
                    $endpoints[] = [
                        'path' => $path,
                        'method' => strtoupper($method),
                        'summary' => $operation['summary'] ?? '',
                        'description' => $operation['description'] ?? '',
                        'tags' => $operation['tags'] ?? [],
                        'operationId' => $operation['operationId'] ?? null
                    ];
                }
            }
        }

        return [
            'success' => true,
            'error' => null,
            'data' => $endpoints
        ];
    }

    /**
     * Get detailed endpoint information
     */
    protected function getEndpointDetails(array $arguments): array
    {
        $specId = $arguments['spec_id'];
        $path = $arguments['path'];
        $method = strtolower($arguments['method']);

        $spec = Cache::get($this->cachePrefix . $specId);
        if (!$spec && Storage::exists("apidog/specs/{$specId}.json")) {
            $spec = json_decode(Storage::get("apidog/specs/{$specId}.json"), true);
            Cache::put($this->cachePrefix . $specId, $spec, now()->addDays(7));
        }

        if (!$spec) {
            return [
                'success' => false,
                'error' => 'Specification not found',
                'data' => null
            ];
        }

        $pathItem = $spec['paths'][$path] ?? null;
        $operation = $pathItem[$method] ?? null;

        if (!$operation) {
            return [
                'success' => false,
                'error' => "Endpoint {$arguments['method']} {$path} not found",
                'data' => null
            ];
        }

        // Include global parameters if any
        $parameters = array_merge(
            $pathItem['parameters'] ?? [],
            $operation['parameters'] ?? []
        );

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'path' => $path,
                'method' => strtoupper($method),
                'operation' => array_merge($operation, ['parameters' => $parameters]),
                'security' => $operation['security'] ?? $spec['security'] ?? [],
                'servers' => $operation['servers'] ?? $spec['servers'] ?? []
            ]
        ];
    }

    /**
     * Generate code from specification
     */
    protected function generateCode(array $arguments): array
    {
        $specId = $arguments['spec_id'];
        $language = $arguments['language'];
        $type = $arguments['type'];
        $endpoints = $arguments['endpoints'] ?? [];

        $spec = Cache::get($this->cachePrefix . $specId);
        if (!$spec && Storage::exists("apidog/specs/{$specId}.json")) {
            $spec = json_decode(Storage::get("apidog/specs/{$specId}.json"), true);
        }

        if (!$spec) {
            return [
                'success' => false,
                'error' => 'Specification not found',
                'data' => null
            ];
        }

        $code = '';

        if ($language === 'php' && $type === 'client') {
            $code = $this->generatePHPClient($spec, $endpoints);
        } elseif ($language === 'php' && $type === 'models') {
            $code = $this->generatePHPModels($spec);
        } elseif ($language === 'php' && $type === 'controllers') {
            $code = $this->generatePHPControllers($spec, $endpoints);
        } else {
            $code = "// Code generation for {$language} {$type} not implemented yet\n";
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'code' => $code,
                'language' => $language,
                'type' => $type
            ]
        ];
    }

    /**
     * Validate request against specification
     */
    protected function validateRequest(array $arguments): array
    {
        $specId = $arguments['spec_id'];
        $path = $arguments['path'];
        $method = strtolower($arguments['method']);
        $request = $arguments['request'];

        $spec = Cache::get($this->cachePrefix . $specId);
        if (!$spec) {
            return [
                'success' => false,
                'error' => 'Specification not found',
                'data' => null
            ];
        }

        $operation = $spec['paths'][$path][$method] ?? null;
        if (!$operation) {
            return [
                'success' => false,
                'error' => "Endpoint {$arguments['method']} {$path} not found",
                'data' => null
            ];
        }

        $errors = [];
        $warnings = [];

        // Validate parameters
        foreach ($operation['parameters'] ?? [] as $param) {
            if ($param['required'] ?? false) {
                $value = $request[$param['in']][$param['name']] ?? null;
                if ($value === null) {
                    $errors[] = "Missing required parameter: {$param['name']} in {$param['in']}";
                }
            }
        }

        // Validate request body
        if (isset($operation['requestBody']) && ($operation['requestBody']['required'] ?? false)) {
            if (!isset($request['body'])) {
                $errors[] = 'Missing required request body';
            }
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'valid' => empty($errors),
                'errors' => $errors,
                'warnings' => $warnings
            ]
        ];
    }

    /**
     * List all cached specifications
     */
    protected function listCachedSpecs(): array
    {
        $specs = [];
        
        // Get from cache
        $cacheKeys = Cache::get($this->cachePrefix . 'keys', []);
        
        // Get from storage
        if (Storage::exists('apidog/specs')) {
            $files = Storage::files('apidog/specs');
            foreach ($files as $file) {
                if (str_ends_with($file, '.json')) {
                    $specId = basename($file, '.json');
                    $spec = json_decode(Storage::get($file), true);
                    $specs[] = [
                        'id' => $specId,
                        'title' => $spec['info']['title'] ?? 'Unknown',
                        'version' => $spec['info']['version'] ?? '1.0.0',
                        'description' => $spec['info']['description'] ?? ''
                    ];
                }
            }
        }

        return [
            'success' => true,
            'error' => null,
            'data' => $specs
        ];
    }

    /**
     * Import API specification to Filament resource
     */
    protected function importToFilament(array $arguments): array
    {
        $specId = $arguments['spec_id'];
        $resourceName = $arguments['resource_name'];
        $endpoints = $arguments['endpoints'] ?? [];

        $spec = Cache::get($this->cachePrefix . $specId);
        if (!$spec) {
            return [
                'success' => false,
                'error' => 'Specification not found',
                'data' => null
            ];
        }

        $code = $this->generateFilamentResource($spec, $resourceName, $endpoints);

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'code' => $code,
                'resource_name' => $resourceName,
                'file_path' => "app/Filament/Resources/{$resourceName}Resource.php"
            ]
        ];
    }

    /**
     * Generate PHP client code
     */
    protected function generatePHPClient(array $spec, array $endpoints): string
    {
        $className = str_replace(' ', '', ucwords($spec['info']['title'] ?? 'Api')) . 'Client';
        $baseUrl = $spec['servers'][0]['url'] ?? 'https://api.example.com';
        
        $code = "<?php\n\n";
        $code .= "namespace App\\Services\\ApiClients;\n\n";
        $code .= "use Illuminate\\Support\\Facades\\Http;\n";
        $code .= "use Illuminate\\Http\\Client\\Response;\n\n";
        $code .= "class {$className}\n{\n";
        $code .= "    protected string \$baseUrl;\n";
        $code .= "    protected array \$headers = [];\n\n";
        $code .= "    public function __construct()\n    {\n";
        $code .= "        \$this->baseUrl = config('services.apidog.base_url', '{$baseUrl}');\n";
        $code .= "    }\n\n";
        $code .= "    public function withHeaders(array \$headers): self\n    {\n";
        $code .= "        \$this->headers = array_merge(\$this->headers, \$headers);\n";
        $code .= "        return \$this;\n";
        $code .= "    }\n\n";

        foreach ($spec['paths'] ?? [] as $path => $pathItem) {
            foreach ($pathItem as $method => $operation) {
                if ($method === 'parameters') continue;
                if (!empty($endpoints) && !in_array($path, $endpoints)) continue;

                $methodName = $operation['operationId'] ?? $this->generateMethodName($method, $path);
                $params = $this->extractParameters($operation);
                
                $code .= "    /**\n";
                $code .= "     * " . ($operation['summary'] ?? "Execute {$method} {$path}") . "\n";
                if (isset($operation['description'])) {
                    $code .= "     * " . str_replace("\n", "\n     * ", $operation['description']) . "\n";
                }
                $code .= "     */\n";
                $code .= "    public function {$methodName}(" . $this->generatePHPParams($params) . "): Response\n";
                $code .= "    {\n";
                $code .= $this->generatePHPMethodBody($method, $path, $params);
                $code .= "    }\n\n";
            }
        }

        $code .= "}\n";
        return $code;
    }

    /**
     * Generate PHP models from specification
     */
    protected function generatePHPModels(array $spec): string
    {
        $code = "<?php\n\n";
        $code .= "namespace App\\Models\\ApiDog;\n\n";

        foreach ($spec['components']['schemas'] ?? [] as $name => $schema) {
            $code .= "class {$name}\n{\n";
            
            // Properties
            foreach ($schema['properties'] ?? [] as $propName => $propSchema) {
                $phpType = $this->getPHPType($propSchema);
                $nullable = !in_array($propName, $schema['required'] ?? []) ? '?' : '';
                
                $code .= "    public {$nullable}{$phpType} \${$propName};\n";
            }
            
            $code .= "\n";
            
            // Constructor
            $code .= "    public function __construct(array \$data = [])\n    {\n";
            foreach ($schema['properties'] ?? [] as $propName => $propSchema) {
                $code .= "        \$this->{$propName} = \$data['{$propName}'] ?? null;\n";
            }
            $code .= "    }\n\n";
            
            // toArray method
            $code .= "    public function toArray(): array\n    {\n";
            $code .= "        return [\n";
            foreach ($schema['properties'] ?? [] as $propName => $propSchema) {
                $code .= "            '{$propName}' => \$this->{$propName},\n";
            }
            $code .= "        ];\n";
            $code .= "    }\n";
            
            $code .= "}\n\n";
        }

        return $code;
    }

    /**
     * Generate PHP controllers from specification
     */
    protected function generatePHPControllers(array $spec, array $endpoints): string
    {
        $code = "<?php\n\n";
        $code .= "namespace App\\Http\\Controllers\\Api;\n\n";
        $code .= "use App\\Http\\Controllers\\Controller;\n";
        $code .= "use Illuminate\\Http\\Request;\n";
        $code .= "use Illuminate\\Http\\JsonResponse;\n\n";
        
        $controllerName = str_replace(' ', '', ucwords($spec['info']['title'] ?? 'Api')) . 'Controller';
        
        $code .= "class {$controllerName} extends Controller\n{\n";

        foreach ($spec['paths'] ?? [] as $path => $pathItem) {
            foreach ($pathItem as $method => $operation) {
                if ($method === 'parameters') continue;
                if (!empty($endpoints) && !in_array($path, $endpoints)) continue;

                $methodName = $operation['operationId'] ?? $this->generateMethodName($method, $path);
                
                $code .= "    /**\n";
                $code .= "     * " . ($operation['summary'] ?? "Handle {$method} {$path}") . "\n";
                $code .= "     */\n";
                $code .= "    public function {$methodName}(Request \$request): JsonResponse\n";
                $code .= "    {\n";
                $code .= "        // TODO: Implement {$methodName}\n";
                $code .= "        return response()->json(['message' => 'Not implemented']);\n";
                $code .= "    }\n\n";
            }
        }

        $code .= "}\n";
        return $code;
    }

    /**
     * Generate Filament resource from specification
     */
    protected function generateFilamentResource(array $spec, string $resourceName, array $endpoints): string
    {
        // This would generate a complete Filament resource
        // For brevity, returning a basic template
        $code = "<?php\n\n";
        $code .= "namespace App\\Filament\\Resources;\n\n";
        $code .= "use Filament\\Resources\\Resource;\n";
        $code .= "use Filament\\Forms;\n";
        $code .= "use Filament\\Tables;\n\n";
        $code .= "class {$resourceName}Resource extends Resource\n{\n";
        $code .= "    // Generated from Apidog specification\n";
        $code .= "    // TODO: Implement resource based on API endpoints\n";
        $code .= "}\n";
        
        return $code;
    }

    /**
     * Helper methods
     */
    protected function generateMethodName(string $method, string $path): string
    {
        $parts = explode('/', trim($path, '/'));
        $name = $method;
        foreach ($parts as $part) {
            if (!str_starts_with($part, '{')) {
                $name .= ucfirst($part);
            }
        }
        return lcfirst(str_replace(['-', '_'], '', $name));
    }

    protected function extractParameters(array $operation): array
    {
        $params = [];
        foreach ($operation['parameters'] ?? [] as $param) {
            $params[] = [
                'name' => $param['name'],
                'in' => $param['in'],
                'required' => $param['required'] ?? false,
                'type' => $param['schema']['type'] ?? 'string'
            ];
        }
        return $params;
    }

    protected function generatePHPParams(array $params): string
    {
        $phpParams = [];
        foreach ($params as $param) {
            if ($param['in'] === 'path' || $param['required']) {
                $phpParams[] = "string \${$param['name']}";
            }
        }
        if (count($params) > count($phpParams)) {
            $phpParams[] = 'array $options = []';
        }
        return implode(', ', $phpParams);
    }

    protected function generatePHPMethodBody(string $method, string $path, array $params): string
    {
        $code = "        \$url = \$this->baseUrl . '{$path}';\n";
        
        // Replace path parameters
        foreach ($params as $param) {
            if ($param['in'] === 'path') {
                $code .= "        \$url = str_replace('{{$param['name']}}', \${$param['name']}, \$url);\n";
            }
        }
        
        $code .= "\n        return Http::withHeaders(\$this->headers)\n";
        $code .= "            ->{$method}(\$url";
        
        if (in_array($method, ['post', 'put', 'patch'])) {
            $code .= ", \$options['body'] ?? []";
        } elseif ($method === 'get') {
            $code .= ", \$options['query'] ?? []";
        }
        
        $code .= ");\n";
        
        return $code;
    }

    protected function getPHPType(array $schema): string
    {
        switch ($schema['type'] ?? 'string') {
            case 'integer':
                return 'int';
            case 'number':
                return 'float';
            case 'boolean':
                return 'bool';
            case 'array':
                return 'array';
            case 'object':
                return 'object';
            default:
                return 'string';
        }
    }

    /**
     * Check if external server is running
     */
    public function isExternalServerRunning(): bool
    {
        $result = Process::run('pgrep -f "apidog-mcp/index.js"');
        return $result->successful() && !empty(trim($result->output()));
    }

    /**
     * Start the external server
     */
    public function startExternalServer(): bool
    {
        $config = config('mcp-external.external_servers.apidog');
        
        if (!$config || !$config['enabled']) {
            return false;
        }

        $env = array_merge($_ENV, $config['env'] ?? []);
        
        $result = Process::env($env)
            ->path(dirname($config['args'][0]))
            ->run($config['command'] . ' ' . implode(' ', $config['args']) . ' > /dev/null 2>&1 &');

        return $result->successful();
    }

    /**
     * Get server configuration
     */
    public function getConfiguration(): array
    {
        return [
            'external_server' => config('mcp-external.external_servers.apidog'),
            'is_running' => $this->isExternalServerRunning(),
            'cached_specs' => count($this->listCachedSpecs()['data'] ?? [])
        ];
    }
}