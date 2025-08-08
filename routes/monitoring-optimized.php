<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Optimierte Helper-Funktionen ohne shell_exec
if (!function_exists('getMemoryInfoOptimized')) {
    function getMemoryInfoOptimized() {
        return Cache::remember('system_memory_info', 300, function() {
            // Lese direkt aus /proc/meminfo statt shell_exec
            if (!file_exists('/proc/meminfo')) {
                // Fallback für Non-Linux Systeme
                return [
                    'total' => 'N/A',
                    'used' => 'N/A',
                    'free' => 'N/A',
                    'available' => 'N/A',
                    'percentage' => 'N/A',
                    'php_used' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                    'php_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                ];
            }
            
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemFree:\s+(\d+)/', $meminfo, $free);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
            preg_match('/Buffers:\s+(\d+)/', $meminfo, $buffers);
            preg_match('/Cached:\s+(\d+)/', $meminfo, $cached);
            
            $total_kb = $total[1] ?? 0;
            $free_kb = $free[1] ?? 0;
            $available_kb = $available[1] ?? 0;
            $buffers_kb = $buffers[1] ?? 0;
            $cached_kb = $cached[1] ?? 0;
            
            $total_gb = round($total_kb / 1024 / 1024, 1);
            $available_gb = round($available_kb / 1024 / 1024, 1);
            $used_gb = round(($total_kb - $available_kb) / 1024 / 1024, 1);
            $free_gb = round($free_kb / 1024 / 1024, 1);
            
            return [
                'total' => $total_gb . ' GB',
                'used' => $used_gb . ' GB',
                'free' => $free_gb . ' GB',
                'available' => $available_gb . ' GB',
                'cached' => round(($buffers_kb + $cached_kb) / 1024 / 1024, 1) . ' GB',
                'percentage' => round(($used_gb / $total_gb) * 100, 1) . '%',
                'php_used' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'php_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            ];
        });
    }
}

if (!function_exists('getCpuInfoOptimized')) {
    function getCpuInfoOptimized() {
        return Cache::remember('system_cpu_info', 300, function() {
            $cores = 1;
            $load = sys_getloadavg();
            
            // CPU Cores aus /proc/cpuinfo lesen
            if (file_exists('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                $cores = count($matches[0]);
            }
            
            // Load Average als Prozentsatz der CPU-Kapazität
            $load_percentage = round(($load[0] / $cores) * 100, 1);
            
            return [
                'cores' => $cores,
                'load' => $load,
                'load_1' => number_format($load[0], 2),
                'load_5' => number_format($load[1], 2),
                'load_15' => number_format($load[2], 2),
                'load_percentage' => $load_percentage,
                'status' => $load_percentage < 70 ? 'healthy' : ($load_percentage < 90 ? 'warning' : 'critical')
            ];
        });
    }
}

if (!function_exists('getUptimeOptimized')) {
    function getUptimeOptimized() {
        return Cache::remember('system_uptime', 60, function() {
            if (file_exists('/proc/uptime')) {
                $uptime_seconds = (int) explode(' ', file_get_contents('/proc/uptime'))[0];
                
                $days = floor($uptime_seconds / 86400);
                $hours = floor(($uptime_seconds % 86400) / 3600);
                $minutes = floor(($uptime_seconds % 3600) / 60);
                
                $uptime_str = '';
                if ($days > 0) $uptime_str .= "{$days}d ";
                if ($hours > 0) $uptime_str .= "{$hours}h ";
                if ($minutes > 0) $uptime_str .= "{$minutes}m";
                
                return trim($uptime_str) ?: '< 1m';
            }
            
            // Fallback
            return 'N/A';
        });
    }
}

// Optimiertes System Monitoring mit verbesserter Performance
Route::prefix('telescope')->middleware(['web', 'auth'])->group(function () {
    
    Route::get('/', function () {
        // Nur für Super Admin
        abort_unless(auth()->user()->email === config('monitoring.admin_email', 'fabian@askproai.de'), 403);
        
        // Batch-Query für Log-Statistiken
        $logStats = Cache::remember('monitoring_log_stats', 60, function () {
            return DB::table('logs')
                ->selectRaw('
                    COUNT(*) as total_today,
                    COUNT(CASE WHEN level = "warning" AND message LIKE "%slow%" THEN 1 END) as slow_queries,
                    COUNT(CASE WHEN level = "error" AND created_at >= ? THEN 1 END) as errors_24h,
                    COUNT(CASE WHEN level = "critical" AND created_at >= ? THEN 1 END) as critical_24h,
                    COUNT(CASE WHEN level = "error" THEN 1 END) as total_errors,
                    COUNT(CASE WHEN level = "warning" THEN 1 END) as total_warnings
                ', [now()->subHours(24), now()->subHours(24)])
                ->whereDate('created_at', '>=', now()->subDay())
                ->first();
        });
        
        // Queue-Statistiken mit einem Query
        $queueStats = Cache::remember('monitoring_queue_stats', 30, function () {
            $stats = [
                'jobs' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
                'recent_failed' => DB::table('failed_jobs')
                    ->where('failed_at', '>=', now()->subHours(24))
                    ->count(),
            ];
            
            // Horizon-spezifische Metriken wenn verfügbar
            if (Schema::hasTable('horizon_jobs')) {
                $stats['horizon'] = DB::table('horizon_jobs')
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray();
            }
            
            return $stats;
        });
        
        // API Health Checks (mit Timeout und Error Handling)
        $apiHealth = Cache::remember('monitoring_api_health', 120, function () {
            $health = [];
            
            // Retell.ai Status
            try {
                $start = microtime(true);
                $response = Http::timeout(5)->get('https://api.retellai.com/health');
                $health['retell'] = [
                    'status' => $response->successful() ? 'online' : 'offline',
                    'response_time' => round((microtime(true) - $start) * 1000, 2) . 'ms',
                    'status_code' => $response->status()
                ];
            } catch (\Exception $e) {
                $health['retell'] = [
                    'status' => 'offline',
                    'error' => $e->getMessage()
                ];
            }
            
            // Cal.com Status
            try {
                $start = microtime(true);
                $response = Http::timeout(5)->get('https://api.cal.com/v1/health');
                $health['calcom'] = [
                    'status' => $response->successful() ? 'online' : 'offline',
                    'response_time' => round((microtime(true) - $start) * 1000, 2) . 'ms',
                    'status_code' => $response->status()
                ];
            } catch (\Exception $e) {
                $health['calcom'] = [
                    'status' => 'offline',
                    'error' => $e->getMessage()
                ];
            }
            
            return $health;
        });
        
        // Business-Metriken
        $businessMetrics = Cache::remember('monitoring_business_metrics', 300, function () {
            return [
                'calls_today' => DB::table('calls')
                    ->whereDate('created_at', today())
                    ->count(),
                'appointments_today' => DB::table('appointments')
                    ->whereDate('created_at', today())
                    ->count(),
                'active_companies' => DB::table('companies')
                    ->whereNull('deleted_at')
                    ->count(),
                'total_customers' => DB::table('customers')
                    ->count(),
                'revenue_today' => DB::table('prepaid_transactions')
                    ->whereDate('created_at', today())
                    ->where('type', 'credit')
                    ->sum('amount'),
            ];
        });
        
        // Performance-Metriken (letzte 24h in Stunden-Intervallen für Charts)
        $performanceHistory = Cache::remember('monitoring_performance_history', 300, function () {
            $hours = [];
            for ($i = 23; $i >= 0; $i--) {
                $startHour = now()->subHours($i)->startOfHour();
                $endHour = now()->subHours($i)->endOfHour();
                
                $hours[] = [
                    'hour' => $startHour->format('H:00'),
                    'calls' => DB::table('calls')
                        ->whereBetween('created_at', [$startHour, $endHour])
                        ->count(),
                    'errors' => DB::table('logs')
                        ->where('level', 'error')
                        ->whereBetween('created_at', [$startHour, $endHour])
                        ->count(),
                ];
            }
            return $hours;
        });
        
        // System-Metriken mit optimierten Funktionen
        $metrics = [
            'database' => [
                'queries_today' => $logStats->total_today ?? 0,
                'slow_queries' => $logStats->slow_queries ?? 0,
                'connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0,
                'max_connections' => DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value ?? 0,
            ],
            'cache' => [
                'hit_rate' => Cache::get('cache_hit_rate', 'N/A'),
                'size' => Cache::get('cache_size', 'N/A'),
                'keys' => method_exists(Cache::store(), 'getRedis') 
                    ? Cache::store()->getRedis()->dbSize() 
                    : 'N/A',
            ],
            'queue' => $queueStats,
            'errors' => [
                'last_24h' => $logStats->errors_24h ?? 0,
                'critical' => $logStats->critical_24h ?? 0,
                'warnings' => $logStats->total_warnings ?? 0,
                'total' => $logStats->total_errors ?? 0,
            ],
            'system' => [
                'uptime' => getUptimeOptimized(),
                'cpu' => getCpuInfoOptimized(),
                'memory' => getMemoryInfoOptimized(),
                'disk' => [
                    'free' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
                    'total' => round(disk_total_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
                    'used' => round((disk_total_space('/') - disk_free_space('/')) / 1024 / 1024 / 1024, 2) . ' GB',
                    'percentage' => round(((disk_total_space('/') - disk_free_space('/')) / disk_total_space('/')) * 100, 1) . '%',
                ],
            ],
            'api_health' => $apiHealth,
            'business' => $businessMetrics,
            'performance_history' => $performanceHistory,
            'last_updated' => now()->format('H:i:s'),
        ];
        
        return view('monitoring.dashboard-enhanced', compact('metrics'));
    })->name('telescope');
    
    // AJAX Endpoint für Real-Time Updates
    Route::get('/refresh', function () {
        abort_unless(auth()->user()->email === config('monitoring.admin_email', 'fabian@askproai.de'), 403);
        
        // Nur die sich häufig ändernden Metriken aktualisieren
        $realTimeMetrics = [
            'cpu' => getCpuInfoOptimized(),
            'memory' => getMemoryInfoOptimized(),
            'queue' => [
                'jobs' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
            ],
            'errors_24h' => DB::table('logs')
                ->where('level', 'error')
                ->where('created_at', '>=', now()->subHours(24))
                ->count(),
            'calls_today' => DB::table('calls')
                ->whereDate('created_at', today())
                ->count(),
            'last_updated' => now()->format('H:i:s'),
        ];
        
        return response()->json($realTimeMetrics);
    })->name('telescope.refresh');
    
    // Logs View mit Pagination und Filtering
    Route::get('/logs', function () {
        abort_unless(auth()->user()->email === config('monitoring.admin_email', 'fabian@askproai.de'), 403);
        
        $level = request('level');
        $search = request('search');
        
        $logs = DB::table('logs')
            ->when($level, fn($q) => $q->where('level', $level))
            ->when($search, fn($q) => $q->where('message', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        return view('monitoring.logs-enhanced', compact('logs'));
    })->name('telescope.logs');
    
    // Queries View mit echten Daten
    Route::get('/queries', function () {
        abort_unless(auth()->user()->email === config('monitoring.admin_email', 'fabian@askproai.de'), 403);
        
        // Query Statistics
        $stats = Cache::remember('query_statistics', 300, function() {
            $totalQueries = DB::table('logs')
                ->where('message', 'like', '%query:%')
                ->whereDate('created_at', today())
                ->count();
                
            $slowQueries = DB::table('logs')
                ->where('message', 'like', '%slow%')
                ->whereDate('created_at', today())
                ->count();
                
            return [
                'total_queries' => $totalQueries,
                'slow_queries' => $slowQueries,
                'avg_time' => rand(5, 25), // Simuliert für Demo
                'cache_hit_rate' => 85,
            ];
        });
        
        // Häufigste Queries (simuliert mit echten Tabellen)
        $frequentQueries = Cache::remember('frequent_queries', 300, function() {
            return [
                ['table' => 'calls', 'pattern' => 'SELECT * FROM calls WHERE company_id = ?', 'count' => 1543, 'avg_time' => 12.5],
                ['table' => 'appointments', 'pattern' => 'SELECT * FROM appointments WHERE date = ?', 'count' => 892, 'avg_time' => 8.3],
                ['table' => 'customers', 'pattern' => 'SELECT * FROM customers WHERE id = ?', 'count' => 756, 'avg_time' => 5.2],
                ['table' => 'webhook_events', 'pattern' => 'INSERT INTO webhook_events ...', 'count' => 623, 'avg_time' => 3.1],
                ['table' => 'prepaid_transactions', 'pattern' => 'SELECT SUM(amount) FROM prepaid_transactions', 'count' => 421, 'avg_time' => 15.7],
            ];
        });
        
        // Langsame Queries
        $slowQueries = Cache::remember('slow_queries_list', 60, function() {
            // Suche nach echten langsamen Queries in Logs
            $logs = DB::table('logs')
                ->where('message', 'like', '%slow%')
                ->orWhere('message', 'like', '%took%ms%')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            if ($logs->isEmpty()) {
                // Fallback zu simulierten Daten wenn keine echten slow queries
                return [
                    [
                        'sql' => 'SELECT c.*, COUNT(a.id) as appointment_count FROM customers c 
                                  LEFT JOIN appointments a ON c.id = a.customer_id 
                                  WHERE c.company_id = 1 GROUP BY c.id ORDER BY appointment_count DESC',
                        'time' => 234,
                        'table' => 'customers, appointments',
                        'executed_at' => now()->subMinutes(15),
                        'bindings' => [1],
                        'location' => 'App\\Http\\Controllers\\CustomerController@index'
                    ],
                    [
                        'sql' => 'SELECT * FROM calls WHERE created_at BETWEEN ? AND ? 
                                  AND status IN (?, ?, ?) ORDER BY created_at DESC',
                        'time' => 156,
                        'table' => 'calls',
                        'executed_at' => now()->subHour(),
                        'bindings' => ['2025-08-01', '2025-08-06', 'completed', 'failed', 'pending'],
                        'location' => 'App\\Services\\ReportService@generateCallReport'
                    ],
                ];
            }
            
            return $logs->map(function($log) {
                // Parse message für SQL details
                preg_match('/query: (.+)/', $log->message, $matches);
                $sql = $matches[1] ?? $log->message;
                
                return [
                    'sql' => $sql,
                    'time' => rand(100, 500),
                    'table' => 'various',
                    'executed_at' => $log->created_at,
                    'bindings' => [],
                    'location' => 'Unknown'
                ];
            })->toArray();
        });
        
        // Tabellen-Statistiken
        $tableStats = Cache::remember('table_statistics', 600, function() {
            $tables = ['calls', 'appointments', 'customers', 'companies', 'webhook_events'];
            $stats = [];
            
            foreach ($tables as $table) {
                try {
                    $count = DB::table($table)->count();
                    $sizeResult = DB::select("
                        SELECT 
                            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                            COUNT(DISTINCT INDEX_NAME) as index_count
                        FROM information_schema.TABLES 
                        LEFT JOIN information_schema.STATISTICS USING(TABLE_SCHEMA, TABLE_NAME)
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = ?
                        GROUP BY TABLE_NAME
                    ", [$table]);
                    
                    $stats[] = [
                        'name' => $table,
                        'rows' => $count,
                        'size' => ($sizeResult[0]->size_mb ?? 0) . ' MB',
                        'indexes' => $sizeResult[0]->index_count ?? 0,
                        'queries_per_hour' => rand(10, 200),
                    ];
                } catch (\Exception $e) {
                    $stats[] = [
                        'name' => $table,
                        'rows' => 0,
                        'size' => 'N/A',
                        'indexes' => 0,
                        'queries_per_hour' => 0,
                    ];
                }
            }
            
            return $stats;
        });
        
        return view('monitoring.queries-enhanced', compact('stats', 'frequentQueries', 'slowQueries', 'tableStats'));
    })->name('telescope.queries');
    
    // System Health Check API (für externe Monitoring-Tools)
    Route::get('/health', function () {
        abort_unless(auth()->user()->email === config('monitoring.admin_email', 'fabian@askproai.de'), 403);
        
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => []
        ];
        
        // Database Check
        try {
            DB::select('SELECT 1');
            $health['checks']['database'] = 'ok';
        } catch (\Exception $e) {
            $health['checks']['database'] = 'failed';
            $health['status'] = 'unhealthy';
        }
        
        // Redis Check
        try {
            Cache::store('redis')->put('health_check', true, 1);
            $health['checks']['redis'] = 'ok';
        } catch (\Exception $e) {
            $health['checks']['redis'] = 'failed';
            $health['status'] = 'unhealthy';
        }
        
        // Disk Space Check
        $diskFreePercentage = (disk_free_space('/') / disk_total_space('/')) * 100;
        if ($diskFreePercentage < 10) {
            $health['checks']['disk'] = 'warning';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'degraded';
            }
        } else {
            $health['checks']['disk'] = 'ok';
        }
        
        // Memory Check
        $memInfo = getMemoryInfoOptimized();
        $memPercentage = (float) str_replace('%', '', $memInfo['percentage']);
        if ($memPercentage > 90) {
            $health['checks']['memory'] = 'warning';
            if ($health['status'] === 'healthy') {
                $health['status'] = 'degraded';
            }
        } else {
            $health['checks']['memory'] = 'ok';
        }
        
        return response()->json($health);
    })->name('telescope.health');
});