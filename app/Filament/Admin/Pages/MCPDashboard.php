<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\MCP\MCPOrchestrator;
use App\Services\Database\ConnectionPoolManager;
use App\Services\MCP\QueueMCPServer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class MCPDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'MCP System Monitor';
    protected static ?string $navigationGroup = 'System & Verwaltung';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.admin.pages.mcp-dashboard';
    protected static ?string $slug = 'mcp-dashboard';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public array $systemHealth = [];
    public array $performanceMetrics = [];
    public array $connectionPoolStats = [];
    public array $queueStatus = [];
    public array $recentErrors = [];
    public array $activeOperations = [];
    public array $serviceMetrics = [];
    
    protected MCPOrchestrator $orchestrator;
    protected QueueMCPServer $queueMCP;
    
    public function boot(
        MCPOrchestrator $orchestrator,
        QueueMCPServer $queueMCP
    ): void {
        $this->orchestrator = $orchestrator;
        $this->queueMCP = $queueMCP;
    }
    
    public function mount(): void
    {
        $this->loadAllData();
    }
    
    #[On('refresh-data')]
    public function loadAllData(): void
    {
        $this->loadSystemHealth();
        $this->loadPerformanceMetrics();
        $this->loadConnectionPoolStats();
        $this->loadQueueStatus();
        $this->loadRecentErrors();
        $this->loadActiveOperations();
        $this->loadServiceMetrics();
    }
    
    protected function loadSystemHealth(): void
    {
        try {
            $health = $this->orchestrator->healthCheck();
            $this->systemHealth = [
                'status' => $health['status'] ?? 'unknown',
                'services' => $health['services'] ?? [],
                'metrics' => $health['metrics'] ?? [],
                'last_check' => now()->format('H:i:s'),
            ];
        } catch (\Exception $e) {
            $this->systemHealth = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function loadPerformanceMetrics(): void
    {
        try {
            $metrics = $this->orchestrator->getMetrics();
            $this->performanceMetrics = [
                'total_requests' => number_format($metrics['total_requests'] ?? 0),
                'total_errors' => number_format($metrics['total_errors'] ?? 0),
                'error_rate' => $metrics['error_rate'] ?? 0,
                'avg_latency' => $metrics['avg_latency_ms'] ?? 0,
                'p99_latency' => $metrics['p99_latency_ms'] ?? 0,
            ];
        } catch (\Exception $e) {
            $this->performanceMetrics = ['error' => $e->getMessage()];
        }
    }
    
    protected function loadConnectionPoolStats(): void
    {
        try {
            $stats = ConnectionPoolManager::getStats();
            
            // Transform the flat stats into pool structure
            $this->connectionPoolStats = [
                'write' => [
                    'idle' => $stats['idle_connections'] ?? 0,
                    'active' => $stats['active_connections'] ?? 0,
                    'total' => $stats['write_pool_size'] ?? 0,
                    'utilization' => $stats['write_pool_size'] > 0 
                        ? round(($stats['active_connections'] / $stats['write_pool_size']) * 100, 1) 
                        : 0,
                ],
                'read' => [
                    'idle' => $stats['idle_connections'] ?? 0,
                    'active' => $stats['active_connections'] ?? 0,
                    'total' => $stats['read_pool_size'] ?? 0,
                    'utilization' => $stats['read_pool_size'] > 0 
                        ? round(($stats['active_connections'] / $stats['read_pool_size']) * 100, 1) 
                        : 0,
                ],
            ];
        } catch (\Exception $e) {
            $this->connectionPoolStats = ['error' => $e->getMessage()];
        }
    }
    
    protected function loadQueueStatus(): void
    {
        try {
            $overview = $this->queueMCP->getOverview();
            $this->queueStatus = [
                'horizon_status' => $overview['horizon_status'] ?? 'unknown',
                'failed_jobs' => number_format($overview['failed_jobs'] ?? 0),
                'queues' => $overview['queues'] ?? [],
                'workers' => $overview['workers'] ?? [],
                'throughput' => $overview['throughput'] ?? [],
            ];
        } catch (\Exception $e) {
            $this->queueStatus = ['error' => $e->getMessage()];
        }
    }
    
    protected function loadRecentErrors(): void
    {
        try {
            $this->recentErrors = DB::table('mcp_metrics')
                ->where('success', false)
                ->where('created_at', '>=', now()->subMinutes(30))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($error) {
                    $metadata = json_decode($error->metadata ?? '{}', true);
                    return [
                        'service' => $error->service,
                        'operation' => $error->operation ?? 'unknown',
                        'error' => $metadata['error'] ?? 'Unknown error',
                        'tenant_id' => $error->tenant_id,
                        'time' => \Carbon\Carbon::parse($error->created_at)->diffForHumans(),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->recentErrors = [];
        }
    }
    
    protected function loadActiveOperations(): void
    {
        try {
            $activeOps = Cache::get('mcp:active_operations', []);
            $this->activeOperations = collect($activeOps)->map(function ($op) {
                return [
                    'id' => substr($op['id'] ?? '', 0, 8),
                    'service' => $op['service'] ?? 'unknown',
                    'operation' => $op['operation'] ?? 'unknown',
                    'duration' => number_format((microtime(true) - ($op['start_time'] ?? 0)) * 1000, 0),
                    'tenant_id' => $op['tenant_id'] ?? null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            $this->activeOperations = [];
        }
    }
    
    protected function loadServiceMetrics(): void
    {
        try {
            $this->serviceMetrics = DB::table('mcp_metrics')
                ->select(
                    'service',
                    DB::raw('COUNT(*) as total_requests'),
                    DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_requests'),
                    DB::raw('AVG(duration_ms) as avg_duration'),
                    DB::raw('MAX(duration_ms) as max_duration'),
                    DB::raw('MIN(duration_ms) as min_duration')
                )
                ->where('created_at', '>=', now()->subHours(1))
                ->groupBy('service')
                ->get()
                ->map(function ($metric) {
                    return [
                        'service' => $metric->service,
                        'requests' => number_format($metric->total_requests),
                        'success_rate' => $metric->total_requests > 0 
                            ? round(($metric->successful_requests / $metric->total_requests) * 100, 1) 
                            : 0,
                        'avg_duration' => round($metric->avg_duration, 1),
                        'max_duration' => round($metric->max_duration, 1),
                        'min_duration' => round($metric->min_duration, 1),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->serviceMetrics = [];
        }
    }
    
    public function refreshData(): void
    {
        $this->loadAllData();
        $this->dispatch('data-refreshed');
    }
    
    protected function getListeners(): array
    {
        return [
            'refresh-data' => 'loadAllData',
        ];
    }
}