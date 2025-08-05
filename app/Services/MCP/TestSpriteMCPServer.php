<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * TestSprite MCP Server Integration
 * 
 * Provides automated testing capabilities through TestSprite's AI testing engine.
 * Enables autonomous test generation, execution, and debugging.
 */
class TestSpriteMCPServer
{
    protected array $config;
    private string $apiKey;
    private string $apiUrl = 'https://api.testsprite.com/v1';
    
    public function __construct()
    {
        $this->apiKey = config('services.testsprite.api_key', '');
        $this->config = [
            'cache' => [
                'ttl' => 300,
                'prefix' => 'mcp:testsprite'
            ]
        ];
    }

    public function getInfo(): array
    {
        return [
            'name' => 'TestSprite MCP Server',
            'version' => '1.0.0',
            'description' => 'AI-powered automated testing and test generation',
            'capabilities' => [
                'test_plan_generation' => true,
                'test_code_generation' => true,
                'test_execution' => true,
                'failure_diagnosis' => true,
                'coverage_reports' => true,
            ]
        ];
    }

    public function getTools(): array
    {
        return [
            'create_test_plan' => [
                'description' => 'Generate a comprehensive test plan from PRD/requirements',
                'parameters' => [
                    'requirements' => 'string|required - Product requirements or feature description',
                    'test_type' => 'string|optional - Type of tests (unit, integration, e2e, all)',
                ]
            ],
            'generate_tests' => [
                'description' => 'Generate test code for a specific feature or component',
                'parameters' => [
                    'component' => 'string|required - Component or feature to test',
                    'framework' => 'string|optional - Test framework (phpunit, pest, laravel)',
                ]
            ],
            'run_tests' => [
                'description' => 'Execute tests and get detailed results',
                'parameters' => [
                    'test_path' => 'string|required - Path to test file or directory',
                    'parallel' => 'boolean|optional - Run tests in parallel',
                ]
            ],
            'diagnose_failure' => [
                'description' => 'Analyze test failures and suggest fixes',
                'parameters' => [
                    'test_output' => 'string|required - Test failure output or error message',
                ]
            ],
            'coverage_report' => [
                'description' => 'Generate test coverage report',
                'parameters' => [
                    'format' => 'string|optional - Report format (html, text, json)',
                ]
            ],
        ];
    }

