<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class SystemHealthOverview extends Widget
{
    protected static string $view = 'filament.admin.widgets.system-health-overview';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
    
    public ?array $healthMetrics = null;
    
    public function mount(): void
    {
        $this->loadHealthMetrics();
    }
    
    public function loadHealthMetrics(): void
    {
        $this->healthMetrics = Cache::get('system_health_metrics', [
            'database' => ['connection_time' => 0, 'connection_usage' => 0, 'slow_queries' => 0],
            'queue' => ['horizon_running' => false, 'failed_jobs' => 0, 'recent_failures' => 0],
            'api' => [],
            'business' => ['stale_calls' => 0, 'appointment_conflicts' => 0, 'inactive_companies' => 0],
            'system' => ['disk_usage' => 0, 'memory_usage' => 0, 'load_average' => ['1m' => 0]],
        ]);
    }
    
    public function getHealthStatus(): string
    {
        if (empty($this->healthMetrics)) {
            return 'unknown';
        }
        
        // Check for critical issues
        if (
            !($this->healthMetrics['queue']['horizon_running'] ?? false) ||
            ($this->healthMetrics['system']['disk_usage'] ?? 0) > 90 ||
            ($this->healthMetrics['system']['memory_usage'] ?? 0) > 90
        ) {
            return 'critical';
        }
        
        // Check for warnings
        if (
            ($this->healthMetrics['database']['slow_queries'] ?? 0) > 5 ||
            ($this->healthMetrics['queue']['recent_failures'] ?? 0) > 10 ||
            ($this->healthMetrics['business']['appointment_conflicts'] ?? 0) > 0
        ) {
            return 'warning';
        }
        
        return 'healthy';
    }
    
    public function refresh(): void
    {
        $this->loadHealthMetrics();
    }
    
    public static function canView(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']);
    }
}