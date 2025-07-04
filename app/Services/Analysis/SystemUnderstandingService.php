<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use App\Services\MCP\MCPAutoDiscoveryService;

/**
 * System Understanding Service
 * 
 * Analyzes existing implementations to understand:
 * - What already exists in the codebase
 * - How it actually works
 * - What the purpose and intention is
 * 
 * This prevents breaking changes and ensures new code integrates properly.
 */
class SystemUnderstandingService
{
    protected MCPAutoDiscoveryService $mcpDiscovery;
    
    /**
     * Cache duration for analysis results (1 hour)
     */
    const ANALYSIS_CACHE_TTL = 3600;
    
    /**
     * Patterns to identify service purposes
     */
    protected array $purposePatterns = [
        'booking' => ['appointment', 'booking', 'schedule', 'calendar'],
        'communication' => ['email', 'sms', 'whatsapp', 'notification'],
        'integration' => ['webhook', 'api', 'sync', 'import', 'export'],
        'payment' => ['payment', 'invoice', 'billing', 'stripe'],
        'data_management' => ['repository', 'crud', 'model', 'database'],
        'authentication' => ['auth', 'login', 'permission', 'role'],
        'monitoring' => ['log', 'metric', 'health', 'status', 'monitor']
    ];
    
    public function __construct(MCPAutoDiscoveryService $mcpDiscovery)
    {
        $this->mcpDiscovery = $mcpDiscovery;
    }
    
    /**
     * Analyze a service or component to understand its implementation
     * 
     * @param string $component Class name or file path
     * @return array Detailed analysis results
     */
    public function analyzeComponent(string $component): array
    {
        $cacheKey = 'system:understanding:' . md5($component);
        
        return Cache::remember($cacheKey, self::ANALYSIS_CACHE_TTL, function () use ($component) {
            $analysis = [
                'component' => $component,
                'exists' => false,
                'purpose' => null,
                'implementation' => [],
                'dependencies' => [],
                'data_flow' => [],
                'integration_points' => [],
                'potential_impacts' => [],
                'recommendations' => [],
                'mcp_opportunities' => []
            ];
            
            try {
                // Determine if it's a class or file path
                if (class_exists($component)) {
                    $analysis = $this->analyzeClass($component, $analysis);
                } elseif (File::exists($component)) {
                    $analysis = $this->analyzeFile($component, $analysis);
                } else {
                    // Try to find the class file
                    $possiblePath = $this->findClassFile($component);
                    if ($possiblePath) {
                        $analysis = $this->analyzeFile($possiblePath, $analysis);
                    }
                }
                
                // Analyze MCP opportunities
                $analysis['mcp_opportunities'] = $this->analyzeMCPOpportunities($analysis);
                
                // Generate recommendations
                $analysis['recommendations'] = $this->generateRecommendations($analysis);
                
            } catch (\Exception $e) {
                Log::error('System understanding analysis failed', [
                    'component' => $component,
                    'error' => $e->getMessage()
                ]);
                
                $analysis['error'] = $e->getMessage();
            }
            
            return $analysis;
        });
    }
    
    /**
     * Analyze a class using reflection
     */
    protected function analyzeClass(string $className, array $analysis): array
    {
        $analysis['exists'] = true;
        $analysis['type'] = 'class';
        
        $reflection = new ReflectionClass($className);
        
        // Determine purpose
        $analysis['purpose'] = $this->determinePurpose($reflection);
        
        // Analyze methods
        $analysis['implementation']['methods'] = $this->analyzeMethods($reflection);
        
        // Analyze dependencies
        $analysis['dependencies'] = $this->analyzeDependencies($reflection);
        
        // Analyze data flow
        $analysis['data_flow'] = $this->analyzeDataFlow($reflection);
        
        // Find integration points
        $analysis['integration_points'] = $this->findIntegrationPoints($reflection);
        
        // Analyze database interactions
        $analysis['database_operations'] = $this->analyzeDatabaseOperations($reflection);
        
        // Check for existing patterns
        $analysis['patterns'] = $this->identifyPatterns($reflection);
        
        return $analysis;
    }
    
