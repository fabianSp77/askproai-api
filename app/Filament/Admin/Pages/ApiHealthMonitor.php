<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\CircuitBreaker\CircuitBreaker;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApiHealthMonitor extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'API Health Monitor';
    protected static ?string $title = '🩺 API Health Monitor';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 100;
    
    protected static string $view = 'filament.admin.pages.api-health-monitor';
    
    public $autoRefresh = true;
    public $refreshInterval = 30; // seconds
    
    // Real-time metrics
    public array $metrics = [];
    public array $circuitBreakerStatus = [];
    public array $recentErrors = [];
    public array $performanceStats = [];
    
    public function mount(): void
    {
        $this->loadMetrics();
    }
    
    public function loadMetrics(): void
    {
        // Get Circuit Breaker Status
        $this->circuitBreakerStatus = CircuitBreaker::getStatus();
        
        // Get Recent API Metrics (last hour)
        $hourAgo = now()->subHour();
        
        $this->metrics = DB::table('circuit_breaker_metrics')
            ->select('service', 'status', DB::raw('COUNT(*) as count'), DB::raw('AVG(duration_ms) as avg_duration'))
            ->where('created_at', '>=', $hourAgo)
            ->groupBy('service', 'status')
            ->get()
            ->groupBy('service')
            ->map(function ($group) {
                $stats = [
                    'total' => 0,
                    'success' => 0,
                    'failure' => 0,
                    'avg_duration' => 0,
                    'success_rate' => 0,
                ];
                
                foreach ($group as $metric) {
                    $stats['total'] += $metric->count;
                    if ($metric->status === 'success') {
                        $stats['success'] = $metric->count;
                        $stats['avg_duration'] = round($metric->avg_duration, 2);
                    } else {
                        $stats['failure'] = $metric->count;
                    }
                }
                
                $stats['success_rate'] = $stats['total'] > 0 
                    ? round(($stats['success'] / $stats['total']) * 100, 2)
                    : 0;
                
                return $stats;
            })
            ->toArray();
        
        // Get Recent Errors
        $this->recentErrors = DB::table('critical_errors')
            ->select('service', 'error_type', 'message', 'created_at')
            ->where('created_at', '>=', $hourAgo)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($error) {
                $error->time_ago = Carbon::parse($error->created_at)->diffForHumans();
                return $error;
            })
            ->toArray();
        
        // Get Performance Stats
        $this->performanceStats = DB::table('circuit_breaker_metrics')
            ->select(
                'service',
                DB::raw('MIN(duration_ms) as min_duration'),
                DB::raw('AVG(duration_ms) as avg_duration'),
                DB::raw('MAX(duration_ms) as max_duration'),
                DB::raw('STDDEV(duration_ms) as stddev_duration')
            )
            ->where('created_at', '>=', $hourAgo)
            ->where('status', 'success')
            ->groupBy('service')
            ->get()
            ->mapWithKeys(function ($stat) {
                return [$stat->service => [
                    'min' => round($stat->min_duration, 2),
                    'avg' => round($stat->avg_duration, 2),
                    'max' => round($stat->max_duration, 2),
                    'stddev' => round($stat->stddev_duration, 2),
                ]];
            })
            ->toArray();
    }
    
    public function getHealthStatus(string $service): array
    {
        $metrics = $this->metrics[$service] ?? null;
        $circuitBreaker = $this->circuitBreakerStatus[$service] ?? null;
        
        if (!$metrics || !$circuitBreaker) {
            return [
                'status' => 'unknown',
                'color' => 'gray',
                'icon' => 'heroicon-o-question-mark-circle',
                'message' => 'No data available',
            ];
        }
        
        // Determine health based on multiple factors
        $successRate = $metrics['success_rate'];
        $avgDuration = $metrics['avg_duration'];
        $circuitState = $circuitBreaker['state'];
        
        if ($circuitState === 'open') {
            return [
                'status' => 'critical',
                'color' => 'danger',
                'icon' => 'heroicon-o-x-circle',
                'message' => 'Circuit breaker OPEN - Service is down',
            ];
        }
        
        if ($successRate < 50) {
            return [
                'status' => 'critical',
                'color' => 'danger',
                'icon' => 'heroicon-o-exclamation-circle',
                'message' => "Critical: {$successRate}% success rate",
            ];
        }
        
        if ($successRate < 90 || $avgDuration > 1000) {
            return [
                'status' => 'warning',
                'color' => 'warning',
                'icon' => 'heroicon-o-exclamation-triangle',
                'message' => "Warning: {$successRate}% success, {$avgDuration}ms avg",
            ];
        }
        
        return [
            'status' => 'healthy',
            'color' => 'success',
            'icon' => 'heroicon-o-check-circle',
            'message' => "Healthy: {$successRate}% success, {$avgDuration}ms avg",
        ];
    }
    
    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->dispatch('start-auto-refresh');
        } else {
            $this->dispatch('stop-auto-refresh');
        }
    }
    
    public function refresh(): void
    {
        $this->loadMetrics();
        $this->dispatch('metrics-updated');
    }
    
    public function resetCircuitBreaker(string $service): void
    {
        try {
            $circuitBreaker = new CircuitBreaker();
            
            // Reset the circuit breaker state
            Cache::put("circuit_breaker:{$service}:state", 'closed');
            Cache::put("circuit_breaker:{$service}:failures", 0);
            Cache::put("circuit_breaker:{$service}:last_failure", null);
            
            \Filament\Notifications\Notification::make()
                ->title('Circuit Breaker Reset')
                ->body("Circuit breaker for {$service} has been reset to CLOSED state")
                ->success()
                ->send();
                
            $this->loadMetrics();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Reset Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function getOverallHealth(): array
    {
        $services = ['calcom', 'retell'];
        $unhealthyCount = 0;
        $warningCount = 0;
        
        foreach ($services as $service) {
            $health = $this->getHealthStatus($service);
            if ($health['status'] === 'critical') {
                $unhealthyCount++;
            } elseif ($health['status'] === 'warning') {
                $warningCount++;
            }
        }
        
        if ($unhealthyCount > 0) {
            return [
                'status' => 'critical',
                'color' => 'danger',
                'message' => "{$unhealthyCount} service(s) down",
            ];
        }
        
        if ($warningCount > 0) {
            return [
                'status' => 'warning',
                'color' => 'warning',
                'message' => "{$warningCount} service(s) degraded",
            ];
        }
        
        return [
            'status' => 'healthy',
            'color' => 'success',
            'message' => 'All services operational',
        ];
    }
    
    public function exportMetrics(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = [
            'timestamp' => now()->toIso8601String(),
            'overall_health' => $this->getOverallHealth(),
            'services' => [],
        ];
        
        foreach (['calcom', 'retell'] as $service) {
            $data['services'][$service] = [
                'health' => $this->getHealthStatus($service),
                'metrics' => $this->metrics[$service] ?? [],
                'circuit_breaker' => $this->circuitBreakerStatus[$service] ?? [],
                'performance' => $this->performanceStats[$service] ?? [],
            ];
        }
        
        $filename = 'api-health-report-' . now()->format('Y-m-d-His') . '.json';
        
        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename);
    }
}