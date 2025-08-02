<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Traits\HasTooltips;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Services\CircuitBreaker\CircuitBreakerService;
use Filament\Pages\Page;
use Filament\Pages\Actions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use App\Services\NavigationService;

class SystemMonitoringDashboard extends Page
{
    use HasTooltips;
    
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'System Monitoring';
    protected static string $view = 'filament.admin.pages.system-monitoring-dashboard';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public static function getNavigationGroup(): ?string
    {
        $config = NavigationService::getNavigationForResource(static::class);
        return $config['group'];
    }
    
    public static function getNavigationSort(): ?int
    {
        $config = NavigationService::getNavigationForResource(static::class);
        return $config['sort'];
    }

    public array $systemMetrics = [];
    public array $apiStatus = [];
    public array $performanceMetrics = [];
    public array $realtimeStats = [
        'active_calls' => 0,
        'today_appointments' => 0,
        'completed_today' => 0,
        'active_companies' => 0,
        'active_phones' => 0,
        'recent_webhooks' => 0,
    ];
    public array $errorLogs = [];
    public array $queueStatus = [];
    
    public bool $autoRefresh = true;
    public int $refreshInterval = 30; // seconds

    public function mount(): void
    {
        // Check if user has permission
        if (!static::canAccess()) {
            abort(403);
        }
        $this->loadAllMetrics();
    }