    public function executeTool(string $tool, array $args = []): array
    {
        try {
            return match($tool) {
                'create_test_plan' => $this->createTestPlan($args),
                'generate_tests' => $this->generateTests($args),
                'run_tests' => $this->runTests($args),
                'diagnose_failure' => $this->diagnoseFailure($args),
                'coverage_report' => $this->coverageReport($args),
                default => [
                    'success' => false,
                    'error' => "Unknown tool: {$tool}"
                ]
            };
        } catch (\Exception $e) {
            Log::error('TestSprite tool execution failed', [
                'tool' => $tool,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function createTestPlan(array $args): array
    {
        if (empty($args['requirements'])) {
            return [
                'success' => false,
                'error' => 'Requirements are required'
            ];
        }

        try {
            $cacheKey = $this->config['cache']['prefix'] . ':plan:' . md5($args['requirements']);
            
            return Cache::remember($cacheKey, $this->config['cache']['ttl'], function() use ($args) {
                $response = Http::withToken($this->apiKey)
                    ->timeout(30)
                    ->post($this->apiUrl . '/test-plans', [
                        'requirements' => $args['requirements'],
                        'test_type' => $args['test_type'] ?? 'all',
                        'project_context' => [
                            'framework' => 'Laravel',
                            'type' => 'SaaS',
                            'domain' => 'AI Phone Assistant'
                        ]
                    ]);

                if ($response->successful()) {
                    $plan = $response->json();
                    return [
                        'success' => true,
                        'plan' => $plan,
                        'summary' => [
                            'total_cases' => $plan['total_cases'] ?? 0,
                            'categories' => $plan['categories'] ?? [],
                            'priority_tests' => array_slice($plan['priority_tests'] ?? [], 0, 5)
                        ]
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Failed to create test plan: ' . $response->body()
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error creating test plan: ' . $e->getMessage()
            ];
        }
    }

    private function generateTests(array $args): array
    {
        if (empty($args['component'])) {
            return [
                'success' => false,
                'error' => 'Component is required'
            ];
        }

        try {
            $framework = $args['framework'] ?? 'pest';
            $component = $args['component'];
            
            // Find component file
            $componentPath = $this->findComponentPath($component);
            $componentCode = $componentPath && file_exists($componentPath) 
                ? file_get_contents($componentPath) 
                : null;

            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post($this->apiUrl . '/generate-tests', [
                    'component' => $component,
                    'framework' => $framework,
                    'code' => $componentCode,
                    'context' => [
                        'laravel_version' => app()->version(),
                        'php_version' => PHP_VERSION
                    ]
                ]);

            if ($response->successful()) {
                $result = $response->json();
                
                // Save generated tests
                $testPath = base_path("tests/Feature/{$component}Test.php");
                if (!file_exists(dirname($testPath))) {
                    mkdir(dirname($testPath), 0755, true);
                }
                
                file_put_contents($testPath, $result['test_code']);
                
                return [
                    'success' => true,
                    'test_path' => $testPath,
                    'test_count' => $result['test_count'] ?? 0,
                    'message' => "Tests generated successfully at {$testPath}"
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to generate tests: ' . $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error generating tests: ' . $e->getMessage()
            ];
        }
    }

    private function runTests(array $args): array
    {
        if (empty($args['test_path'])) {
            return [
                'success' => false,
                'error' => 'Test path is required'
            ];
        }

        try {
            $testPath = $args['test_path'];
            $parallel = $args['parallel'] ?? true;
            
            // Run tests using Laravel's test command
            $command = $parallel 
                ? "php artisan test --parallel {$testPath}"
                : "php artisan test {$testPath}";
                
            $result = Process::timeout(300)->run($command);
            
            $output = $result->output();
            $exitCode = $result->exitCode();
            
            // Parse test results
            $stats = $this->parseTestOutput($output);
            
            // Send results to TestSprite for analysis if API key is available
            if ($this->apiKey) {
                $response = Http::withToken($this->apiKey)
                    ->post($this->apiUrl . '/analyze-results', [
                        'output' => $output,
                        'exit_code' => $exitCode,
                        'test_path' => $testPath
                    ]);

                if ($response->successful()) {
                    $analysis = $response->json();
                    $stats = array_merge($stats, $analysis);
                }
            }

            return [
                'success' => $exitCode === 0,
                'stats' => $stats,
                'output' => $output,
                'exit_code' => $exitCode
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error running tests: ' . $e->getMessage()
            ];
        }
    }

    private function diagnoseFailure(array $args): array
    {
        if (empty($args['test_output'])) {
            return [
                'success' => false,
                'error' => 'Test output is required'
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post($this->apiUrl . '/diagnose', [
                    'error_output' => $args['test_output'],
                    'context' => [
                        'framework' => 'Laravel',
                        'language' => 'PHP'
                    ]
                ]);

            if ($response->successful()) {
                $diagnosis = $response->json();
                
                return [
                    'success' => true,
                    'diagnosis' => [
                        'root_cause' => $diagnosis['root_cause'] ?? 'Unknown',
                        'fixes' => $diagnosis['fixes'] ?? [],
                        'context' => $diagnosis['context'] ?? ''
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to diagnose: ' . $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error diagnosing failure: ' . $e->getMessage()
            ];
        }
    }

    private function coverageReport(array $args): array
    {
        try {
            $format = $args['format'] ?? 'text';
            
            // Generate coverage report
            $command = match($format) {
                'html' => 'php artisan test --coverage-html=coverage',
                'json' => 'php artisan test --coverage --coverage-json=coverage.json',
                default => 'php artisan test --coverage'
            };
            
            $result = Process::timeout(600)->run($command);
            
            if ($result->successful()) {
                $output = $result->output();
                $coverage = $this->parseCoverageOutput($output);
                
                return [
                    'success' => true,
                    'coverage' => $coverage,
                    'format' => $format,
                    'output' => $output,
                    'report_path' => match($format) {
                        'html' => 'coverage/index.html',
                        'json' => 'coverage.json',
                        default => null
                    }
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to generate coverage report: ' . $result->errorOutput()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error generating coverage: ' . $e->getMessage()
            ];
        }
    }

    private function findComponentPath(string $component): ?string
    {
        $possiblePaths = [
            "app/{$component}.php",
            "app/Services/{$component}.php",
            "app/Models/{$component}.php",
            "app/Http/Controllers/{$component}.php",
            "app/Http/Controllers/{$component}Controller.php",
        ];
        
        foreach ($possiblePaths as $path) {
            $fullPath = base_path($path);
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        
        return null;
    }

    private function parseTestOutput(string $output): array
    {
        $stats = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'duration' => 0
        ];

        // Parse PHPUnit/Pest output
        if (preg_match('/Tests:\s+(\d+)\s+passed/', $output, $matches)) {
            $stats['passed'] = (int) $matches[1];
        }
        if (preg_match('/(\d+)\s+failed/', $output, $matches)) {
            $stats['failed'] = (int) $matches[1];
        }
        if (preg_match('/(\d+)\s+skipped/', $output, $matches)) {
            $stats['skipped'] = (int) $matches[1];
        }
        if (preg_match('/Time:\s+([\d.]+)/', $output, $matches)) {
            $stats['duration'] = (float) $matches[1];
        }

        $stats['total'] = $stats['passed'] + $stats['failed'] + $stats['skipped'];
        
        return $stats;
    }

    private function parseCoverageOutput(string $output): array
    {
        $coverage = [
            'total' => 0,
            'lines' => 0,
            'methods' => 0,
            'classes' => 0
        ];

        if (preg_match('/Total Coverage:\s+([\d.]+)%/', $output, $matches)) {
            $coverage['total'] = (float) $matches[1];
        }
        if (preg_match('/Lines:\s+([\d.]+)%/', $output, $matches)) {
            $coverage['lines'] = (float) $matches[1];
        }
        if (preg_match('/Methods:\s+([\d.]+)%/', $output, $matches)) {
            $coverage['methods'] = (float) $matches[1];
        }
        if (preg_match('/Classes:\s+([\d.]+)%/', $output, $matches)) {
            $coverage['classes'] = (float) $matches[1];
        }

        return $coverage;
    }
}