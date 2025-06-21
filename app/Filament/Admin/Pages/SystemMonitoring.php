<?php

namespace App\Filament\Admin\Pages;

use App\Services\Monitoring\MetricsCollectorService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SystemMonitoring extends Page
{
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.admin.pages.system-monitoring';
    
    public $systemHealth = [];
    public $performanceMetrics = [];
    public $queueStatus = [];
    public $recentErrors = [];
    
    public function mount(): void
    {
        $this->loadSystemHealth();
        $this->loadPerformanceMetrics();
        $this->loadQueueStatus();
        $this->loadRecentErrors();
    }
    
    protected function loadSystemHealth(): void
    {
        $this->systemHealth = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'disk' => $this->checkDiskSpace(),
            'memory' => $this->checkMemory(),
        ];
    }
    
    protected function loadPerformanceMetrics(): void
    {
        // Response times
        $responseTimeKey = 'response_times:' . date('Y-m-d:H');
        $responseTimes = Cache::get($responseTimeKey, []);
        
        // Cache hit rate
        $info = Redis::info();
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $cacheHitRate = ($hits + $misses) > 0 ? round(($hits / ($hits + $misses)) * 100, 2) : 0;
        
        // Database stats
        $dbStats = DB::select("SHOW STATUS WHERE Variable_name IN ('Queries', 'Slow_queries', 'Threads_connected')");
        $dbMetrics = [];
        foreach ($dbStats as $stat) {
            $dbMetrics[$stat->Variable_name] = $stat->Value;
        }
        
        $this->performanceMetrics = [
            'avg_response_time' => !empty($responseTimes) ? round(array_sum($responseTimes) / count($responseTimes), 2) : 0,
            'cache_hit_rate' => $cacheHitRate,
            'db_queries' => $dbMetrics['Queries'] ?? 0,
            'db_slow_queries' => $dbMetrics['Slow_queries'] ?? 0,
            'db_connections' => $dbMetrics['Threads_connected'] ?? 0,
            'webhooks_processed' => Cache::get('webhook_metrics:' . date('Y-m-d:H') . ':total', 0),
        ];
    }
    
    protected function loadQueueStatus(): void
    {
        $queues = [
            'default' => 'Default Queue',
            'webhooks-high-priority' => 'Webhooks (High)',
            'webhooks-medium-priority' => 'Webhooks (Medium)',
            'webhooks-low-priority' => 'Webhooks (Low)',
        ];
        
        $this->queueStatus = [];
        
        foreach ($queues as $queue => $name) {
            $size = Redis::llen("queues:{$queue}");
            $processing = Redis::zcard("horizon:{$queue}:reserved");
            
            $this->queueStatus[] = [
                'name' => $name,
                'queue' => $queue,
                'pending' => $size,
                'processing' => $processing,
                'status' => $this->getQueueStatus($size),
            ];
        }
        
        // Failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        $this->queueStatus[] = [
            'name' => 'Failed Jobs',
            'queue' => 'failed',
            'pending' => $failedJobs,
            'processing' => 0,
            'status' => $failedJobs > 10 ? 'critical' : ($failedJobs > 0 ? 'warning' : 'healthy'),
        ];
    }
    
    protected function loadRecentErrors(): void
    {
        $this->recentErrors = DB::table('logs')
            ->where('level', 'error')
            ->where('created_at', '>', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($error) {
                return [
                    'message' => Str::limit($error->message, 100),
                    'context' => json_decode($error->context, true),
                    'time' => Carbon::parse($error->created_at)->diffForHumans(),
                ];
            })
            ->toArray();
    }
    
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $time = (microtime(true) - $start) * 1000;
            
            return [
                'status' => 'healthy',
                'message' => 'Connected',
                'response_time' => round($time, 2) . 'ms',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $time = (microtime(true) - $start) * 1000;
            
            $info = Redis::info();
            $memory = $info['used_memory_human'] ?? 'Unknown';
            
            return [
                'status' => 'healthy',
                'message' => 'Connected',
                'response_time' => round($time, 2) . 'ms',
                'memory_usage' => $memory,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkQueue(): array
    {
        $failedJobs = DB::table('failed_jobs')->count();
        
        if ($failedJobs > 100) {
            return [
                'status' => 'critical',
                'message' => "{$failedJobs} failed jobs",
            ];
        } elseif ($failedJobs > 10) {
            return [
                'status' => 'warning',
                'message' => "{$failedJobs} failed jobs",
            ];
        }
        
        return [
            'status' => 'healthy',
            'message' => 'Running smoothly',
            'failed_jobs' => $failedJobs,
        ];
    }
    
    protected function checkDiskSpace(): array
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $used = $total - $free;
        $percentage = round(($used / $total) * 100, 2);
        
        if ($percentage > 90) {
            $status = 'critical';
        } elseif ($percentage > 80) {
            $status = 'warning';
        } else {
            $status = 'healthy';
        }
        
        return [
            'status' => $status,
            'message' => "{$percentage}% used",
            'free' => $this->formatBytes($free),
            'total' => $this->formatBytes($total),
        ];
    }
    
    protected function checkMemory(): array
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->getMemoryLimit();
        
        $percentage = $limit > 0 ? round(($memory / $limit) * 100, 2) : 0;
        
        if ($percentage > 90) {
            $status = 'critical';
        } elseif ($percentage > 80) {
            $status = 'warning';
        } else {
            $status = 'healthy';
        }
        
        return [
            'status' => $status,
            'message' => "{$percentage}% used",
            'current' => $this->formatBytes($memory),
            'peak' => $this->formatBytes($peak),
            'limit' => $this->formatBytes($limit),
        ];
    }
    
    protected function getQueueStatus(int $size): string
    {
        if ($size > 1000) {
            return 'critical';
        } elseif ($size > 100) {
            return 'warning';
        }
        
        return 'healthy';
    }
    
    protected function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    // Auto-refresh every 30 seconds
    public function getPollingInterval(): ?string
    {
        return '30s';
    }
}