    /**
     * Analyze a file
     */
    protected function analyzeFile(string $filePath, array $analysis): array
    {
        $analysis['exists'] = true;
        $analysis['type'] = 'file';
        $analysis['file_path'] = $filePath;
        
        $content = File::get($filePath);
        
        // Extract class name if PHP file
        if (Str::endsWith($filePath, '.php')) {
            $className = $this->extractClassName($content);
            if ($className && class_exists($className)) {
                return $this->analyzeClass($className, $analysis);
            }
        }
        
        // Analyze file content
        $analysis['implementation']['lines'] = substr_count($content, "\n") + 1;
        $analysis['implementation']['size'] = strlen($content);
        
        // Find imports/dependencies
        if (Str::endsWith($filePath, '.php')) {
            $analysis['dependencies'] = $this->extractDependenciesFromCode($content);
        }
        
        return $analysis;
    }
    
    /**
     * Determine the purpose of a class
     */
    protected function determinePurpose(ReflectionClass $reflection): array
    {
        $className = $reflection->getName();
        $methods = $reflection->getMethods();
        
        $purpose = [
            'primary' => 'unknown',
            'category' => 'general',
            'description' => '',
            'confidence' => 0.0
        ];
        
        // Check class name patterns
        foreach ($this->purposePatterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($className, $pattern) !== false) {
                    $purpose['category'] = $category;
                    $purpose['confidence'] = 0.7;
                    break 2;
                }
            }
        }
        
        // Analyze method names
        $methodPurposes = [];
        foreach ($methods as $method) {
            $methodName = strtolower($method->getName());
            foreach ($this->purposePatterns as $category => $patterns) {
                foreach ($patterns as $pattern) {
                    if (stripos($methodName, $pattern) !== false) {
                        $methodPurposes[] = $category;
                    }
                }
            }
        }
        
        // Determine primary purpose from methods
        if (!empty($methodPurposes)) {
            $purposeCounts = array_count_values($methodPurposes);
            arsort($purposeCounts);
            $purpose['primary'] = key($purposeCounts);
            $purpose['confidence'] = min(0.9, $purpose['confidence'] + 0.3);
        }
        
        // Extract description from docblock
        $docComment = $reflection->getDocComment();
        if ($docComment) {
            preg_match('/\*\s+(.+?)(\n|\*)/s', $docComment, $matches);
            if (isset($matches[1])) {
                $purpose['description'] = trim($matches[1]);
            }
        }
        
        return $purpose;
    }
    
    /**
     * Analyze class methods
     */
    protected function analyzeMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && !$method->isConstructor()) {
                $methods[$method->getName()] = [
                    'visibility' => 'public',
                    'parameters' => $this->getMethodParameters($method),
                    'return_type' => $method->getReturnType() ? $method->getReturnType()->getName() : 'mixed',
                    'description' => $this->getMethodDescription($method),
                    'calls_external_api' => $this->checksForExternalAPICalls($method),
                    'database_operations' => $this->checksForDatabaseOperations($method)
                ];
            }
        }
        
        return $methods;
    }
    
    /**
     * Get method parameters
     */
    protected function getMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];
        
        foreach ($method->getParameters() as $param) {
            $parameters[$param->getName()] = [
                'type' => $param->getType() ? $param->getType()->getName() : 'mixed',
                'optional' => $param->isOptional(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            ];
        }
        
        return $parameters;
    }
    
    /**
     * Get method description from docblock
     */
    protected function getMethodDescription(ReflectionMethod $method): ?string
    {
        $doc = $method->getDocComment();
        if (!$doc) return null;
        
        preg_match('/\*\s+(.+?)(\n|\*)/s', $doc, $matches);
        return isset($matches[1]) ? trim($matches[1]) : null;
    }
    
    /**
     * Check if method makes external API calls
     */
    protected function checksForExternalAPICalls(ReflectionMethod $method): bool
    {
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        
        if (!$filename || !$startLine || !$endLine) return false;
        
        $methodCode = $this->getMethodCode($filename, $startLine, $endLine);
        
        $apiPatterns = [
            'Http::',
            'curl_',
            'file_get_contents',
            'GuzzleHttp',
            '->post(',
            '->get(',
            '->put(',
            '->delete(',
            'CalcomService',
            'RetellService'
        ];
        
        foreach ($apiPatterns as $pattern) {
            if (stripos($methodCode, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if method performs database operations
     */
    protected function checksForDatabaseOperations(ReflectionMethod $method): bool
    {
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        
        if (!$filename || !$startLine || !$endLine) return false;
        
        $methodCode = $this->getMethodCode($filename, $startLine, $endLine);
        
        $dbPatterns = [
            'DB::',
            '->save()',
            '->create(',
            '->update(',
            '->delete(',
            '->first(',
            '->get(',
            '->find(',
            '->where(',
            'Eloquent',
            'QueryBuilder'
        ];
        
        foreach ($dbPatterns as $pattern) {
            if (stripos($methodCode, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get method source code
     */
    protected function getMethodCode(string $filename, int $startLine, int $endLine): string
    {
        $lines = file($filename);
        $methodLines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
        return implode('', $methodLines);
    }
    
    /**
     * Analyze class dependencies
     */
    protected function analyzeDependencies(ReflectionClass $reflection): array
    {
        $dependencies = [
            'constructor' => [],
            'imports' => [],
            'traits' => [],
            'interfaces' => [],
            'parent' => null
        ];
        
        // Constructor dependencies
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->getType() && !$param->getType()->isBuiltin()) {
                    $dependencies['constructor'][] = $param->getType()->getName();
                }
            }
        }
        
        // Parent class
        $parent = $reflection->getParentClass();
        if ($parent) {
            $dependencies['parent'] = $parent->getName();
        }
        
        // Interfaces
        $dependencies['interfaces'] = $reflection->getInterfaceNames();
        
        // Traits
        $dependencies['traits'] = $reflection->getTraitNames();
        
        // File imports
        $filename = $reflection->getFileName();
        if ($filename) {
            $content = File::get($filename);
            $dependencies['imports'] = $this->extractDependenciesFromCode($content);
        }
        
        return $dependencies;
    }
    
    /**
     * Extract dependencies from code
     */
    protected function extractDependenciesFromCode(string $code): array
    {
        $imports = [];
        
        // Extract use statements
        preg_match_all('/^use\s+([^;]+);/m', $code, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $import) {
                $import = trim($import);
                if (!str_contains($import, ' as ')) {
                    $imports[] = $import;
                } else {
                    list($class, ) = explode(' as ', $import);
                    $imports[] = trim($class);
                }
            }
        }
        
        return array_unique($imports);
    }
    
    /**
     * Analyze data flow through the component
     */
    protected function analyzeDataFlow(ReflectionClass $reflection): array
    {
        $dataFlow = [
            'inputs' => [],
            'outputs' => [],
            'transformations' => [],
            'external_calls' => []
        ];
        
        foreach ($reflection->getMethods() as $method) {
            if (!$method->isPublic()) continue;
            
            $methodName = $method->getName();
            
            // Analyze inputs
            $params = $this->getMethodParameters($method);
            if (!empty($params)) {
                $dataFlow['inputs'][$methodName] = array_keys($params);
            }
            
            // Analyze outputs
            $returnType = $method->getReturnType();
            if ($returnType) {
                $dataFlow['outputs'][$methodName] = $returnType->getName();
            }
            
            // Check for transformations
            if (preg_match('/transform|convert|parse|format|map/i', $methodName)) {
                $dataFlow['transformations'][] = $methodName;
            }
            
            // Check for external calls
            if ($this->checksForExternalAPICalls($method)) {
                $dataFlow['external_calls'][] = $methodName;
            }
        }
        
        return $dataFlow;
    }
    
    /**
     * Find integration points with other systems
     */
    protected function findIntegrationPoints(ReflectionClass $reflection): array
    {
        $integrations = [];
        
        $integrationPatterns = [
            'retell' => ['Retell', 'retell', 'phone', 'call'],
            'calcom' => ['Calcom', 'cal.com', 'calendar', 'booking'],
            'stripe' => ['Stripe', 'payment', 'invoice'],
            'whatsapp' => ['WhatsApp', 'whatsapp', 'wa'],
            'email' => ['Mail', 'Email', 'smtp'],
            'webhook' => ['Webhook', 'webhook', 'callback']
        ];
        
        $className = $reflection->getName();
        $methods = $reflection->getMethods();
        
        foreach ($integrationPatterns as $integration => $patterns) {
            foreach ($patterns as $pattern) {
                // Check class name
                if (stripos($className, $pattern) !== false) {
                    $integrations[] = $integration;
                    break;
                }
                
                // Check method names
                foreach ($methods as $method) {
                    if (stripos($method->getName(), $pattern) !== false) {
                        $integrations[] = $integration;
                        break 2;
                    }
                }
            }
        }
        
        return array_unique($integrations);
    }
    
    /**
     * Analyze database operations
     */
    protected function analyzeDatabaseOperations(ReflectionClass $reflection): array
    {
        $operations = [
            'models' => [],
            'queries' => [],
            'transactions' => false,
            'migrations_affected' => []
        ];
        
        // Find model references
        foreach ($reflection->getMethods() as $method) {
            $filename = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();
            
            if (!$filename || !$startLine || !$endLine) continue;
            
            $methodCode = $this->getMethodCode($filename, $startLine, $endLine);
            
            // Find model usage
            preg_match_all('/(\w+)::(?:find|where|create|update|delete)/', $methodCode, $matches);
            if (isset($matches[1])) {
                foreach ($matches[1] as $model) {
                    if (class_exists("App\\Models\\$model")) {
                        $operations['models'][] = $model;
                    }
                }
            }
            
            // Check for transactions
            if (stripos($methodCode, 'DB::transaction') !== false) {
                $operations['transactions'] = true;
            }
        }
        
        $operations['models'] = array_unique($operations['models']);
        
        return $operations;
    }
    
    /**
     * Identify design patterns
     */
    protected function identifyPatterns(ReflectionClass $reflection): array
    {
        $patterns = [];
        
        $className = $reflection->getShortName();
        
        // Repository Pattern
        if (str_ends_with($className, 'Repository')) {
            $patterns[] = 'Repository';
        }
        
        // Service Pattern
        if (str_ends_with($className, 'Service')) {
            $patterns[] = 'Service';
        }
        
        // Factory Pattern
        if (str_ends_with($className, 'Factory') || $reflection->hasMethod('create')) {
            $patterns[] = 'Factory';
        }
        
        // Observer Pattern
        if (str_ends_with($className, 'Observer') || str_ends_with($className, 'Listener')) {
            $patterns[] = 'Observer';
        }
        
        // Singleton Pattern
        if ($reflection->hasMethod('getInstance')) {
            $patterns[] = 'Singleton';
        }
        
        // Strategy Pattern
        if ($reflection->implementsInterface('App\Contracts\StrategyInterface') ||
            str_ends_with($className, 'Strategy')) {
            $patterns[] = 'Strategy';
        }
        
        return $patterns;
    }
    
    /**
     * Analyze MCP opportunities
     */
    protected function analyzeMCPOpportunities(array $analysis): array
    {
        $opportunities = [];
        
        // Check for external API calls that could use MCP
        if (!empty($analysis['data_flow']['external_calls'])) {
            foreach ($analysis['data_flow']['external_calls'] as $method) {
                $opportunities[] = [
                    'method' => $method,
                    'suggestion' => 'Could use MCP server for external API calls',
                    'recommended_mcp' => $this->recommendMCPServer($analysis, $method)
                ];
            }
        }
        
        // Check for database operations that could use DatabaseMCP
        if (!empty($analysis['database_operations']['models'])) {
            $opportunities[] = [
                'area' => 'Database Operations',
                'suggestion' => 'Consider using DatabaseMCP for complex queries',
                'models' => $analysis['database_operations']['models']
            ];
        }
        
        // Check integration points
        foreach ($analysis['integration_points'] as $integration) {
            $mcpMap = [
                'retell' => 'retell',
                'calcom' => 'calcom',
                'stripe' => 'stripe',
                'whatsapp' => 'whatsapp',
                'webhook' => 'webhook'
            ];
            
            if (isset($mcpMap[$integration])) {
                $opportunities[] = [
                    'integration' => $integration,
                    'suggestion' => "Use {$mcpMap[$integration]} MCP server",
                    'priority' => 'high'
                ];
            }
        }
        
        return $opportunities;
    }
    
    /**
     * Recommend MCP server based on analysis
     */
    protected function recommendMCPServer(array $analysis, string $method): string
    {
        // Use MCP auto-discovery to find best server
        $discovery = $this->mcpDiscovery->discoverForTask($method);
        return $discovery['server'] ?? 'database';
    }
    
    /**
     * Generate recommendations based on analysis
     */
    protected function generateRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        // Check for missing error handling
        if (empty($analysis['implementation']['methods'])) {
            $recommendations[] = [
                'type' => 'implementation',
                'priority' => 'medium',
                'message' => 'Consider adding public methods to expose functionality'
            ];
        }
        
        // Check for database transaction usage
        if (!empty($analysis['database_operations']['models']) && 
            !$analysis['database_operations']['transactions']) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'high',
                'message' => 'Consider using database transactions for data consistency'
            ];
        }
        
        // Check for MCP opportunities
        if (!empty($analysis['mcp_opportunities'])) {
            $recommendations[] = [
                'type' => 'architecture',
                'priority' => 'high',
                'message' => 'Consider using MCP servers for better modularity and maintainability'
            ];
        }
        
        // Check for missing interfaces
        if (empty($analysis['dependencies']['interfaces']) && 
            str_ends_with($analysis['component'], 'Service')) {
            $recommendations[] = [
                'type' => 'design',
                'priority' => 'medium',
                'message' => 'Consider implementing an interface for better testability'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Find class file path
     */
    protected function findClassFile(string $className): ?string
    {
        $className = ltrim($className, '\\');
        $className = str_replace('\\', '/', $className);
        
        $possiblePaths = [
            app_path(str_replace('App/', '', $className) . '.php'),
            base_path($className . '.php'),
            base_path('src/' . $className . '.php')
        ];
        
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Extract class name from PHP file content
     */
    protected function extractClassName(string $content): ?string
    {
        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch);
        $namespace = isset($namespaceMatch[1]) ? trim($namespaceMatch[1]) : '';
        
        // Extract class name
        preg_match('/class\s+(\w+)/', $content, $classMatch);
        $className = isset($classMatch[1]) ? trim($classMatch[1]) : '';
        
        if ($className) {
            return $namespace ? $namespace . '\\' . $className : $className;
        }
        
        return null;
    }
    
    /**
     * Analyze impact of changes to a component
     */
    public function analyzeImpact(string $component, array $proposedChanges): array
    {
        $currentAnalysis = $this->analyzeComponent($component);
        
        $impact = [
            'component' => $component,
            'risk_level' => 'low',
            'affected_components' => [],
            'breaking_changes' => [],
            'recommendations' => [],
            'rollback_plan' => []
        ];
        
        // Find components that depend on this one
        $dependents = $this->findDependentComponents($component);
        $impact['affected_components'] = $dependents;
        
        // Analyze proposed changes
        foreach ($proposedChanges as $change) {
            if ($change['type'] === 'method_removal' && 
                isset($currentAnalysis['implementation']['methods'][$change['method']])) {
                $impact['breaking_changes'][] = [
                    'type' => 'method_removal',
                    'method' => $change['method'],
                    'used_by' => $this->findMethodUsage($component, $change['method'])
                ];
                $impact['risk_level'] = 'high';
            }
            
            if ($change['type'] === 'signature_change') {
                $impact['breaking_changes'][] = [
                    'type' => 'signature_change',
                    'method' => $change['method'],
                    'change' => $change['details']
                ];
                $impact['risk_level'] = 'medium';
            }
        }
        
        // Generate rollback plan
        $impact['rollback_plan'] = $this->generateRollbackPlan($component, $proposedChanges);
        
        return $impact;
    }
    
    /**
     * Find components that depend on the given component
     */
    protected function findDependentComponents(string $component): array
    {
        // This would scan the codebase for usages
        // For now, return empty array
        return [];
    }
    
    /**
     * Find where a method is used
     */
    protected function findMethodUsage(string $class, string $method): array
    {
        // This would scan the codebase for method calls
        // For now, return empty array
        return [];
    }
    
    /**
     * Generate rollback plan
     */
    protected function generateRollbackPlan(string $component, array $changes): array
    {
        return [
            'backup_component' => "cp $component $component.backup",
            'restore_command' => "cp $component.backup $component",
            'cache_clear' => 'php artisan cache:clear',
            'test_command' => 'php artisan test --filter=' . class_basename($component)
        ];
    }
}