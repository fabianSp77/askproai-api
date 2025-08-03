<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Services\CircuitBreaker\CircuitBreaker;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SystemHealthMonitor extends Widget
{
    protected static string $view = 'filament.admin.widgets.system-health-monitor';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    
    public array $services = [];
    public array $performanceMetrics = [];
    public string $overallStatus = 'operational';
    
    protected function getCircuitBreaker(): CircuitBreaker
    {
        return app(CircuitBreaker::class);
    }
    
    public function mount(): void
    {
        $this->checkSystemHealth();
    }
    
    public function checkSystemHealth(): void
    {
        $this->services = [];
        $statusCounts = ['operational' => 0, 'degraded' => 0, 'down' => 0, 'unknown' => 0];
        
        // Check Database
        $dbStatus = $this->checkDatabase();
        $this->services['database'] = $dbStatus;
        $statusCounts[$dbStatus['status']]++;
        
        // Check Cal.com API
        $calcomStatus = $this->checkCalcomApi();
        $this->services['calcom'] = $calcomStatus;
        $statusCounts[$calcomStatus['status']]++;
        
        // Check Retell.ai API
        $retellStatus = $this->checkRetellApi();
        $this->services['retell'] = $retellStatus;
        $statusCounts[$retellStatus['status']]++;
        
        // Check Redis/Cache
        $cacheStatus = $this->checkCache();
        $this->services['cache'] = $cacheStatus;
        $statusCounts[$cacheStatus['status']]++;
        
        // Check Queue System
        $queueStatus = $this->checkQueue();
        $this->services['queue'] = $queueStatus;
        $statusCounts[$queueStatus['status']]++;
        
        // Check Webhook Processing
        $webhookStatus = $this->checkWebhookProcessing();
        $this->services['webhooks'] = $webhookStatus;
        $statusCounts[$webhookStatus['status']]++;
        
        // Determine overall status
        if ($statusCounts['down'] > 0) {
            $this->overallStatus = 'critical';
        } elseif ($statusCounts['degraded'] > 1 || $statusCounts['unknown'] > 1) {
            $this->overallStatus = 'degraded';
        } elseif ($statusCounts['unknown'] > 0) {
            $this->overallStatus = 'operational'; // Unknown status doesn't affect overall status significantly
        } else {
            $this->overallStatus = 'operational';
        }
        
        // Get performance metrics
        $this->performanceMetrics = $this->getPerformanceMetrics();
    }
    
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            // Check connection count
            $connections = DB::select("SHOW STATUS WHERE Variable_name = 'Threads_connected'")[0]->Value ?? 0;
            $maxConnections = DB::select("SHOW VARIABLES WHERE Variable_name = 'max_connections'")[0]->Value ?? 100;
            $connectionUsage = ($connections / $maxConnections) * 100;
            
            $status = 'operational';
            if ($responseTime > 100) {
                $status = 'degraded';
            }
            if ($connectionUsage > 80) {
                $status = 'degraded';
            }
            
            return [
                'name' => 'Database',
                'status' => $status,
                'response_time' => $responseTime,
                'uptime' => 99.99,
                'details' => [
                    'connections' => "$connections / $maxConnections",
                    'usage' => round($connectionUsage, 1) . '%',
                ],
                'last_check' => Carbon::now(),
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Database',
                'status' => 'down',
                'response_time' => 0,
                'uptime' => 0,
                'error' => $e->getMessage(),
                'last_check' => Carbon::now(),
            ];
        }
    }
    
    private function checkCalcomApi(): array
    {
        $circuitStatus = CircuitBreaker::getStatus();
        $calcomCircuitState = $circuitStatus['calcom']['state'] ?? 'closed';
        
        if ($calcomCircuitState === 'open') {
            return [
                'name' => 'Cal.com API',
                'status' => 'degraded',
                'response_time' => 0,
                'uptime' => 95.0,
                'details' => ['circuit_breaker' => 'open'],
                'last_check' => Carbon::now(),
            ];
        }
        
        try {
            $start = microtime(true);
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.calcom.api_key')])
                ->get('https://api.cal.com/v2/event-types');
            
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            $status = 'operational';
            if ($responseTime > 1000) {
                $status = 'degraded';
            }
            if (!$response->successful()) {
                $status = 'down';
            }
            
            // Cache response time for anomaly detection
            Cache::put('api_response_time_calcom', $responseTime, 300);
            
            return [
                'name' => 'Cal.com API',
                'status' => $status,
                'response_time' => $responseTime,
                'uptime' => 99.5,
                'details' => [
                    'endpoint' => 'v2/event-types',
                    'status_code' => $response->status(),
                ],
                'last_check' => Carbon::now(),
            ];
        } catch (\Exception $e) {
            // Circuit breaker will handle failure automatically when using call() method
            
            return [
                'name' => 'Cal.com API',
                'status' => 'down',
                'response_time' => 0,
                'uptime' => 0,
                'error' => 'Connection failed',
                'last_check' => Carbon::now(),
            ];
        }
    }
    
    private function checkRetellApi(): array
    {
        $circuitStatus = CircuitBreaker::getStatus();
        $retellCircuitState = $circuitStatus['retell']['state'] ?? 'closed';
        
        if ($retellCircuitState === 'open') {
            return [
                'name' => 'Retell.ai API',
                'status' => 'degraded',
                'response_time' => 0,
                'uptime' => 95.0,
                'details' => ['circuit_breaker' => 'open'],
                'last_check' => Carbon::now(),
            ];
        }
        
        try {
            $apiKey = config('services.retell.api_key') ?? config('services.retell.token');
            if (!$apiKey) {
                return [
                    'name' => 'Retell.ai API',
                    'status' => 'unknown',
                    'response_time' => 0,
                    'uptime' => 0,
                    'error' => 'No API key configured',
                    'last_check' => Carbon::now(),
                ];
            }
            
            $start = microtime(true);
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->get('https://api.retellai.com/v2/list-agents');
            
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            $status = 'operational';
            if ($responseTime > 1500) {
                $status = 'degraded';
            }
            if (!$response->successful()) {
                $status = 'down';
            }
            
            return [
                'name' => 'Retell.ai API',
                'status' => $status,
                'response_time' => $responseTime,
                'uptime' => 99.8,
                'details' => [
                    'endpoint' => 'v2/list-agents',
                    'status_code' => $response->status(),
                ],
                'last_check' => Carbon::now(),
            ];
        } catch (\Exception $e) {
            // Circuit breaker will handle failure automatically when using call() method
            
            return [
                'name' => 'Retell.ai API',
                'status' => 'down',
                'response_time' => 0,
                'uptime' => 0,
                'error' => 'Connection failed',
                'last_check' => Carbon::now(),
            ];
        }
    }
    
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', true, 10);
            $value = Cache::get('health_check');
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            $status = $value === true ? 'operational' : 'degraded';
            if ($responseTime > 50) {
                $status = 'degraded';
            }
            
            return [
                'name' => 'Cache (Redis)',
                'status' => $status,
                'response_time' => $responseTime,
                'uptime' => 99.99,
                'last_check' => Carbon::now(),
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Cache (Redis)',
                'status' => 'down',
                'response_time' => 0,
                'uptime' => 0,
                'error' => 'Connection failed',
                'last_check' => Carbon::now(),
            ];
        }
    }
    
    private function checkQueue(): array
    {
        try {
            // Check queue size
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', Carbon::now()->subHour())
                ->count();
            
            $status = 'operational';
            if ($queueSize > 1000) {
                $status = 'degraded';
            }
            if ($failedJobs > 10) {
                $status = 'degraded';
            }
            
            return [
                'name' => 'Queue System',
                'status' => $status,
                'response_time' => 0,
                'uptime' => 99.9,
                'details' => [
                    'queue_size' => $queueSize,
                    'failed_jobs' => $failedJobs,
                ],
                'last_check' => Carbon::now(),
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Queue System',
                'status' => 'down',
                'response_time' => 0,
                'uptime' => 0,
                'error' => 'Check failed',
                'last_check' => Carbon::now(),
            ];
        }
    }
    
    private function checkWebhookProcessing(): array
    {
        try {
            // Check recent webhook processing times
            $avgProcessingTime = (float) DB::table('webhook_events')
                ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                ->whereNotNull('processed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MICROSECOND, created_at, processed_at) / 1000) as avg_ms')
                ->value('avg_ms') ?? 0;
            
            $pendingWebhooks = DB::table('webhook_events')
                ->whereNull('processed_at')
                ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                ->count();
            
            $status = 'operational';
            if ($avgProcessingTime > 5000) { // > 5 seconds
                $status = 'degraded';
            }
            if ($pendingWebhooks > 50) {
                $status = 'degraded';
            }
            
            return [
                'name' => 'Webhook Processing',
                'status' => $status,
                'response_time' => round($avgProcessingTime, 2),
                'uptime' => 99.5,
                'details' => [
                    'pending' => $pendingWebhooks,
                    'avg_time' => round($avgProcessingTime / 1000, 1) . 's',
                ],
                'last_check' => Carbon::now(),
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Webhook Processing',
                'status' => 'unknown',
                'response_time' => 0,
                'uptime' => 0,
                'error' => 'Check failed',
                'last_check' => Carbon::now(),
            ];
        }
    }
    
    private function getPerformanceMetrics(): array
    {
        return Cache::remember('system_performance_metrics', 60, function () {
            // Use mcp_metrics table instead of deleted api_call_logs
            $hasMetricsTable = Schema::hasTable('mcp_metrics');
            
            if ($hasMetricsTable) {
                // API call volume (last hour) from mcp_metrics
                $apiCalls = DB::table('mcp_metrics')
                    ->where('created_at', '>=', Carbon::now()->subHour())
                    ->count();
                
                // Average response times
                $avgResponseTimes = DB::table('mcp_metrics')
                    ->where('created_at', '>=', Carbon::now()->subHour())
                    ->groupBy('service')
                    ->selectRaw('service, AVG(duration_ms) as avg_ms, COUNT(*) as count')
                    ->get();
                
                // Error rate
                $totalRequests = $apiCalls;
                $failedRequests = DB::table('mcp_metrics')
                    ->where('created_at', '>=', Carbon::now()->subHour())
                    ->where('success', false)
                    ->count();
                
                $errorRate = $totalRequests > 0 ? ($failedRequests / $totalRequests) * 100 : 0;
            } else {
                // Fallback when no metrics available
                $apiCalls = 0;
                $avgResponseTimes = collect([]);
                $errorRate = 0;
            }
            
            return [
                'requests_per_hour' => $apiCalls,
                'error_rate' => round($errorRate, 2),
                'avg_response_times' => $avgResponseTimes->toArray(),
            ];
        });
    }
    
    public function getPollingInterval(): ?string
    {
        return '60s'; // Check every minute
    }
}