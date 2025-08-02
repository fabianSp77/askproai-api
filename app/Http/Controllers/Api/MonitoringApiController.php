<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MCP\MonitoringMCPServer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MonitoringApiController extends Controller
{
    protected MonitoringMCPServer $monitoringMCP;
    
    public function __construct(MonitoringMCPServer $monitoringMCP)
    {
        $this->monitoringMCP = $monitoringMCP;
        
        // Only super admins can access monitoring
        $this->middleware('can:viewSystemMonitoring');
    }
    
    /**
     * Get system health status
     */
    public function health(Request $request): JsonResponse
    {
        $result = $this->monitoringMCP->executeTool('getSystemHealth', [
            'include_details' => $request->boolean('include_details', false),
            'check_external' => $request->boolean('check_external', false)
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Get performance metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metric_types' => 'nullable|array',
            'metric_types.*' => 'string|in:cpu,memory,disk,network,database,cache,queue',
            'time_range' => 'nullable|string|in:1h,6h,24h,7d,30d',
            'aggregation' => 'nullable|string|in:min,avg,max'
        ]);
        
        $result = $this->monitoringMCP->executeTool('getPerformanceMetrics', [
            'metric_types' => $validated['metric_types'] ?? ['cpu', 'memory', 'database'],
            'time_range' => $validated['time_range'] ?? '1h',
            'aggregation' => $validated['aggregation'] ?? 'avg'
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Monitor API endpoints
     */
    public function apiEndpoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoints' => 'nullable|array',
            'endpoints.*' => 'string',
            'include_response_times' => 'nullable|boolean',
            'include_error_rates' => 'nullable|boolean',
            'time_range' => 'nullable|string'
        ]);
        
        $result = $this->monitoringMCP->executeTool('monitorApiEndpoints', [
            'endpoints' => $validated['endpoints'] ?? [],
            'include_response_times' => $validated['include_response_times'] ?? true,
            'include_error_rates' => $validated['include_error_rates'] ?? true,
            'time_range' => $validated['time_range'] ?? '1h'
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Get error logs
     */
    public function errors(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'severity' => 'nullable|string|in:debug,info,warning,error,critical',
            'time_range' => 'nullable|string',
            'pattern' => 'nullable|string|max:255',
            'group_by' => 'nullable|string|in:type,endpoint,user,time',
            'limit' => 'nullable|integer|min:1|max:1000'
        ]);
        
        $result = $this->monitoringMCP->executeTool('getErrorLogs', [
            'severity' => $validated['severity'] ?? null,
            'time_range' => $validated['time_range'] ?? '24h',
            'pattern' => $validated['pattern'] ?? null,
            'group_by' => $validated['group_by'] ?? 'type',
            'limit' => $validated['limit'] ?? 100
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Monitor database performance
     */
    public function database(Request $request): JsonResponse
    {
        $result = $this->monitoringMCP->executeTool('monitorDatabasePerformance', [
            'include_slow_queries' => $request->boolean('include_slow_queries', true),
            'include_table_stats' => $request->boolean('include_table_stats', true),
            'include_connection_stats' => $request->boolean('include_connection_stats', true),
            'slow_query_threshold' => $request->float('slow_query_threshold', 1.0)
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Monitor queue health
     */
    public function queues(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'queues' => 'nullable|array',
            'queues.*' => 'string',
            'include_failed_jobs' => 'nullable|boolean',
            'include_processing_times' => 'nullable|boolean'
        ]);
        
        $result = $this->monitoringMCP->executeTool('monitorQueueHealth', [
            'queues' => $validated['queues'] ?? ['default', 'emails', 'webhooks'],
            'include_failed_jobs' => $validated['include_failed_jobs'] ?? true,
            'include_processing_times' => $validated['include_processing_times'] ?? true
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Create or update an alert
     */
    public function createAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'metric' => 'required|string',
            'condition' => 'required|string|in:gt,lt,eq,gte,lte',
            'threshold' => 'required|numeric',
            'duration' => 'nullable|integer|min:1|max:60',
            'actions' => 'nullable|array',
            'actions.*' => 'string|in:log,email,slack,webhook',
            'enabled' => 'nullable|boolean'
        ]);
        
        $result = $this->monitoringMCP->executeTool('setAlert', $validated);
        
        return response()->json($result);
    }
    
    /**
     * Generate health report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => 'nullable|string|in:summary,detailed,executive',
            'include_recommendations' => 'nullable|boolean',
            'format' => 'nullable|string|in:json,html,pdf'
        ]);
        
        $result = $this->monitoringMCP->executeTool('generateHealthReport', [
            'report_type' => $validated['report_type'] ?? 'summary',
            'include_recommendations' => $validated['include_recommendations'] ?? true,
            'format' => $validated['format'] ?? 'json'
        ]);
        
        return response()->json($result);
    }
    
    /**
     * Get real-time monitoring data (for WebSocket updates)
     */
    public function realtime(Request $request): JsonResponse
    {
        // This would typically be pushed via WebSocket, but can be polled
        $data = [
            'timestamp' => now()->toIso8601String(),
            'health_status' => $this->getQuickHealthStatus(),
            'active_alerts' => $this->getActiveAlerts(),
            'current_metrics' => [
                'cpu' => $this->getCurrentCpuUsage(),
                'memory' => $this->getCurrentMemoryUsage(),
                'active_users' => $this->getActiveUserCount(),
                'requests_per_minute' => $this->getRequestsPerMinute()
            ]
        ];
        
        return response()->json($data);
    }
    
    /**
     * Test monitoring alert
     */
    public function testAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_id' => 'required|string',
            'test_value' => 'nullable|numeric'
        ]);
        
        // Simulate alert trigger for testing
        $alert = \Cache::get("monitoring:alert:{$validated['alert_id']}");
        
        if (!$alert) {
            return response()->json(['error' => 'Alert not found'], 404);
        }
        
        // Test alert actions
        $testResult = [
            'alert' => $alert,
            'test_value' => $validated['test_value'] ?? $alert['threshold'] + 1,
            'would_trigger' => true,
            'actions_tested' => []
        ];
        
        foreach ($alert['actions'] as $action) {
            $testResult['actions_tested'][$action] = 'Would execute ' . $action . ' action';
        }
        
        return response()->json($testResult);
    }
    
    // Helper methods
    
    protected function getQuickHealthStatus(): string
    {
        // Quick health check without full details
        try {
            \DB::select('SELECT 1');
            $cacheWorks = \Cache::remember('health_check_test', 1, fn() => true);
            
            return $cacheWorks ? 'healthy' : 'degraded';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }
    
    protected function getActiveAlerts(): array
    {
        $alertIds = \Cache::get('monitoring:alerts:active', []);
        $activeAlerts = [];
        
        foreach ($alertIds as $alertId) {
            $alert = \Cache::get("monitoring:alert:{$alertId}");
            if ($alert && $alert['enabled']) {
                // Check if alert condition is met
                // This is simplified - real implementation would check actual metrics
                $activeAlerts[] = [
                    'id' => $alertId,
                    'name' => $alert['name'],
                    'metric' => $alert['metric'],
                    'severity' => 'warning'
                ];
            }
        }
        
        return $activeAlerts;
    }
    
    protected function getCurrentCpuUsage(): float
    {
        $load = sys_getloadavg();
        return round($load[0] * 100 / 4, 2); // Assuming 4 cores
    }
    
    protected function getCurrentMemoryUsage(): float
    {
        $memory = memory_get_usage(true);
        $limit = $this->getMemoryLimit();
        return round(($memory / $limit) * 100, 2);
    }
    
    protected function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {
            if ($matches[2] == 'M') {
                return $matches[1] * 1024 * 1024;
            } else if ($matches[2] == 'K') {
                return $matches[1] * 1024;
            }
        }
        return 134217728; // Default 128MB
    }
    
    protected function getActiveUserCount(): int
    {
        // Count users active in last 5 minutes
        return \DB::table('sessions')
            ->where('last_activity', '>', now()->subMinutes(5)->timestamp)
            ->count();
    }
    
    protected function getRequestsPerMinute(): int
    {
        // Get from cache or calculate from access logs
        return \Cache::remember('monitoring:rpm', 60, function () {
            return \App\Models\ApiEndpointMetric::where('created_at', '>', now()->subMinute())
                ->count();
        });
    }
}