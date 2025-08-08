<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use Carbon\Carbon;

class MCPAdminController extends Controller
{
    protected MCPOrchestrator $orchestrator;

    public function __construct(MCPOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Get current MCP configuration
     */
    public function getConfiguration(): JsonResponse
    {
        try {
            $config = [
                'enabled' => config('mcp.enabled', false),
                'rolloutPercentage' => config('mcp.rollout_percentage', 0),
                'tokens' => [
                    'retell' => config('services.retell.api_key') ? '••••••••' : '',
                    'calcom' => config('services.calcom.api_key') ? '••••••••' : '',
                    'database' => 'internal',
                ],
                'rateLimits' => [
                    'requestsPerMinute' => config('mcp.rate_limits.requests_per_minute', 100),
                    'burstLimit' => config('mcp.rate_limits.burst_limit', 20),
                ],
                'circuitBreaker' => [
                    'failureThreshold' => config('mcp.circuit_breaker.failure_threshold', 5),
                    'resetTimeout' => config('mcp.circuit_breaker.reset_timeout', 60000),
                    'halfOpenRequests' => config('mcp.circuit_breaker.half_open_requests', 3),
                ],
            ];

            return response()->json(['data' => $config]);
        } catch (\Exception $e) {
            Log::error('Failed to get MCP configuration', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load configuration'], 500);
        }
    }

    /**
     * Update MCP configuration
     */
    public function updateConfiguration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'rolloutPercentage' => 'required|integer|min:0|max:100',
            'rateLimits.requestsPerMinute' => 'required|integer|min:1|max:1000',
            'rateLimits.burstLimit' => 'required|integer|min:1|max:100',
            'circuitBreaker.failureThreshold' => 'required|integer|min:1|max:20',
            'circuitBreaker.resetTimeout' => 'required|integer|min:1000|max:300000',
            'circuitBreaker.halfOpenRequests' => 'required|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $config = $request->all();
            
            // Update configuration in cache (in production, you'd update database/config files)
            Cache::put('mcp:configuration', $config, 3600);
            
            // Log configuration change
            Log::info('MCP configuration updated', [
                'user_id' => auth()->id(),
                'config' => $config,
            ]);

            // Trigger configuration reload event
            broadcast(new \App\Events\MCPConfigurationUpdated($config));

            return response()->json(['message' => 'Configuration updated successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to update MCP configuration', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update configuration'], 500);
        }
    }

    /**
     * Get real-time metrics
     */
    public function getMetrics(Request $request): JsonResponse
    {
        try {
            $timeRange = $request->get('timeRange', '1h');
            
            // Get basic metrics
            $metrics = [
                'totalRequests' => Cache::get('mcp:metrics:total_requests', 0),
                'successRate' => Cache::get('mcp:metrics:success_rate', 100.0),
                'averageLatency' => Cache::get('mcp:metrics:avg_latency', 0),
                'circuitBreakerState' => Cache::get('mcp:circuit_breaker:state', 'closed'),
                'activeConnections' => Cache::get('mcp:metrics:active_connections', 0),
                'requestsPerMinute' => Cache::get('mcp:metrics:requests_per_minute', 0),
                'errorRate' => Cache::get('mcp:metrics:error_rate', 0),
                'cacheHitRate' => Cache::get('mcp:metrics:cache_hit_rate', 0),
            ];

            // Add time-series data if requested
            if ($request->get('detailed', false)) {
                $metrics['timeSeries'] = $this->getTimeSeriesMetrics($timeRange);
            }

            return response()->json(['data' => $metrics]);
        } catch (\Exception $e) {
            Log::error('Failed to get MCP metrics', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load metrics'], 500);
        }
    }

    /**
     * Get recent MCP calls
     */
    public function getRecentCalls(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 50);
            $calls = Cache::get('mcp:recent_calls', []);
            
            $formattedCalls = collect($calls)
                ->take($limit)
                ->map(function ($call) {
                    return [
                        'tool' => $call['tool'] ?? 'unknown',
                        'operation' => $call['operation'] ?? 'unknown',
                        'success' => $call['success'] ?? false,
                        'duration' => $call['duration'] ?? 0,
                        'timestamp' => $call['timestamp'] ?? now()->format('H:i:s'),
                        'error' => $call['error'] ?? null,
                        'responseSize' => $call['response_size'] ?? 0,
                        'cached' => $call['cached'] ?? false,
                    ];
                })
                ->values();

            return response()->json(['data' => $formattedCalls]);
        } catch (\Exception $e) {
            Log::error('Failed to get recent MCP calls', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load recent calls'], 500);
        }
    }

    /**
     * Test an MCP tool
     */
    public function testTool(Request $request, string $toolName): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            // Map tool names to actual operations
            $operation = match ($toolName) {
                'calcom' => 'testConnection',
                'database' => 'healthCheck',
                'retell' => 'testConnection',
                'webhook' => 'healthCheck',
                'queue' => 'getOverview',
                default => 'healthCheck',
            };

            $mcpRequest = new MCPRequest(
                service: $toolName,
                operation: $operation,
                params: $request->all(),
                tenantId: auth()->user()->company_id ?? 1
            );

            $response = $this->orchestrator->route($mcpRequest);
            $responseTime = round((microtime(true) - $startTime) * 1000);

            // Log test result
            $this->logToolTest($toolName, $operation, $response->isSuccess(), $responseTime);

            return response()->json([
                'success' => $response->isSuccess(),
                'responseTime' => $responseTime,
                'data' => $response->getData(),
                'error' => $response->isSuccess() ? null : $response->getError(),
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            Log::error("MCP tool test failed: {$toolName}", [
                'error' => $e->getMessage(),
                'response_time' => $responseTime,
            ]);

            return response()->json([
                'success' => false,
                'responseTime' => $responseTime,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Get circuit breaker status
     */
    public function getCircuitBreakerStatus(): JsonResponse
    {
        try {
            $status = [
                'state' => Cache::get('mcp:circuit_breaker:state', 'closed'),
                'failures' => Cache::get('mcp:circuit_breaker:failures', 0),
                'lastFailTime' => Cache::get('mcp:circuit_breaker:last_fail_time'),
                'successCount' => Cache::get('mcp:circuit_breaker:success_count', 0),
            ];

            return response()->json(['data' => $status]);
        } catch (\Exception $e) {
            Log::error('Failed to get circuit breaker status', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get circuit breaker status'], 500);
        }
    }

    /**
     * Toggle circuit breaker state
     */
    public function toggleCircuitBreaker(): JsonResponse
    {
        try {
            $currentState = Cache::get('mcp:circuit_breaker:state', 'closed');
            $newState = $currentState === 'open' ? 'closed' : 'open';
            
            Cache::put('mcp:circuit_breaker:state', $newState, 3600);
            
            if ($newState === 'closed') {
                Cache::put('mcp:circuit_breaker:failures', 0, 3600);
                Cache::put('mcp:circuit_breaker:success_count', 0, 3600);
            }

            Log::info('Circuit breaker toggled', [
                'from' => $currentState,
                'to' => $newState,
                'user_id' => auth()->id(),
            ]);

            broadcast(new \App\Events\CircuitBreakerStateChanged($newState));

            return response()->json([
                'message' => "Circuit breaker {$newState}",
                'state' => $newState,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle circuit breaker', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to toggle circuit breaker'], 500);
        }
    }

    /**
     * Reset metrics
     */
    public function resetMetrics(): JsonResponse
    {
        try {
            $keys = [
                'mcp:metrics:total_requests',
                'mcp:metrics:success_rate',
                'mcp:metrics:avg_latency',
                'mcp:metrics:active_connections',
                'mcp:metrics:requests_per_minute',
                'mcp:metrics:error_rate',
                'mcp:metrics:cache_hit_rate',
                'mcp:recent_calls',
            ];

            foreach ($keys as $key) {
                Cache::forget($key);
            }

            Log::info('MCP metrics reset', ['user_id' => auth()->id()]);

            return response()->json(['message' => 'Metrics reset successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to reset metrics', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to reset metrics'], 500);
        }
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): JsonResponse
    {
        try {
            $health = $this->orchestrator->healthCheck();
            
            return response()->json(['data' => $health]);
        } catch (\Exception $e) {
            Log::error('Failed to get system health', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get system health'], 500);
        }
    }

    /**
     * Validate configuration
     */
    public function validateConfiguration(Request $request): JsonResponse
    {
        try {
            $config = $request->all();
            $errors = [];

            // Validate rollout percentage
            if ($config['rolloutPercentage'] > 100) {
                $errors['rolloutPercentage'] = 'Cannot exceed 100%';
            }

            // Validate rate limits
            if ($config['rateLimits']['requestsPerMinute'] > 1000) {
                $errors['rateLimits.requestsPerMinute'] = 'Recommended maximum is 1000';
            }

            // Validate circuit breaker settings
            if ($config['circuitBreaker']['resetTimeout'] < 10000) {
                $errors['circuitBreaker.resetTimeout'] = 'Minimum recommended timeout is 10 seconds';
            }

            $isValid = empty($errors);

            return response()->json([
                'valid' => $isValid,
                'errors' => $errors,
                'warnings' => $this->getConfigurationWarnings($config),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to validate configuration', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to validate configuration'], 500);
        }
    }

    /**
     * Get available tools information
     */
    public function getAvailableTools(): JsonResponse
    {
        try {
            $tools = [
                'calcom' => [
                    'name' => 'Cal.com Integration',
                    'description' => 'Calendar and appointment booking',
                    'status' => 'available',
                    'operations' => ['testConnection', 'getBookings', 'createBooking'],
                ],
                'database' => [
                    'name' => 'Database Service',
                    'description' => 'Database queries and operations',
                    'status' => 'available',
                    'operations' => ['healthCheck', 'getCallStats', 'runQuery'],
                ],
                'retell' => [
                    'name' => 'Retell AI Phone',
                    'description' => 'AI phone call handling',
                    'status' => 'available',
                    'operations' => ['testConnection', 'getCallStats', 'getRecentCalls'],
                ],
                'webhook' => [
                    'name' => 'Webhook Service',
                    'description' => 'Webhook processing and management',
                    'status' => 'available',
                    'operations' => ['healthCheck', 'getStats', 'processWebhook'],
                ],
                'queue' => [
                    'name' => 'Queue Manager',
                    'description' => 'Background job processing',
                    'status' => 'available',
                    'operations' => ['getOverview', 'getMetrics', 'getRecentJobs'],
                ],
            ];

            return response()->json(['data' => $tools]);
        } catch (\Exception $e) {
            Log::error('Failed to get available tools', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get available tools'], 500);
        }
    }

    /**
     * Get webhook comparison data
     */
    public function getWebhookComparison(Request $request): JsonResponse
    {
        try {
            $timeRange = $request->get('timeRange', '24h');
            
            // Mock comparison data (replace with real metrics)
            $comparison = [
                'mcp' => [
                    'totalRequests' => Cache::get('mcp:metrics:total_requests', 0),
                    'successRate' => Cache::get('mcp:metrics:success_rate', 100.0),
                    'averageLatency' => Cache::get('mcp:metrics:avg_latency', 0),
                    'errorRate' => Cache::get('mcp:metrics:error_rate', 0),
                ],
                'webhook' => [
                    'totalRequests' => Cache::get('webhook:metrics:total_requests', 0),
                    'successRate' => Cache::get('webhook:metrics:success_rate', 98.5),
                    'averageLatency' => Cache::get('webhook:metrics:avg_latency', 150),
                    'errorRate' => Cache::get('webhook:metrics:error_rate', 1.5),
                ],
            ];

            return response()->json(['data' => $comparison]);
        } catch (\Exception $e) {
            Log::error('Failed to get webhook comparison', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get webhook comparison'], 500);
        }
    }

    /**
     * Get time series metrics for charts
     */
    private function getTimeSeriesMetrics(string $timeRange): array
    {
        // Generate mock time series data (replace with real metrics)
        $points = 24; // 24 hours
        $interval = 3600; // 1 hour
        
        if ($timeRange === '1h') {
            $points = 60;
            $interval = 60; // 1 minute
        } elseif ($timeRange === '7d') {
            $points = 7;
            $interval = 86400; // 1 day
        }

        $data = [];
        $now = now();
        
        for ($i = $points - 1; $i >= 0; $i--) {
            $timestamp = $now->copy()->subSeconds($i * $interval);
            
            $data[] = [
                'timestamp' => $timestamp->toISOString(),
                'requests' => rand(10, 100),
                'latency' => rand(50, 200),
                'successRate' => rand(95, 100),
                'errors' => rand(0, 5),
            ];
        }

        return $data;
    }

    /**
     * Log tool test results
     */
    private function logToolTest(string $tool, string $operation, bool $success, int $responseTime): void
    {
        $testResult = [
            'tool' => $tool,
            'operation' => $operation,
            'success' => $success,
            'response_time' => $responseTime,
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
        ];

        // Store in recent tests cache
        $recentTests = Cache::get('mcp:recent_tests', []);
        array_unshift($recentTests, $testResult);
        Cache::put('mcp:recent_tests', array_slice($recentTests, 0, 50), 3600);

        Log::info('MCP tool tested', $testResult);
    }

    /**
     * Get configuration warnings
     */
    private function getConfigurationWarnings(array $config): array
    {
        $warnings = [];

        if ($config['rolloutPercentage'] > 90) {
            $warnings[] = 'High rollout percentage may affect system stability';
        }

        if ($config['rateLimits']['requestsPerMinute'] > 500) {
            $warnings[] = 'High rate limit may impact server performance';
        }

        if ($config['circuitBreaker']['failureThreshold'] < 3) {
            $warnings[] = 'Low failure threshold may cause frequent circuit breaker trips';
        }

        return $warnings;
    }
}