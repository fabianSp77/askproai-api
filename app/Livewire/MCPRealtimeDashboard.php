<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\MCP\MCPOrchestrator;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class MCPRealtimeDashboard extends Component
{
    public $systemHealth = [];
    public $serviceMetrics = [];
    public $activeOperations = [];
    public $recentErrors = [];
    public $performanceMetrics = [];
    
    // Real-time update flags
    public $autoRefresh = true;
    public $refreshInterval = 5; // seconds
    public $lastUpdate;
    
    // Filter options
    public $selectedService = 'all';
    public $timeRange = '1h';
    
    protected $mcpOrchestrator;
    
    public function boot(MCPOrchestrator $mcpOrchestrator)
    {
        $this->mcpOrchestrator = $mcpOrchestrator;
    }
    
    public function mount()
    {
        $this->loadDashboardData();
        $this->lastUpdate = now()->format('H:i:s');
    }
    
    public function loadDashboardData()
    {
        // Use cache with short TTL for real-time feel
        $this->systemHealth = Cache::remember('mcp.health', 30, function () {
            return $this->mcpOrchestrator->getSystemHealth();
        });
        
        $this->serviceMetrics = Cache::remember("mcp.metrics.{$this->timeRange}", 60, function () {
            return $this->mcpOrchestrator->getServiceMetrics($this->timeRange);
        });
        
        $this->activeOperations = Cache::remember('mcp.operations', 10, function () {
            return $this->mcpOrchestrator->getActiveOperations();
        });
        
        $this->recentErrors = Cache::remember('mcp.errors', 30, function () {
            return $this->mcpOrchestrator->getRecentErrors(10);
        });
        
        $this->performanceMetrics = $this->calculatePerformanceMetrics();
    }
    
    protected function calculatePerformanceMetrics()
    {
        $metrics = [];
        
        foreach ($this->serviceMetrics as $service => $data) {
            $metrics[$service] = [
                'requests_per_second' => round($data['total_requests'] / 3600, 2),
                'error_rate' => $data['total_requests'] > 0 
                    ? round(($data['failed_requests'] / $data['total_requests']) * 100, 2)
                    : 0,
                'avg_latency' => round($data['avg_latency'] ?? 0, 2),
                'p95_latency' => round($data['p95_latency'] ?? 0, 2),
                'p99_latency' => round($data['p99_latency'] ?? 0, 2),
            ];
        }
        
        return $metrics;
    }
    
    public function refreshData()
    {
        $this->loadDashboardData();
        $this->lastUpdate = now()->format('H:i:s');
        
        // Emit browser event for JavaScript updates
        $this->dispatch('mcp-data-refreshed', [
            'timestamp' => now()->timestamp,
            'health' => $this->systemHealth
        ]);
    }
    
    #[On('service-selected')]
    public function filterByService($service)
    {
        $this->selectedService = $service;
        $this->loadDashboardData();
    }
    
    #[On('time-range-changed')]
    public function changeTimeRange($range)
    {
        $this->timeRange = $range;
        $this->loadDashboardData();
    }
    
    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
    }
    
    public function clearCache($service = null)
    {
        if ($service) {
            Cache::forget("mcp.cache.{$service}");
            $this->mcpOrchestrator->clearServiceCache($service);
        } else {
            Cache::tags(['mcp'])->flush();
        }
        
        $this->loadDashboardData();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $service 
                ? "Cache cleared for {$service}" 
                : 'All MCP caches cleared'
        ]);
    }
    
    public function retryFailedOperation($operationId)
    {
        try {
            $result = $this->mcpOrchestrator->retryOperation($operationId);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Operation retried successfully'
            ]);
            
            $this->loadDashboardData();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to retry operation: ' . $e->getMessage()
            ]);
        }
    }
    
    public function acknowledgeError($errorId)
    {
        $this->mcpOrchestrator->acknowledgeError($errorId);
        $this->loadDashboardData();
    }
    
    public function render()
    {
        return view('livewire.mcp-realtime-dashboard', [
            'services' => array_keys($this->serviceMetrics),
            'healthScore' => $this->calculateHealthScore(),
            'criticalAlerts' => $this->getCriticalAlerts(),
        ]);
    }
    
    protected function calculateHealthScore()
    {
        $totalScore = 0;
        $serviceCount = 0;
        
        foreach ($this->systemHealth['services'] ?? [] as $service => $status) {
            $serviceCount++;
            if ($status === 'healthy') {
                $totalScore += 100;
            } elseif ($status === 'degraded') {
                $totalScore += 50;
            }
        }
        
        return $serviceCount > 0 ? round($totalScore / $serviceCount) : 0;
    }
    
    protected function getCriticalAlerts()
    {
        $alerts = [];
        
        // Check error rates
        foreach ($this->performanceMetrics as $service => $metrics) {
            if ($metrics['error_rate'] > 5) {
                $alerts[] = [
                    'type' => 'error',
                    'service' => $service,
                    'message' => "High error rate: {$metrics['error_rate']}%"
                ];
            }
            
            if ($metrics['p99_latency'] > 1000) {
                $alerts[] = [
                    'type' => 'warning',
                    'service' => $service,
                    'message' => "High latency: {$metrics['p99_latency']}ms"
                ];
            }
        }
        
        // Check active operations
        $stuckOperations = collect($this->activeOperations)
            ->filter(fn($op) => $op['duration'] > 30000)
            ->count();
            
        if ($stuckOperations > 0) {
            $alerts[] = [
                'type' => 'warning',
                'service' => 'system',
                'message' => "{$stuckOperations} operations running > 30s"
            ];
        }
        
        return $alerts;
    }
}