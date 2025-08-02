<?php

namespace App\Filament\Admin\Pages;

use App\Services\FeatureFlagService;
use App\Services\Monitoring\HealthCheckService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DeploymentMonitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Deployment Monitor';
    protected static string $view = 'filament.admin.pages.deployment-monitor';
    protected static ?int $navigationSort = 10;
    
    public $deploymentStatus = [];
    public $systemHealth = [];
    public $featureFlags = [];
    public $performanceMetrics = [];
    public $recentDeployments = [];
    public $alerts = [];

    public function mount(): void
    {
        $this->loadDeploymentStatus();
        $this->loadSystemHealth();
        $this->loadFeatureFlags();
        $this->loadPerformanceMetrics();
        $this->loadRecentDeployments();
        $this->loadAlerts();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshData'),
                
            Action::make('emergency_rollback')
                ->label('Emergency Rollback')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Emergency Rollback')
                ->modalDescription('This will immediately disable all feature flags and trigger a rollback. Are you sure?')
                ->action('emergencyRollback'),
                
            Action::make('health_check')
                ->label('Run Health Check')
                ->icon('heroicon-o-heart')
                ->action('runHealthCheck'),
        ];
    }

    public function refreshData(): void
    {
        $this->mount();
        $this->notification()
            ->title('Data refreshed')
            ->success()
            ->send();
    }

    public function emergencyRollback(): void
    {
        try {
            $featureFlagService = app(FeatureFlagService::class);
            $featureFlagService->emergencyDisableAll('Emergency rollback from monitoring dashboard');
            
            // Log the action
            Log::critical('Emergency rollback triggered from deployment monitor', [
                'user' => auth()->user()?->email,
                'timestamp' => now()->toISOString()
            ]);
            
            $this->notification()
                ->title('Emergency rollback initiated')
                ->body('All feature flags have been disabled. Check logs for details.')
                ->danger()
                ->send();
                
            // Refresh data to show updated status
            $this->mount();
            
        } catch (\Exception $e) {
            $this->notification()
                ->title('Emergency rollback failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runHealthCheck(): void
    {
        try {
            $healthService = app(HealthCheckService::class);
            $result = $healthService->check();
            
            $this->notification()
                ->title('Health check completed')
                ->body("Status: {$result['status']}")
                ->color($result['status'] === 'healthy' ? 'success' : 'danger')
                ->send();
                
            $this->loadSystemHealth();
            
        } catch (\Exception $e) {
            $this->notification()
                ->title('Health check failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function loadDeploymentStatus(): void
    {
        // Get current deployment status
        $statusFiles = glob(storage_path('deployment/status-*.txt'));
        $currentDeployment = null;
        
        if (!empty($statusFiles)) {
            $latestStatusFile = end($statusFiles);
            $deploymentId = basename($latestStatusFile, '.txt');
            $status = file_get_contents($latestStatusFile);
            
            $currentDeployment = [
                'id' => str_replace('status-', '', $deploymentId),
                'status' => trim($status),
                'timestamp' => filemtime($latestStatusFile),
            ];
        }

        $this->deploymentStatus = [
            'current' => $currentDeployment,
            'in_progress' => $currentDeployment && in_array($currentDeployment['status'], ['deploying', 'validating']),
            'last_successful' => $this->getLastSuccessfulDeployment(),
            'rollback_available' => file_exists(storage_path('deployment/previous-commit.txt')),
        ];
    }

    private function loadSystemHealth(): void
    {
        try {
            $healthService = app(HealthCheckService::class);
            $health = $healthService->check();
            
            $this->systemHealth = [
                'overall_status' => $health['status'] ?? 'unknown',
                'checks' => $health['checks'] ?? [],
                'timestamp' => $health['timestamp'] ?? now()->toISOString(),
                'response_time' => $this->getAverageResponseTime(),
                'error_rate' => $this->getErrorRate(),
                'uptime' => $this->getUptime(),
            ];
        } catch (\Exception $e) {
            $this->systemHealth = [
                'overall_status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    private function loadFeatureFlags(): void
    {
        try {
            $featureFlagService = app(FeatureFlagService::class);
            $flags = $featureFlagService->getAllFlags();
            
            $this->featureFlags = [
                'total' => count($flags),
                'enabled' => count(array_filter($flags, fn($flag) => $flag->enabled)),
                'partial_rollout' => count(array_filter($flags, fn($flag) => $flag->enabled && $flag->rollout_percentage < 100)),
                'flags' => array_map(function($flag) use ($featureFlagService) {
                    $stats = $featureFlagService->getUsageStats($flag->key, 1);
                    return [
                        'key' => $flag->key,
                        'name' => $flag->name,
                        'enabled' => $flag->enabled,
                        'rollout_percentage' => $flag->rollout_percentage,
                        'evaluations_last_hour' => $stats['total_evaluations'] ?? 0,
                    ];
                }, array_slice($flags, 0, 10)) // Show top 10
            ];
        } catch (\Exception $e) {
            $this->featureFlags = [
                'error' => $e->getMessage(),
                'total' => 0,
                'enabled' => 0,
            ];
        }
    }

    private function loadPerformanceMetrics(): void
    {
        $this->performanceMetrics = [
            'response_time' => $this->getAverageResponseTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'database_connections' => $this->getDatabaseConnections(),
            'queue_size' => $this->getQueueSize(),
            'cache_hit_rate' => $this->getCacheHitRate(),
        ];
    }

    private function loadRecentDeployments(): void
    {
        $deploymentFiles = glob(storage_path('deployment/deploy-*.log'));
        rsort($deploymentFiles); // Sort by newest first
        
        $this->recentDeployments = array_map(function($file) {
            $filename = basename($file, '.log');
            $deploymentId = str_replace('deploy-', '', $filename);
            
            return [
                'id' => $deploymentId,
                'timestamp' => filemtime($file),
                'status' => $this->getDeploymentStatusFromLog($file),
                'duration' => $this->getDeploymentDuration($file),
            ];
        }, array_slice($deploymentFiles, 0, 5)); // Show last 5 deployments
    }

    private function loadAlerts(): void
    {
        $this->alerts = [];
        
        // Check for high error rate
        $errorRate = $this->getErrorRate();
        if ($errorRate > 5) {
            $this->alerts[] = [
                'type' => 'error',
                'title' => 'High Error Rate',
                'message' => "Current error rate: {$errorRate}%",
                'timestamp' => now(),
            ];
        }
        
        // Check for slow response times
        $responseTime = $this->getAverageResponseTime();
        if ($responseTime > 2000) {
            $this->alerts[] = [
                'type' => 'warning',
                'title' => 'Slow Response Times',
                'message' => "Average response time: {$responseTime}ms",
                'timestamp' => now(),
            ];
        }
        
        // Check for failed deployments
        if ($this->deploymentStatus['current'] && $this->deploymentStatus['current']['status'] === 'failed') {
            $this->alerts[] = [
                'type' => 'error',
                'title' => 'Deployment Failed',
                'message' => 'Latest deployment failed. Consider rollback.',
                'timestamp' => now(),
            ];
        }
        
        // Check queue size
        $queueSize = $this->getQueueSize();
        if ($queueSize > 1000) {
            $this->alerts[] = [
                'type' => 'warning',
                'title' => 'Large Queue Backlog',
                'message' => "Queue size: {$queueSize} jobs",
                'timestamp' => now(),
            ];
        }
    }

    private function getAverageResponseTime(): float
    {
        // This would typically come from your monitoring system
        // For now, return a cached value or calculate from logs
        return Cache::remember('avg_response_time', 300, function() {
            try {
                // Simple curl test to health endpoint
                $start = microtime(true);
                $ch = curl_init('https://api.askproai.de/health');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_exec($ch);
                $end = microtime(true);
                curl_close($ch);
                
                return round(($end - $start) * 1000, 2);
            } catch (\Exception $e) {
                return 0;
            }
        });
    }

    private function getErrorRate(): float
    {
        return Cache::remember('error_rate', 300, function() {
            try {
                $logFile = storage_path('logs/laravel.log');
                if (!file_exists($logFile)) {
                    return 0;
                }
                
                $lines = array_slice(file($logFile), -1000); // Last 1000 lines
                $errorCount = 0;
                $totalLines = count($lines);
                
                foreach ($lines as $line) {
                    if (str_contains($line, 'ERROR') || str_contains($line, 'CRITICAL')) {
                        $errorCount++;
                    }
                }
                
                return $totalLines > 0 ? round(($errorCount / $totalLines) * 100, 2) : 0;
            } catch (\Exception $e) {
                return 0;
            }
        });
    }

    private function getUptime(): string
    {
        // This would typically come from your monitoring system
        return Cache::remember('system_uptime', 3600, function() {
            try {
                $uptime = shell_exec('uptime -p');
                return trim($uptime) ?: 'Unknown';
            } catch (\Exception $e) {
                return 'Unknown';
            }
        });
    }

    private function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimitInBytes();
        
        return [
            'used_mb' => round($memoryUsage / 1024 / 1024, 2),
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'percentage' => round(($memoryUsage / $memoryLimit) * 100, 2),
        ];
    }

    private function getCpuUsage(): float
    {
        return Cache::remember('cpu_usage', 60, function() {
            try {
                $load = sys_getloadavg();
                return round($load[0] * 100, 2);
            } catch (\Exception $e) {
                return 0;
            }
        });
    }

    private function getDatabaseConnections(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return (int) ($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getQueueSize(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getCacheHitRate(): float
    {
        // This would typically come from Redis stats
        return Cache::remember('cache_hit_rate', 300, function() {
            try {
                // Mock calculation - would use actual Redis stats
                return 85.4;
            } catch (\Exception $e) {
                return 0;
            }
        });
    }

    private function getLastSuccessfulDeployment(): ?array
    {
        $successFile = storage_path('deployment/last-successful-deployment.txt');
        if (file_exists($successFile)) {
            return [
                'commit' => trim(file_get_contents($successFile)),
                'timestamp' => filemtime($successFile),
            ];
        }
        return null;
    }

    private function getDeploymentStatusFromLog(string $logFile): string
    {
        if (!file_exists($logFile)) {
            return 'unknown';
        }
        
        $content = file_get_contents($logFile);
        if (str_contains($content, 'Deployment completed successfully')) {
            return 'success';
        } elseif (str_contains($content, 'Deployment failed')) {
            return 'failed';
        } elseif (str_contains($content, 'Starting deployment')) {
            return 'in_progress';
        }
        
        return 'unknown';
    }

    private function getDeploymentDuration(string $logFile): ?int
    {
        if (!file_exists($logFile)) {
            return null;
        }
        
        $lines = file($logFile);
        $startTime = null;
        $endTime = null;
        
        foreach ($lines as $line) {
            if (str_contains($line, 'Starting deployment') && preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $startTime = $matches[1];
            } elseif (str_contains($line, 'complete') && preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $endTime = $matches[1];
            }
        }
        
        if ($startTime && $endTime) {
            return strtotime($endTime) - strtotime($startTime);
        }
        
        return null;
    }

    private function getMemoryLimitInBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit == -1) {
            return PHP_INT_MAX;
        }

        preg_match('/^(\d+)(.)$/', $memoryLimit, $matches);
        if (!$matches) {
            return PHP_INT_MAX;
        }

        $value = (int) $matches[1];
        $unit = strtolower($matches[2]);

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return $value;
        }
    }
}