    public function loadAllMetrics(): void
    {
        // Load metrics concurrently for better performance
        $promises = [
            'system' => fn() => $this->loadSystemMetrics(),
            'api' => fn() => $this->loadApiStatus(),
            'performance' => fn() => $this->loadPerformanceMetrics(),
            'realtime' => fn() => $this->loadRealtimeStats(),
            'errors' => fn() => $this->loadErrorLogs(),
            'queue' => fn() => $this->loadQueueStatus(),
        ];
        
        // Execute all loads - they'll run independently
        foreach ($promises as $key => $loader) {
            try {
                $loader();
            } catch (\Exception $e) {
                \Log::error("Failed to load {$key} metrics", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    protected function loadSystemMetrics(): void
    {
        // Database metrics
        $dbStats = DB::select("
            SELECT 
                COUNT(*) as total_connections,
                COUNT(CASE WHEN command != 'Sleep' THEN 1 END) as active_queries
            FROM information_schema.processlist
        ")[0];

        // Redis metrics
        try {
            $redisInfo = Redis::info();
            $redisMemory = $redisInfo['used_memory_human'] ?? 'N/A';
            $redisConnections = $redisInfo['connected_clients'] ?? 0;
        } catch (\Exception $e) {
            $redisMemory = 'Offline';
            $redisConnections = 0;
            \Log::warning('Redis connection failed in monitoring dashboard', [
                'error' => $e->getMessage()
            ]);
        }

        // Server metrics
        $serverLoad = sys_getloadavg();
        $diskUsage = round((disk_total_space('/') - disk_free_space('/')) / disk_total_space('/') * 100, 2);
        $memoryUsage = $this->getMemoryUsage();

        $this->systemMetrics = [
            'database' => [
                'status' => $dbStats->total_connections > 0 ? 'online' : 'offline',
                'connections' => $dbStats->total_connections,
                'active_queries' => $dbStats->active_queries,
            ],
            'redis' => [
                'status' => $redisMemory !== 'Error' ? 'online' : 'offline',
                'memory' => $redisMemory,
                'connections' => $redisConnections,
            ],
            'server' => [
                'load_average' => [
                    '1m' => round($serverLoad[0], 2),
                    '5m' => round($serverLoad[1], 2),
                    '15m' => round($serverLoad[2], 2),
                ],
                'disk_usage' => $diskUsage . '%',
                'memory_usage' => $memoryUsage . '%',
            ],
        ];
    }

    protected function loadApiStatus(): void
    {
        $apis = [
            'calcom' => [
                'name' => 'Cal.com',
                'endpoint' => 'https://api.cal.com/v2/health',
                'timeout' => 5,
            ],
            'retell' => [
                'name' => 'Retell.ai',
                'endpoint' => 'https://api.retellai.com',
                'timeout' => 5,
            ],
            'stripe' => [
                'name' => 'Stripe',
                'endpoint' => 'https://api.stripe.com',
                'timeout' => 5,
            ],
        ];

        foreach ($apis as $key => $api) {
            $startTime = microtime(true);
            
            try {
                $response = Http::timeout($api['timeout'])->get($api['endpoint']);
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                
                $this->apiStatus[$key] = [
                    'name' => $api['name'],
                    'status' => $response->successful() ? 'online' : 'error',
                    'response_time' => $responseTime,
                    'status_code' => $response->status(),
                ];
            } catch (\Exception $e) {
                $this->apiStatus[$key] = [
                    'name' => $api['name'],
                    'status' => 'offline',
                    'response_time' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Check circuit breakers
        $this->apiStatus['circuit_breakers'] = $this->getCircuitBreakerStatus();
    }

    protected function loadPerformanceMetrics(): void
    {
        $timeRange = now()->subHours(24);

        // Cache schema checks for performance
        $hasApiCallLogs = Cache::remember('schema_has_api_call_logs', 3600, function() {
            return Schema::hasTable('api_call_logs');
        });
        
        $hasSlowQueryLogs = Cache::remember('schema_has_slow_query_logs', 3600, function() {
            return Schema::hasTable('slow_query_logs');
        });

        // Average response times - use actual tables if they exist
        $avgResponseTimes = collect();
        if ($hasApiCallLogs) {
            $avgResponseTimes = Cache::remember('monitoring_avg_response_times', 60, function () use ($timeRange) {
                return DB::table('api_call_logs')
                    ->where('created_at', '>=', $timeRange)
                    ->groupBy('service')
                    ->selectRaw('service, AVG(duration_ms) as avg_time, COUNT(*) as total_calls')
                    ->get();
            });
        }

        // Slow queries - check if table exists
        $slowQueries = 0;
        if ($hasSlowQueryLogs) {
            $slowQueries = Cache::remember('monitoring_slow_queries', 60, function() use ($timeRange) {
                return DB::table('slow_query_logs')
                    ->where('created_at', '>=', $timeRange)
                    ->where('duration_ms', '>', 1000)
                    ->count();
            });
        }

        // Cache hit rate
        $cacheStats = Cache::get('cache_statistics', [
            'hits' => 0,
            'misses' => 0,
        ]);
        $cacheHitRate = $cacheStats['hits'] + $cacheStats['misses'] > 0
            ? round($cacheStats['hits'] / ($cacheStats['hits'] + $cacheStats['misses']) * 100, 2)
            : 0;

        $this->performanceMetrics = [
            'response_times' => $avgResponseTimes,
            'slow_queries' => $slowQueries,
            'cache_hit_rate' => $cacheHitRate,
        ];
    }

    protected function loadRealtimeStats(): void
    {
        $now = now();

        // Active calls - bypass tenant scope for admin monitoring
        $activeCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('status', 'in_progress')
            ->where('created_at', '>=', $now->subHours(1))
            ->count();

        // Today's appointments - bypass tenant scope
        $todayAppointments = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereDate('starts_at', today())
            ->count();

        // Completed appointments today - bypass tenant scope
        $completedToday = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereDate('starts_at', today())
            ->where('status', 'completed')
            ->count();

        // Active companies
        $activeCompanies = Company::where('subscription_status', 'active')
            ->orWhere('subscription_status', 'trial')
            ->count();

        // Active phone numbers - bypass tenant scope
        $activePhones = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('is_active', true)
            ->whereHas('retellAgent', function ($query) {
                $query->withoutGlobalScope(\App\Scopes\TenantScope::class);
            })
            ->count();

        // Recent webhook events
        $recentWebhooks = DB::table('webhook_events')
            ->where('created_at', '>=', $now->subMinutes(5))
            ->count();

        $this->realtimeStats = [
            'active_calls' => $activeCalls,
            'today_appointments' => $todayAppointments,
            'completed_today' => $completedToday,
            'active_companies' => $activeCompanies,
            'active_phones' => $activePhones,
            'recent_webhooks' => $recentWebhooks,
        ];
    }

    protected function loadErrorLogs(): void
    {
        // Recent errors from logs - check if table exists
        $errorLogs = [];
        
        if (Schema::hasTable('error_logs')) {
            $logs = Cache::remember('monitoring_error_logs', 60, function () {
                return DB::table('error_logs')
                    ->where('created_at', '>=', now()->subHours(24))
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($log) {
                        return [
                            'level' => $log->level,
                            'message' => substr($log->message, 0, 100) . '...',
                            'context' => $log->context,
                            'created_at' => $log->created_at,
                        ];
                    });
            });
            $errorLogs = $logs->toArray();
        }

        // Add failed jobs if table exists
        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($failedJobs as $job) {
                $errorLogs[] = [
                    'level' => 'error',
                    'message' => 'Failed Job: ' . $job->queue,
                    'context' => json_decode($job->exception, true),
                    'created_at' => $job->failed_at,
                ];
            }
        }
        
        $this->errorLogs = $errorLogs;
    }

    protected function loadQueueStatus(): void
    {
        // Horizon status - use Laravel's Process facade for security
        try {
            $horizonStatus = \Illuminate\Support\Facades\Process::run('php artisan horizon:status')->output();
            $isHorizonRunning = str_contains($horizonStatus, 'running');
        } catch (\Exception $e) {
            $horizonStatus = 'Unable to check status';
            $isHorizonRunning = false;
        }

        // Queue sizes
        $queues = ['default', 'webhooks', 'calls', 'notifications'];
        $queueSizes = [];

        foreach ($queues as $queue) {
            $size = Redis::llen("queues:$queue");
            $queueSizes[$queue] = $size;
        }

        // Failed jobs
        $failedJobsCount = DB::table('failed_jobs')->count();

        // Processing rate (jobs per minute) - check if table exists
        $processingRate = 0;
        if (Schema::hasTable('job_batches')) {
            $processingRate = Cache::remember('queue_processing_rate', 60, function () {
                return DB::table('job_batches')
                    ->where('finished_at', '>=', now()->subMinutes(5))
                    ->count() / 5; // per minute
            });
        }

        $this->queueStatus = [
            'horizon' => [
                'status' => $isHorizonRunning ? 'running' : 'stopped',
                'raw_output' => $horizonStatus,
            ],
            'queues' => $queueSizes,
            'failed_jobs' => $failedJobsCount,
            'processing_rate' => round($processingRate, 2),
        ];
    }

    protected function getMemoryUsage(): float
    {
        // Use PHP's built-in memory functions instead of shell_exec
        if (PHP_OS_FAMILY === 'Linux') {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
            
            if (isset($total[1]) && isset($available[1])) {
                $used = $total[1] - $available[1];
                return round(($used / $total[1]) * 100, 2);
            }
        }
        
        // Fallback to PHP memory usage
        return round((memory_get_usage(true) / memory_get_peak_usage(true)) * 100, 2);
    }

    protected function getCircuitBreakerStatus(): array
    {
        $services = ['calcom', 'retell', 'stripe'];
        $status = [];

        foreach ($services as $service) {
            // Simple check based on config
            $hasKey = match($service) {
                'calcom' => !empty(config('services.calcom.api_key')),
                'retell' => !empty(config('services.retell.api_key')),
                'stripe' => !empty(config('services.stripe.secret')),
                default => false,
            };

            $status[$service] = $hasKey ? 'closed' : 'open';
        }

        return $status;
    }

    public function refresh(): void
    {
        $this->loadAllMetrics();
        $this->dispatch('metricsUpdated');
    }

    #[On('toggle-auto-refresh')]
    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function exportMetrics(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = [
            'timestamp' => now()->toIso8601String(),
            'system_metrics' => $this->systemMetrics,
            'api_status' => $this->apiStatus,
            'performance_metrics' => $this->performanceMetrics,
            'realtime_stats' => $this->realtimeStats,
            'queue_status' => $this->queueStatus,
        ];

        $filename = 'system-metrics-' . now()->format('Y-m-d-H-i-s') . '.json';
        
        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename);
    }

    protected function getActions(): array
    {
        return static::applyFormActionTooltips([
            Actions\Action::make('refresh')
                ->label('Aktualisieren')
                ->tooltip(static::tooltip('refresh_data'))
                ->icon('heroicon-o-arrow-path')
                ->action('refresh'),
                
            Actions\Action::make('export')
                ->label('Export')
                ->tooltip(static::tooltip('export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportMetrics'),
                
            Actions\Action::make('preflight')
                ->label('Preflight Check')
                ->tooltip(static::tooltip('preflight_check'))
                ->icon('heroicon-o-shield-check')
                ->url('/admin/preflight-check'),
        ]);
    }

    
    protected function handleMetricError(string $metric, \Exception $e): void
    {
        \Log::error("Monitoring dashboard metric error: {$metric}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Send notification to admin if critical metric fails
        if (in_array($metric, ['database', 'redis', 'queue'])) {
            \Filament\Notifications\Notification::make()
                ->title('Monitoring Error')
                ->body("Failed to load {$metric} metrics")
                ->danger()
                ->send();
        }
    }
    
    public function getErrorDisplay(string $message): string
    {
        return match($message) {
            'Redis connection failed' => 'Redis ist offline. Bitte prüfen Sie die Verbindung.',
            'Database connection failed' => 'Datenbankverbindung fehlgeschlagen.',
            'API timeout' => 'API-Timeout. Service antwortet nicht.',
            'Permission denied' => 'Keine Berechtigung für diese Metrik.',
            default => 'Fehler beim Laden der Daten.'
        };
    }
}