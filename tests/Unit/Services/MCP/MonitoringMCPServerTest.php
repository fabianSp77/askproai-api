<?php

namespace Tests\Unit\Services\MCP;

use Tests\TestCase;
use App\Services\MCP\MonitoringMCPServer;
use App\Models\Company;
use App\Models\SystemMetric;
use App\Models\ErrorLog;
use App\Models\ApiEndpointMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class MonitoringMCPServerTest extends TestCase
{
    use RefreshDatabase;
    
    protected MonitoringMCPServer $mcp;
    protected Company $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mcp = new MonitoringMCPServer();
        
        // Create test data
        $this->company = Company::factory()->create();
        
        // Set company context
        app()->instance('currentCompany', $this->company);
    }
    
    public function test_get_tools_returns_correct_structure()
    {
        $tools = $this->mcp->getTools();
        
        $this->assertIsArray($tools);
        $this->assertCount(8, $tools);
        
        $toolNames = array_column($tools, 'name');
        $expectedTools = [
            'getSystemHealth',
            'getPerformanceMetrics',
            'monitorApiEndpoints',
            'getErrorLogs',
            'monitorDatabasePerformance',
            'monitorQueueHealth',
            'setAlert',
            'generateHealthReport'
        ];
        
        foreach ($expectedTools as $expectedTool) {
            $this->assertContains($expectedTool, $toolNames);
        }
    }
    
    public function test_get_system_health()
    {
        $result = $this->mcp->executeTool('getSystemHealth', [
            'include_details' => true,
            'check_external' => false
        ]);
        
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('health_score', $result);
        $this->assertArrayHasKey('details', $result);
        
        // Check component statuses
        $components = ['database', 'cache', 'queue', 'filesystem', 'resources'];
        foreach ($components as $component) {
            $this->assertArrayHasKey($component, $result['components']);
            $this->assertArrayHasKey('status', $result['components'][$component]);
            $this->assertContains($result['components'][$component]['status'], 
                ['healthy', 'degraded', 'critical']);
        }
        
        // Health score should be between 0 and 100
        $this->assertGreaterThanOrEqual(0, $result['health_score']);
        $this->assertLessThanOrEqual(100, $result['health_score']);
        
        // Verify health check was stored
        $this->assertDatabaseHas('system_metrics', [
            'type' => 'health_check',
            'value' => $result['health_score']
        ]);
    }
    
    public function test_get_system_health_with_external_services()
    {
        // Mock external service responses
        Http::fake([
            '*retell*' => Http::response(['status' => 'ok'], 200),
            '*calcom*' => Http::response(['status' => 'ok'], 200),
            '*stripe*' => Http::response([], 200),
            '*pusher*' => Http::response(['channels' => []], 200)
        ]);
        
        $result = $this->mcp->executeTool('getSystemHealth', [
            'check_external' => true
        ]);
        
        $this->assertArrayHasKey('external_services', $result['components']);
        $this->assertArrayHasKey('retell', $result['components']['external_services']);
        $this->assertArrayHasKey('calcom', $result['components']['external_services']);
        $this->assertArrayHasKey('stripe', $result['components']['external_services']);
        $this->assertArrayHasKey('pusher', $result['components']['external_services']);
    }
    
    public function test_get_performance_metrics()
    {
        // Create historical metric data
        $metricTypes = ['cpu', 'memory', 'disk', 'network'];
        foreach ($metricTypes as $type) {
            for ($i = 60; $i >= 0; $i--) {
                SystemMetric::create([
                    'type' => $type,
                    'value' => rand(20, 80),
                    'metadata' => ['unit' => 'percent'],
                    'created_at' => Carbon::now()->subMinutes($i)
                ]);
            }
        }
        
        $result = $this->mcp->executeTool('getPerformanceMetrics', [
            'metric_types' => ['cpu', 'memory'],
            'time_range' => '1h',
            'aggregation' => 'avg'
        ]);
        
        $this->assertArrayHasKey('cpu', $result);
        $this->assertArrayHasKey('memory', $result);
        $this->assertArrayHasKey('trends', $result);
        $this->assertArrayHasKey('alerts', $result);
        
        // Check CPU metrics
        $this->assertArrayHasKey('current', $result['cpu']);
        $this->assertArrayHasKey('avg', $result['cpu']);
        $this->assertArrayHasKey('min', $result['cpu']);
        $this->assertArrayHasKey('max', $result['cpu']);
        $this->assertArrayHasKey('history', $result['cpu']);
        
        $this->assertNotEmpty($result['cpu']['history']);
    }
    
    public function test_monitor_api_endpoints()
    {
        // Create API endpoint metrics
        $endpoints = ['/api/appointments', '/api/customers', '/api/calls'];
        
        foreach ($endpoints as $endpoint) {
            for ($i = 60; $i >= 0; $i--) {
                ApiEndpointMetric::create([
                    'endpoint' => $endpoint,
                    'method' => 'GET',
                    'status_code' => rand(0, 100) < 95 ? 200 : 500,
                    'response_time' => rand(50, 300),
                    'memory_usage' => rand(1000000, 5000000),
                    'created_at' => Carbon::now()->subMinutes($i)
                ]);
            }
        }
        
        $result = $this->mcp->executeTool('monitorApiEndpoints', [
            'endpoints' => $endpoints,
            'include_response_times' => true,
            'include_error_rates' => true,
            'time_range' => '1h'
        ]);
        
        $this->assertArrayHasKey('endpoints', $result);
        $this->assertArrayHasKey('overall', $result);
        
        foreach ($endpoints as $endpoint) {
            $this->assertArrayHasKey($endpoint, $result['endpoints']);
            $endpointData = $result['endpoints'][$endpoint];
            
            $this->assertArrayHasKey('status', $endpointData);
            $this->assertArrayHasKey('availability', $endpointData);
            $this->assertArrayHasKey('total_requests', $endpointData);
            $this->assertArrayHasKey('success_rate', $endpointData);
            $this->assertArrayHasKey('response_times', $endpointData);
            $this->assertArrayHasKey('errors', $endpointData);
            
            // Response times structure
            $this->assertArrayHasKey('p50', $endpointData['response_times']);
            $this->assertArrayHasKey('p95', $endpointData['response_times']);
            $this->assertArrayHasKey('p99', $endpointData['response_times']);
            $this->assertArrayHasKey('avg', $endpointData['response_times']);
        }
    }
    
    public function test_get_error_logs()
    {
        // Create error logs with different severities
        $severities = ['debug', 'info', 'warning', 'error', 'critical'];
        $types = ['database_error', 'api_error', 'validation_error'];
        
        foreach ($severities as $severity) {
            foreach ($types as $type) {
                ErrorLog::factory()->count(3)->create([
                    'severity' => $severity,
                    'type' => $type,
                    'message' => "Test {$severity} {$type}",
                    'created_at' => Carbon::now()->subHours(rand(1, 24))
                ]);
            }
        }
        
        // Test with severity filter
        $result = $this->mcp->executeTool('getErrorLogs', [
            'severity' => 'error',
            'time_range' => '24h',
            'group_by' => 'type'
        ]);
        
        $this->assertArrayHasKey('total_errors', $result);
        $this->assertArrayHasKey('time_range', $result);
        $this->assertArrayHasKey('grouped', $result);
        $this->assertArrayHasKey('analysis', $result);
        $this->assertArrayHasKey('recent_errors', $result);
        
        // Should only have error severity logs
        foreach ($result['recent_errors'] as $error) {
            $this->assertEquals('error', $error['severity']);
        }
        
        // Test with pattern search
        $result = $this->mcp->executeTool('getErrorLogs', [
            'pattern' => 'database',
            'time_range' => '24h'
        ]);
        
        foreach ($result['recent_errors'] as $error) {
            $this->assertStringContainsString('database', $error['message']);
        }
    }
    
    public function test_monitor_database_performance()
    {
        $result = $this->mcp->executeTool('monitorDatabasePerformance', [
            'include_slow_queries' => true,
            'include_table_stats' => true,
            'include_connection_stats' => true,
            'slow_query_threshold' => 1.0
        ]);
        
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('slow_queries', $result);
        $this->assertArrayHasKey('tables', $result);
        $this->assertArrayHasKey('query_cache', $result);
        $this->assertArrayHasKey('innodb', $result);
        
        // Connection stats
        $this->assertArrayHasKey('current', $result['connections']);
        $this->assertArrayHasKey('max', $result['connections']);
        $this->assertArrayHasKey('usage_percent', $result['connections']);
        
        // Slow queries
        $this->assertArrayHasKey('count', $result['slow_queries']);
        $this->assertArrayHasKey('threshold', $result['slow_queries']);
        $this->assertArrayHasKey('queries', $result['slow_queries']);
    }
    
    public function test_monitor_queue_health()
    {
        // Simulate queue data in Redis
        Redis::shouldReceive('llen')
            ->with('queues:default')
            ->andReturn(50);
        Redis::shouldReceive('llen')
            ->with('queues:default:processing')
            ->andReturn(5);
        Redis::shouldReceive('zcard')
            ->with('queues:default:delayed')
            ->andReturn(10);
        Redis::shouldReceive('zcard')
            ->with('queues:default:reserved')
            ->andReturn(3);
        
        Redis::shouldReceive('llen')
            ->with('queues:emails')
            ->andReturn(100);
        Redis::shouldReceive('llen')
            ->with('queues:emails:processing')
            ->andReturn(10);
        Redis::shouldReceive('zcard')
            ->with('queues:emails:delayed')
            ->andReturn(20);
        Redis::shouldReceive('zcard')
            ->with('queues:emails:reserved')
            ->andReturn(5);
        
        // Create failed jobs
        DB::table('failed_jobs')->insert([
            [
                'connection' => 'redis',
                'queue' => 'default',
                'payload' => json_encode(['job' => 'TestJob']),
                'exception' => 'Test exception',
                'failed_at' => Carbon::now()->subHours(2)
            ],
            [
                'connection' => 'redis',
                'queue' => 'emails',
                'payload' => json_encode(['job' => 'SendEmail']),
                'exception' => 'Email exception',
                'failed_at' => Carbon::now()->subHour()
            ]
        ]);
        
        $result = $this->mcp->executeTool('monitorQueueHealth', [
            'queues' => ['default', 'emails'],
            'include_failed_jobs' => true,
            'include_processing_times' => true
        ]);
        
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('queues', $result);
        
        // Check default queue
        $defaultQueue = collect($result['queues'])->firstWhere('name', 'default');
        $this->assertEquals(50, $defaultQueue['size']);
        $this->assertEquals(5, $defaultQueue['processing']);
        $this->assertEquals(10, $defaultQueue['delayed']);
        $this->assertEquals(3, $defaultQueue['reserved']);
        $this->assertEquals(1, $defaultQueue['failed_jobs']['count']);
        
        // Check emails queue
        $emailsQueue = collect($result['queues'])->firstWhere('name', 'emails');
        $this->assertEquals(100, $emailsQueue['size']);
        $this->assertEquals('warning', $emailsQueue['status']); // Should warn about high queue size
    }
    
    public function test_set_alert()
    {
        $result = $this->mcp->executeTool('setAlert', [
            'name' => 'High CPU Usage',
            'metric' => 'cpu_usage',
            'condition' => 'gt',
            'threshold' => 80,
            'duration' => 5,
            'actions' => ['log', 'email'],
            'enabled' => true
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('alert', $result);
        $this->assertEquals('High CPU Usage', $result['alert']['name']);
        $this->assertEquals('cpu_usage', $result['alert']['metric']);
        $this->assertEquals('gt', $result['alert']['condition']);
        $this->assertEquals(80, $result['alert']['threshold']);
        
        // Verify alert was stored in cache
        $alertId = $result['alert']['id'];
        $storedAlert = Cache::get("monitoring:alert:{$alertId}");
        $this->assertNotNull($storedAlert);
        $this->assertEquals('High CPU Usage', $storedAlert['name']);
        
        // Verify alert is in active list
        $activeAlerts = Cache::get('monitoring:alerts:active', []);
        $this->assertContains($alertId, $activeAlerts);
    }
    
    public function test_generate_health_report()
    {
        // Create some metrics for the report
        SystemMetric::create([
            'type' => 'health_check',
            'value' => 85,
            'metadata' => ['status' => 'healthy'],
            'created_at' => Carbon::now()
        ]);
        
        ErrorLog::factory()->count(5)->create([
            'severity' => 'error',
            'created_at' => Carbon::now()->subHours(12)
        ]);
        
        $result = $this->mcp->executeTool('generateHealthReport', [
            'report_type' => 'summary',
            'include_recommendations' => true,
            'format' => 'json'
        ]);
        
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('trends', $result);
        
        // Check data structure
        $this->assertArrayHasKey('system_health', $result['data']);
        $this->assertArrayHasKey('performance_metrics', $result['data']);
        $this->assertArrayHasKey('api_monitoring', $result['data']);
        $this->assertArrayHasKey('error_summary', $result['data']);
        $this->assertArrayHasKey('database_performance', $result['data']);
        $this->assertArrayHasKey('queue_health', $result['data']);
        
        // Should have recommendations
        $this->assertNotEmpty($result['recommendations']);
    }
    
    public function test_execute_tool_with_invalid_tool_name()
    {
        $result = $this->mcp->executeTool('invalidTool', []);
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Unknown tool', $result['error']);
    }
    
    public function test_filesystem_health_check()
    {
        $result = $this->mcp->executeTool('getSystemHealth', [
            'include_details' => true
        ]);
        
        $filesystem = $result['components']['filesystem'];
        $this->assertArrayHasKey('paths', $filesystem);
        $this->assertArrayHasKey('disk_usage', $filesystem);
        
        // Check critical paths
        $paths = ['storage', 'logs', 'cache', 'sessions'];
        foreach ($paths as $path) {
            $this->assertArrayHasKey($path, $filesystem['paths']);
            $this->assertEquals('ok', $filesystem['paths'][$path]);
        }
        
        // Disk usage
        $this->assertArrayHasKey('used_percent', $filesystem['disk_usage']);
        $this->assertArrayHasKey('free_gb', $filesystem['disk_usage']);
        $this->assertArrayHasKey('total_gb', $filesystem['disk_usage']);
    }
    
    public function test_resource_health_check()
    {
        $result = $this->mcp->executeTool('getSystemHealth', [
            'include_details' => true
        ]);
        
        $resources = $result['components']['resources'];
        $this->assertArrayHasKey('cpu', $resources);
        $this->assertArrayHasKey('memory', $resources);
        
        // CPU metrics
        $this->assertArrayHasKey('usage_percent', $resources['cpu']);
        $this->assertArrayHasKey('load_average', $resources['cpu']);
        $this->assertArrayHasKey('cores', $resources['cpu']);
        
        // Memory metrics
        $this->assertArrayHasKey('usage_percent', $resources['memory']);
        $this->assertArrayHasKey('used_mb', $resources['memory']);
        $this->assertArrayHasKey('limit', $resources['memory']);
    }
    
    public function test_alert_triggering_simulation()
    {
        // Set up an alert
        $alertResult = $this->mcp->executeTool('setAlert', [
            'name' => 'Test Alert',
            'metric' => 'test_metric',
            'condition' => 'gt',
            'threshold' => 50,
            'actions' => ['log']
        ]);
        
        $alertId = $alertResult['alert']['id'];
        
        // Simulate metric exceeding threshold
        SystemMetric::create([
            'type' => 'test_metric',
            'value' => 75, // Above threshold
            'created_at' => Carbon::now()
        ]);
        
        // Check alert would trigger
        $alerts = $this->mcp->executeTool('getPerformanceMetrics', [
            'metric_types' => ['test_metric']
        ]);
        
        $this->assertNotEmpty($alerts['alerts']);
    }
}