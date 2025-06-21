<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MCP\MCPOrchestrator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MCPStreamController extends Controller
{
    protected MCPOrchestrator $mcpOrchestrator;
    
    public function __construct(MCPOrchestrator $mcpOrchestrator)
    {
        $this->mcpOrchestrator = $mcpOrchestrator;
    }
    
    /**
     * Stream real-time MCP updates via Server-Sent Events
     */
    public function stream(Request $request): StreamedResponse
    {
        $tenantId = $request->user()?->company_id;
        
        return new StreamedResponse(function () use ($tenantId) {
            // Set up SSE headers
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable Nginx buffering
            
            // Send initial connection event
            $this->sendEvent('connected', ['timestamp' => now()->toIso8601String()]);
            
            $lastHealthCheck = 0;
            $lastMetricsUpdate = 0;
            $lastErrorCheck = 0;
            $lastQueueCheck = 0;
            
            while (true) {
                $now = time();
                
                try {
                    // Health updates every 30 seconds
                    if ($now - $lastHealthCheck >= 30) {
                        $health = $this->mcpOrchestrator->getSystemHealth();
                        $this->sendEvent('health', $health);
                        $lastHealthCheck = $now;
                    }
                    
                    // Metrics updates every 10 seconds
                    if ($now - $lastMetricsUpdate >= 10) {
                        $metrics = $this->getRealtimeMetrics($tenantId);
                        $this->sendEvent('metrics', $metrics);
                        $lastMetricsUpdate = $now;
                    }
                    
                    // Error detection every 5 seconds
                    if ($now - $lastErrorCheck >= 5) {
                        $errors = $this->checkForNewErrors($tenantId);
                        if (!empty($errors)) {
                            $this->sendEvent('error:detected', $errors);
                        }
                        $lastErrorCheck = $now;
                    }
                    
                    // Queue status every 15 seconds
                    if ($now - $lastQueueCheck >= 15) {
                        $queueStatus = $this->getQueueStatus();
                        $this->sendEvent('queue:update', $queueStatus);
                        $lastQueueCheck = $now;
                    }
                    
                    // Check for service status changes
                    $this->checkServiceStatusChanges();
                    
                    // Send heartbeat
                    $this->sendEvent('heartbeat', ['timestamp' => now()->toIso8601String()]);
                    
                    // Flush output
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                    
                    // Sleep for 2 seconds
                    sleep(2);
                    
                    // Check if client is still connected
                    if (connection_aborted()) {
                        break;
                    }
                    
                } catch (\Exception $e) {
                    Log::error('MCP Stream error', [
                        'error' => $e->getMessage(),
                        'tenant_id' => $tenantId
                    ]);
                    
                    $this->sendEvent('error', [
                        'message' => 'Stream error occurred',
                        'timestamp' => now()->toIso8601String()
                    ]);
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
    
    /**
     * Send SSE event
     */
    protected function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
    }
    
    /**
     * Get real-time metrics
     */
    protected function getRealtimeMetrics(?int $tenantId): array
    {
        $key = "mcp.metrics.realtime.{$tenantId}";
        
        return Cache::remember($key, 5, function () use ($tenantId) {
            $metrics = [];
            
            // Get current active operations
            $activeOps = $this->mcpOrchestrator->getActiveOperations();
            $metrics['active_operations'] = count($activeOps);
            
            // Get request rate (last minute)
            $requestRate = Cache::get("mcp.requests.rate.{$tenantId}", 0);
            $metrics['requests_per_minute'] = $requestRate;
            
            // Get error rate (last minute)
            $errorRate = Cache::get("mcp.errors.rate.{$tenantId}", 0);
            $metrics['errors_per_minute'] = $errorRate;
            
            // Get average latency
            $latencies = Cache::get("mcp.latencies.{$tenantId}", []);
            $metrics['avg_latency'] = !empty($latencies) 
                ? round(array_sum($latencies) / count($latencies), 2)
                : 0;
            
            // Get cache hit rate
            $cacheHits = Cache::get("mcp.cache.hits.{$tenantId}", 0);
            $cacheMisses = Cache::get("mcp.cache.misses.{$tenantId}", 0);
            $totalCacheRequests = $cacheHits + $cacheMisses;
            
            $metrics['cache_hit_rate'] = $totalCacheRequests > 0
                ? round(($cacheHits / $totalCacheRequests) * 100, 2)
                : 0;
            
            return $metrics;
        });
    }
    
    /**
     * Check for new errors
     */
    protected function checkForNewErrors(?int $tenantId): array
    {
        $lastCheckKey = "mcp.errors.lastcheck.{$tenantId}";
        $lastCheck = Cache::get($lastCheckKey, now()->subMinutes(5));
        
        $errors = [];
        
        // Query for new errors since last check
        $newErrors = $this->mcpOrchestrator->getErrorsSince($lastCheck, $tenantId);
        
        if (!empty($newErrors)) {
            $errors = array_map(function ($error) {
                return [
                    'id' => $error['id'],
                    'service' => $error['service'],
                    'message' => $error['message'],
                    'timestamp' => $error['created_at'],
                    'severity' => $error['severity'] ?? 'error'
                ];
            }, $newErrors);
        }
        
        Cache::put($lastCheckKey, now(), 300);
        
        return $errors;
    }
    
    /**
     * Get queue status
     */
    protected function getQueueStatus(): array
    {
        return Cache::remember('mcp.queue.status', 10, function () {
            $queues = [
                'default' => $this->getQueueSize('default'),
                'webhooks' => $this->getQueueSize('webhooks'),
                'mcp' => $this->getQueueSize('mcp'),
                'high' => $this->getQueueSize('high'),
            ];
            
            $workers = $this->mcpOrchestrator->getWorkerStatus();
            
            return [
                'queues' => $queues,
                'workers' => $workers,
                'failed_jobs' => $this->getFailedJobsCount(),
                'timestamp' => now()->toIso8601String()
            ];
        });
    }
    
    /**
     * Get queue size
     */
    protected function getQueueSize(string $queue): array
    {
        $size = \Queue::size($queue);
        
        return [
            'size' => $size,
            'status' => $this->getQueueStatus($size)
        ];
    }
    
    /**
     * Determine queue status based on size
     */
    protected function getQueueStatusLevel(int $size): string
    {
        if ($size > 1000) return 'critical';
        if ($size > 500) return 'high';
        if ($size > 100) return 'medium';
        return 'normal';
    }
    
    /**
     * Get failed jobs count
     */
    protected function getFailedJobsCount(): int
    {
        return \DB::table('failed_jobs')->count();
    }
    
    /**
     * Check for service status changes
     */
    protected function checkServiceStatusChanges(): void
    {
        $currentStatus = $this->mcpOrchestrator->getServiceStatuses();
        $lastStatus = Cache::get('mcp.service.status', []);
        
        foreach ($currentStatus as $service => $status) {
            if (!isset($lastStatus[$service]) || $lastStatus[$service] !== $status) {
                $this->sendEvent('service:status', [
                    'service' => $service,
                    'status' => $status,
                    'previous' => $lastStatus[$service] ?? 'unknown',
                    'timestamp' => now()->toIso8601String()
                ]);
            }
        }
        
        Cache::put('mcp.service.status', $currentStatus, 60);
    }
}