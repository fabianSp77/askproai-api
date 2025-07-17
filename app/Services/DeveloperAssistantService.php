<?php

namespace App\Services;

use App\Services\MCP\MCPOrchestrator;
use App\Services\MCPAutoDiscoveryService;
use App\Services\MemoryBankAutomationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\Traits\CodeTemplateTrait;

class DeveloperAssistantService
{
    use CodeTemplateTrait;
    protected MCPOrchestrator $orchestrator;
    protected MCPAutoDiscoveryService $discovery;
    protected MemoryBankAutomationService $memory;
    
    protected array $codePatterns = [];
    protected array $projectContext = [];
    
    public function __construct(
        MCPOrchestrator $orchestrator,
        MCPAutoDiscoveryService $discovery,
        MemoryBankAutomationService $memory
    ) {
        $this->orchestrator = $orchestrator;
        $this->discovery = $discovery;
        $this->memory = $memory;
        
        $this->loadCodePatterns();
        $this->loadProjectContext();
    }
    
    /**
     * Generate code based on description
     */
    public function generateCode(string $description, string $type = 'auto'): array
    {
        try {
            // Analyze description to determine best approach
            $analysis = $this->analyzeRequest($description, $type);
            
            // Load relevant patterns and examples
            $patterns = $this->findRelevantPatterns($analysis);
            
            // Generate code based on patterns
            $code = $this->generateFromPatterns($analysis, $patterns);
            
            // Store in memory for future reference
            $this->memory->remember(
                "code_generation_{$analysis['type']}",
                [
                    'description' => $description,
                    'type' => $analysis['type'],
                    'code' => $code,
                    'patterns_used' => array_map(fn($p) => $p['name'], $patterns),
                    'timestamp' => now()->toDateTimeString()
                ],
                'code_generation',
                ['code', 'generation', $analysis['type']]
            );
            
            return [
                'success' => true,
                'type' => $analysis['type'],
                'code' => $code,
                'files' => $this->suggestFileLocations($analysis, $code),
                'tests' => $this->generateTests($analysis, $code),
                'documentation' => $this->generateDocumentation($analysis, $code)
            ];
            
        } catch (\Exception $e) {
            Log::error('Code generation failed', [
                'description' => $description,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Analyze existing code and suggest improvements
     */
    public function analyzeCode(string $filePath): array
    {
        if (!File::exists($filePath)) {
            return ['error' => 'File not found'];
        }
        
        $content = File::get($filePath);
        $analysis = [];
        
        // Check for common issues
        $analysis['issues'] = $this->findCodeIssues($content);
        
        // Suggest improvements
        $analysis['improvements'] = $this->suggestImprovements($content, $filePath);
        
        // Check against project patterns
        $analysis['pattern_compliance'] = $this->checkPatternCompliance($content, $filePath);
        
        // Performance suggestions
        $analysis['performance'] = $this->analyzePerformance($content);
        
        // Security check
        $analysis['security'] = $this->checkSecurity($content);
        
        // Test coverage
        $analysis['test_coverage'] = $this->checkTestCoverage($filePath);
        
        return $analysis;
    }
    
    /**
     * Suggest next development steps
     */
    public function suggestNextSteps(string $context = ''): array
    {
        try {
            // Get recent activities from memory
            $recentWork = $this->memory->search('', 'work_context', ['development'])['data']['results'] ?? [];
            
            // Analyze current project state
            $projectState = $this->analyzeProjectState();
            
            // Generate suggestions
            $suggestions = [
                'immediate' => $this->getImmediateSuggestions($projectState, $recentWork),
                'short_term' => $this->getShortTermSuggestions($projectState),
                'long_term' => $this->getLongTermSuggestions($projectState),
                'refactoring' => $this->getRefactoringSuggestions(),
                'testing' => $this->getTestingSuggestions()
            ];
            
            // Prioritize based on context
            if ($context) {
                $suggestions = $this->prioritizeSuggestions($suggestions, $context);
            }
            
            return [
                'success' => true,
                'suggestions' => $suggestions,
                'project_health' => $projectState['health'],
                'recommended_focus' => $this->determineRecommendedFocus($projectState)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate boilerplate code
     */
    public function generateBoilerplate(string $type, array $params = []): array
    {
        $templates = [
            'filament-resource' => $this->generateFilamentResource($params),
            'mcp-server' => $this->generateMCPServer($params),
            'service' => $this->generateService($params),
            'repository' => $this->generateRepository($params),
            'test' => $this->generateTest($params),
            'migration' => $this->generateMigration($params),
            'api-endpoint' => $this->generateApiEndpoint($params),
            'job' => $this->generateJob($params),
            'event-listener' => $this->generateEventListener($params),
            'notification' => $this->generateNotification($params)
        ];
        
        if (!isset($templates[$type])) {
            return [
                'success' => false,
                'error' => "Unknown boilerplate type: {$type}",
                'available_types' => array_keys($templates)
            ];
        }
        
        return [
            'success' => true,
            'type' => $type,
            'files' => $templates[$type],
            'instructions' => $this->getBoilerplateInstructions($type, $params)
        ];
    }
    
    /**
     * Find similar code in project
     */
    public function findSimilarCode(string $codeSnippet): array
    {
        $results = [];
        $pattern = $this->extractPattern($codeSnippet);
        
        // Search in app directory
        $files = File::allFiles(app_path());
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;
            
            $content = File::get($file->getPathname());
            $similarity = $this->calculateSimilarity($codeSnippet, $content);
            
            if ($similarity > 0.7) { // 70% similarity threshold
                $results[] = [
                    'file' => $file->getRelativePathname(),
                    'similarity' => round($similarity * 100, 2),
                    'matches' => $this->findMatchingLines($codeSnippet, $content)
                ];
            }
        }
        
        // Sort by similarity
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        return array_slice($results, 0, 10); // Top 10 results
    }
    
    /**
     * Explain code functionality
     */
    public function explainCode(string $filePath, ?int $startLine = null, ?int $endLine = null): array
    {
        if (!File::exists($filePath)) {
            return ['error' => 'File not found'];
        }
        
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        
        // Extract relevant portion
        if ($startLine && $endLine) {
            $lines = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
            $content = implode("\n", $lines);
        }
        
        // Analyze code structure
        $structure = $this->analyzeCodeStructure($content);
        
        // Generate explanation
        $explanation = [
            'summary' => $this->generateSummary($structure),
            'purpose' => $this->determinePurpose($structure),
            'components' => $this->explainComponents($structure),
            'dependencies' => $this->findDependencies($content),
            'side_effects' => $this->identifySideEffects($content),
            'usage_examples' => $this->generateUsageExamples($structure)
        ];
        
        return $explanation;
    }
    
    /**
     * Load code patterns from existing project
     */
    protected function loadCodePatterns(): void
    {
        // Load patterns from memory bank
        $patterns = $this->memory->search('pattern', 'code_patterns')['data']['results'] ?? [];
        
        foreach ($patterns as $pattern) {
            $this->codePatterns[] = $pattern['value'];
        }
        
        // Add default patterns if none exist
        if (empty($this->codePatterns)) {
            $this->loadDefaultPatterns();
        }
    }
    
    /**
     * Load project context
     */
    protected function loadProjectContext(): void
    {
        $this->projectContext = [
            'namespace' => 'App',
            'framework' => 'Laravel',
            'php_version' => PHP_VERSION,
            'conventions' => [
                'naming' => 'PascalCase for classes, camelCase for methods',
                'architecture' => 'Service-oriented with Repository pattern',
                'testing' => 'PHPUnit with feature and unit tests'
            ]
        ];
    }
    
    /**
     * Analyze request to determine code type
     */
    protected function analyzeRequest(string $description, string $type): array
    {
        $keywords = [
            'service' => ['service', 'business logic', 'process'],
            'controller' => ['controller', 'endpoint', 'api', 'route'],
            'model' => ['model', 'entity', 'database', 'table'],
            'repository' => ['repository', 'data access', 'query'],
            'job' => ['job', 'queue', 'async', 'background'],
            'command' => ['command', 'artisan', 'cli'],
            'resource' => ['resource', 'filament', 'admin'],
            'test' => ['test', 'testing', 'phpunit']
        ];
        
        if ($type === 'auto') {
            foreach ($keywords as $codeType => $words) {
                foreach ($words as $word) {
                    if (stripos($description, $word) !== false) {
                        $type = $codeType;
                        break 2;
                    }
                }
            }
        }
        
        return [
            'type' => $type,
            'description' => $description,
            'keywords' => $this->extractKeywords($description)
        ];
    }
    
    /**
     * Find relevant patterns for code generation
     */
    protected function findRelevantPatterns(array $analysis): array
    {
        $relevant = [];
        
        foreach ($this->codePatterns as $pattern) {
            if ($pattern['type'] === $analysis['type']) {
                $relevant[] = $pattern;
            }
        }
        
        return $relevant;
    }
    
    /**
     * Generate code from patterns
     */
    protected function generateFromPatterns(array $analysis, array $patterns): array
    {
        // This is a simplified version - in reality, this would use
        // more sophisticated code generation techniques
        
        $basePattern = $patterns[0] ?? $this->getDefaultPattern($analysis['type']);
        
        return [
            'main' => $this->fillTemplate($basePattern['template'], $analysis),
            'interface' => $basePattern['interface'] ?? null,
            'trait' => $basePattern['trait'] ?? null
        ];
    }
    
    /**
     * Generate Filament resource boilerplate
     */
    protected function generateFilamentResource(array $params): array
    {
        $modelName = $params['model'] ?? 'Example';
        $modelClass = "App\\Models\\{$modelName}";
        $resourceName = "{$modelName}Resource";
        
        return [
            "app/Filament/Admin/Resources/{$resourceName}.php" => $this->getFilamentResourceTemplate($modelName, $modelClass),
            "app/Filament/Admin/Resources/{$resourceName}/Pages/List{$modelName}s.php" => $this->getFilamentListPageTemplate($modelName, $resourceName),
            "app/Filament/Admin/Resources/{$resourceName}/Pages/Create{$modelName}.php" => $this->getFilamentCreatePageTemplate($modelName, $resourceName),
            "app/Filament/Admin/Resources/{$resourceName}/Pages/Edit{$modelName}.php" => $this->getFilamentEditPageTemplate($modelName, $resourceName)
        ];
    }
    
    /**
     * Generate MCP server boilerplate
     */
    protected function generateMCPServer(array $params): array
    {
        $name = $params['name'] ?? 'Example';
        $className = "{$name}MCPServer";
        
        return [
            "app/Services/MCP/{$className}.php" => $this->getMCPServerTemplate($name, $className, $params['tools'] ?? [])
        ];
    }
    
    /**
     * Generate service boilerplate
     */
    protected function generateService(array $params): array
    {
        $name = $params['name'] ?? 'Example';
        $className = "{$name}Service";
        
        return [
            "app/Services/{$className}.php" => $this->getServiceTemplate($name, $className),
            "app/Contracts/{$name}ServiceInterface.php" => $this->getServiceInterfaceTemplate($name)
        ];
    }
    
    /**
     * Generate repository boilerplate
     */
    protected function generateRepository(array $params): array
    {
        $name = $params['name'] ?? 'Example';
        $className = "{$name}Repository";
        $modelName = $params['model'] ?? $name;
        
        return [
            "app/Repositories/{$className}.php" => $this->getRepositoryTemplate($name, $className, $modelName),
            "app/Contracts/{$name}RepositoryInterface.php" => $this->getRepositoryInterfaceTemplate($name)
        ];
    }
    
    /**
     * Generate test boilerplate
     */
    protected function generateTest(array $params): array
    {
        $name = $params['name'] ?? 'Example';
        $type = $params['test_type'] ?? 'unit';
        $className = "{$name}Test";
        
        return [
            "tests/" . ucfirst($type) . "/{$className}.php" => $this->getTestTemplate($name, $className, $type)
        ];
    }
    
    /**
     * Generate migration boilerplate
     */
    protected function generateMigration(array $params): array
    {
        $table = $params['table'] ?? 'examples';
        $action = $params['action'] ?? 'create';
        $timestamp = date('Y_m_d_His');
        $className = 'Create' . Str::studly($table) . 'Table';
        
        return [
            "database/migrations/{$timestamp}_{$action}_{$table}_table.php" => $this->getMigrationTemplate($table, $className, $action)
        ];
    }
    
    /**
     * Generate API endpoint boilerplate
     */
    protected function generateApiEndpoint(array $params): array
    {
        $resource = $params['resource'] ?? 'example';
        $actions = explode(',', $params['actions'] ?? 'index,show,store,update,destroy');
        $controllerName = Str::studly($resource) . 'Controller';
        
        return [
            "app/Http/Controllers/Api/{$controllerName}.php" => $this->getApiControllerTemplate($resource, $controllerName, $actions),
            "routes/api-{$resource}.php" => $this->getApiRoutesTemplate($resource, $controllerName, $actions)
        ];
    }
    
    /**
     * Generate job boilerplate
     */
    protected function generateJob(array $params): array
    {
        $name = $params['name'] ?? 'Example';
        $className = "{$name}Job";
        
        return [
            "app/Jobs/{$className}.php" => $this->getJobTemplate($name, $className)
        ];
    }
    
    /**
     * Generate event listener boilerplate
     */
    protected function generateEventListener(array $params): array
    {
        $name = $params['name'] ?? 'Example';
        $eventClass = "{$name}Event";
        $listenerClass = "{$name}Listener";
        
        return [
            "app/Events/{$eventClass}.php" => $this->getEventTemplate($name, $eventClass),
            "app/Listeners/{$listenerClass}.php" => $this->getListenerTemplate($name, $eventClass, $listenerClass)
        ];
    }
    
    /**
     * Generate notification boilerplate
     */
    protected function generateNotification(array $params): array
    {
        $name = $params['name'] ?? 'Example';
        $className = "{$name}Notification";
        
        return [
            "app/Notifications/{$className}.php" => $this->getNotificationTemplate($name, $className)
        ];
    }
    
    /**
     * Get boilerplate instructions
     */
    protected function getBoilerplateInstructions(string $type, array $params): array
    {
        $instructions = [
            'filament-resource' => [
                'Run migrations if you created a new model',
                'Register any required policies',
                'Configure navigation settings in the resource',
                'Add form fields and table columns as needed'
            ],
            'mcp-server' => [
                'Register the server in MCPServiceProvider',
                'Add configuration to config/mcp-servers.php',
                'Implement tool handler methods',
                'Add health check logic if needed'
            ],
            'service' => [
                'Register service in AppServiceProvider if needed',
                'Inject dependencies via constructor',
                'Add business logic methods',
                'Write unit tests for the service'
            ],
            'api-endpoint' => [
                'Include the route file in RouteServiceProvider',
                'Add request validation classes',
                'Implement resource transformers',
                'Document API endpoints'
            ]
        ];
        
        return $instructions[$type] ?? ['Review and customize the generated code'];
    }
    
    /**
     * Analyze project state
     */
    protected function analyzeProjectState(): array
    {
        // Simplified project analysis
        return [
            'health' => [
                'score' => 85,
                'test_coverage' => 75,
                'code_quality' => 'Good'
            ],
            'stats' => [
                'total_files' => count(File::allFiles(app_path())),
                'controllers' => count(File::files(app_path('Http/Controllers'))),
                'models' => count(File::files(app_path('Models'))),
                'services' => count(File::files(app_path('Services')))
            ]
        ];
    }
    
    /**
     * Get immediate suggestions
     */
    protected function getImmediateSuggestions(array $projectState, array $recentWork): array
    {
        $suggestions = [];
        
        // Check for missing tests
        if ($projectState['health']['test_coverage'] < 80) {
            $suggestions[] = [
                'task' => 'Increase test coverage',
                'reason' => 'Current coverage is below 80%',
                'priority' => 'high'
            ];
        }
        
        // Check recent work for follow-ups
        foreach ($recentWork as $work) {
            if (isset($work['value']['type']) && $work['value']['type'] === 'todo') {
                $suggestions[] = [
                    'task' => $work['value']['content'] ?? 'Complete pending task',
                    'priority' => 'medium'
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Get short term suggestions
     */
    protected function getShortTermSuggestions(array $projectState): array
    {
        return [
            [
                'task' => 'Refactor large controllers',
                'reason' => 'Improve maintainability',
                'priority' => 'medium'
            ],
            [
                'task' => 'Add API documentation',
                'reason' => 'Improve developer experience',
                'priority' => 'low'
            ]
        ];
    }
    
    /**
     * Get long term suggestions
     */
    protected function getLongTermSuggestions(array $projectState): array
    {
        return [
            [
                'task' => 'Implement caching strategy',
                'reason' => 'Improve performance',
                'priority' => 'low'
            ],
            [
                'task' => 'Add monitoring and alerting',
                'reason' => 'Improve reliability',
                'priority' => 'medium'
            ]
        ];
    }
    
    /**
     * Get refactoring suggestions
     */
    protected function getRefactoringSuggestions(): array
    {
        return [];
    }
    
    /**
     * Get testing suggestions
     */
    protected function getTestingSuggestions(): array
    {
        return [];
    }
    
    /**
     * Prioritize suggestions
     */
    protected function prioritizeSuggestions(array $suggestions, string $context): array
    {
        // Reorder based on context
        return $suggestions;
    }
    
    /**
     * Determine recommended focus
     */
    protected function determineRecommendedFocus(array $projectState): string
    {
        if ($projectState['health']['test_coverage'] < 60) {
            return 'Focus on improving test coverage';
        }
        
        if ($projectState['health']['score'] < 70) {
            return 'Focus on code quality improvements';
        }
        
        return 'Continue with feature development';
    }
    
    /**
     * Load default patterns
     */
    protected function loadDefaultPatterns(): void
    {
        $this->codePatterns = [
            [
                'name' => 'Laravel Service',
                'type' => 'service',
                'template' => $this->getServiceTemplate('Example', 'ExampleService')
            ],
            [
                'name' => 'Repository Pattern',
                'type' => 'repository',
                'template' => $this->getRepositoryTemplate('Example', 'ExampleRepository', 'Example')
            ]
        ];
    }
    
    /**
     * Get default pattern
     */
    protected function getDefaultPattern(string $type): array
    {
        return [
            'template' => match($type) {
                'service' => $this->getServiceTemplate('Generated', 'GeneratedService'),
                'controller' => $this->getControllerTemplate('Generated', 'GeneratedController'),
                default => '<?php // Generated code'
            }
        ];
    }
    
    /**
     * Fill template with data
     */
    protected function fillTemplate(string $template, array $data): string
    {
        // Simple template filling
        return $template;
    }
    
    /**
     * Suggest file locations
     */
    protected function suggestFileLocations(array $analysis, array $code): array
    {
        $type = $analysis['type'];
        $name = $this->extractNameFromDescription($analysis['description']);
        
        return match($type) {
            'service' => ["app/Services/{$name}Service.php"],
            'controller' => ["app/Http/Controllers/{$name}Controller.php"],
            'model' => ["app/Models/{$name}.php"],
            'repository' => ["app/Repositories/{$name}Repository.php"],
            default => []
        };
    }
    
    /**
     * Generate tests for code
     */
    protected function generateTests(array $analysis, array $code): string
    {
        return "// TODO: Add tests for {$analysis['type']}";
    }
    
    /**
     * Generate documentation
     */
    protected function generateDocumentation(array $analysis, array $code): string
    {
        return "// TODO: Add documentation";
    }
    
    /**
     * Extract name from description
     */
    protected function extractNameFromDescription(string $description): string
    {
        // Extract potential class name from description
        if (preg_match('/\b([A-Z][a-zA-Z]+)\b/', $description, $matches)) {
            return $matches[1];
        }
        
        return 'Generated';
    }
    
    /**
     * Find code issues implementation
     */
    protected function findCodeIssues(string $content): array
    {
        $analysisService = new CodeAnalysisService();
        return $analysisService->findIssues($content);
    }
    
    /**
     * Suggest improvements implementation
     */
    protected function suggestImprovements(string $content, string $filePath): array
    {
        $analysisService = new CodeAnalysisService();
        return $analysisService->generateSuggestions($content, $filePath);
    }
    
    /**
     * Check pattern compliance
     */
    protected function checkPatternCompliance(string $content, string $filePath): array
    {
        // Simplified pattern compliance check
        $score = 100;
        $violations = [];
        
        // Check naming conventions
        if (str_contains($filePath, 'Controller') && !str_ends_with($filePath, 'Controller.php')) {
            $violations[] = 'Controller file should end with Controller.php';
            $score -= 10;
        }
        
        return [
            'score' => $score,
            'violations' => $violations
        ];
    }
    
    /**
     * Analyze performance
     */
    protected function analyzePerformance(string $content): array
    {
        $suggestions = [];
        
        // Check for N+1 queries
        if (preg_match('/foreach.*\$.*->/', $content) && str_contains($content, '->get()')) {
            $suggestions[] = 'Potential N+1 query detected. Consider using eager loading.';
        }
        
        return $suggestions;
    }
    
    /**
     * Check security
     */
    protected function checkSecurity(string $content): array
    {
        $issues = [];
        
        // Check for SQL injection
        if (preg_match('/DB::raw\s*\(.*\$/', $content)) {
            $issues[] = 'Potential SQL injection risk with DB::raw()';
        }
        
        return $issues;
    }
    
    /**
     * Check test coverage
     */
    protected function checkTestCoverage(string $filePath): array
    {
        // Simplified test coverage check
        $testFile = str_replace('.php', 'Test.php', $filePath);
        $testFile = str_replace('app/', 'tests/Unit/', $testFile);
        
        return [
            'has_tests' => File::exists($testFile),
            'test_file' => $testFile
        ];
    }
    
    /**
     * Analyze code structure
     */
    protected function analyzeCodeStructure(string $content): array
    {
        return [
            'type' => $this->detectCodeType($content),
            'methods' => $this->extractMethods($content),
            'properties' => $this->extractProperties($content),
            'dependencies' => $this->extractDependencies($content)
        ];
    }
    
    /**
     * Generate summary
     */
    protected function generateSummary(array $structure): string
    {
        $type = $structure['type'];
        $methodCount = count($structure['methods']);
        
        return "This is a {$type} with {$methodCount} methods.";
    }
    
    /**
     * Determine purpose
     */
    protected function determinePurpose(array $structure): string
    {
        // Analyze method names and structure to determine purpose
        return "Manages business logic and data operations.";
    }
    
    /**
     * Explain components
     */
    protected function explainComponents(array $structure): array
    {
        $explanations = [];
        
        foreach ($structure['methods'] as $method) {
            $explanations[$method] = "Handles specific functionality";
        }
        
        return $explanations;
    }
    
    /**
     * Find dependencies
     */
    protected function findDependencies(string $content): array
    {
        $deps = [];
        
        if (preg_match_all('/use\s+([\w\\\\]+);/', $content, $matches)) {
            $deps = $matches[1];
        }
        
        return $deps;
    }
    
    /**
     * Identify side effects
     */
    protected function identifySideEffects(string $content): array
    {
        $effects = [];
        
        if (str_contains($content, 'DB::')) {
            $effects[] = 'Database operations';
        }
        
        if (str_contains($content, 'Log::')) {
            $effects[] = 'Logging';
        }
        
        if (str_contains($content, 'dispatch(')) {
            $effects[] = 'Queue jobs';
        }
        
        return $effects;
    }
    
    /**
     * Generate usage examples
     */
    protected function generateUsageExamples(array $structure): string
    {
        return "// Example usage\n// \$service = new Service();\n// \$result = \$service->method();";
    }
    
    /**
     * Calculate similarity
     */
    protected function calculateSimilarity(string $code1, string $code2): float
    {
        $analysisService = new CodeAnalysisService();
        return $analysisService->calculateSimilarity($code1, $code2);
    }
    
    /**
     * Extract pattern from code
     */
    protected function extractPattern(string $code): string
    {
        // Normalize code to extract pattern
        return preg_replace('/\$\w+/', '$var', $code);
    }
    
    /**
     * Find matching lines
     */
    protected function findMatchingLines(string $needle, string $haystack): array
    {
        $matches = [];
        $lines = explode("\n", $haystack);
        $needleLines = explode("\n", $needle);
        
        foreach ($lines as $lineNum => $line) {
            foreach ($needleLines as $needleLine) {
                if (similar_text($line, $needleLine) > 0.8 * max(strlen($line), strlen($needleLine))) {
                    $matches[] = [
                        'line' => $lineNum + 1,
                        'content' => trim($line)
                    ];
                }
            }
        }
        
        return array_slice($matches, 0, 5);
    }
    
    /**
     * Format tools array
     */
    protected function formatToolsArray(array $tools): string
    {
        $formatted = "[\n";
        foreach ($tools as $tool) {
            $formatted .= "            [\n";
            $formatted .= "                'name' => '{$tool['name']}',\n";
            $formatted .= "                'description' => '{$tool['description']}',\n";
            $formatted .= "                'parameters' => " . var_export($tool['parameters'] ?? [], true) . ",\n";
            $formatted .= "            ],\n";
        }
        $formatted .= "        ]";
        return $formatted;
    }
    
    /**
     * Detect code type
     */
    protected function detectCodeType(string $content): string
    {
        if (str_contains($content, 'extends Controller')) return 'controller';
        if (str_contains($content, 'extends Model')) return 'model';
        if (str_contains($content, 'Service')) return 'service';
        if (str_contains($content, 'Repository')) return 'repository';
        return 'class';
    }
    
    /**
     * Extract methods
     */
    protected function extractMethods(string $content): array
    {
        $methods = [];
        
        if (preg_match_all('/function\s+(\w+)/', $content, $matches)) {
            $methods = $matches[1];
        }
        
        return $methods;
    }
    
    /**
     * Extract properties
     */
    protected function extractProperties(string $content): array
    {
        $properties = [];
        
        if (preg_match_all('/(?:public|protected|private)\s+(?:\??\w+\s+)?\$(\w+)/', $content, $matches)) {
            $properties = $matches[1];
        }
        
        return $properties;
    }
    
    /**
     * Extract dependencies from content
     */
    protected function extractDependencies(string $content): array
    {
        $deps = [];
        
        // Constructor dependencies
        if (preg_match('/function\s+__construct\s*\((.*?)\)/', $content, $match)) {
            if (preg_match_all('/(\w+)\s+\$\w+/', $match[1], $params)) {
                $deps = array_merge($deps, $params[1]);
            }
        }
        
        return array_unique($deps);
    }
    
    /**
     * Extract keywords from description
     */
    protected function extractKeywords(string $description): array
    {
        $words = str_word_count(strtolower($description), 1);
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for'];
        
        return array_values(array_diff($words, $stopWords));
    }
}