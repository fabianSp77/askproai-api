<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

// Helper function für Memory Info
if (!function_exists('getMemoryInfo')) {
    function getMemoryInfo() {
        $free = shell_exec('free -b');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", preg_replace('/\s+/', ' ', $free_arr[1]));
        
        $total = round($mem[1] / 1024 / 1024 / 1024, 1); // GB
        $used = round($mem[2] / 1024 / 1024 / 1024, 1);  // GB
        $free = round($mem[3] / 1024 / 1024 / 1024, 1);  // GB
        $available = round($mem[6] / 1024 / 1024 / 1024, 1); // GB
        
        return [
            'total' => $total . ' GB',
            'used' => $used . ' GB',
            'free' => $free . ' GB',
            'available' => $available . ' GB',
            'percentage' => round(($used / $total) * 100, 1) . '%',
            'php_used' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'php_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        ];
    }
}

// System Monitoring Route für Super Admin
Route::prefix('telescope')->middleware(['web', 'auth'])->group(function () {
    
    Route::get('/', function () {
        // Nur für Super Admin
        abort_unless(auth()->user()->email === 'fabian@askproai.de', 403);
        
        // System-Metriken sammeln
        $metrics = [
            'database' => [
                'queries_today' => Cache::remember('queries_today', 60, function () {
                    return DB::table('logs')
                        ->whereDate('created_at', today())
                        ->count();
                }),
                'slow_queries' => Cache::remember('slow_queries', 60, function () {
                    return DB::table('logs')
                        ->where('level', 'warning')
                        ->where('message', 'like', '%slow%')
                        ->whereDate('created_at', today())
                        ->count();
                }),
            ],
            'cache' => [
                'hit_rate' => Cache::get('cache_hit_rate', 'N/A'),
                'size' => Cache::get('cache_size', 'N/A'),
            ],
            'queue' => [
                'jobs_processed' => Cache::remember('jobs_processed', 60, function () {
                    return DB::table('jobs')->count();
                }),
                'failed_jobs' => Cache::remember('failed_jobs', 60, function () {
                    return DB::table('failed_jobs')->count();
                }),
            ],
            'errors' => [
                'last_24h' => Cache::remember('errors_24h', 60, function () {
                    return DB::table('logs')
                        ->where('level', 'error')
                        ->where('created_at', '>=', now()->subHours(24))
                        ->count();
                }),
                'critical' => Cache::remember('critical_errors', 60, function () {
                    return DB::table('logs')
                        ->where('level', 'critical')
                        ->where('created_at', '>=', now()->subHours(24))
                        ->count();
                }),
            ],
            'system' => [
                'uptime' => exec('uptime -p'),
                'load' => sys_getloadavg(),
                'cpu_cores' => shell_exec('nproc'),
                'memory' => getMemoryInfo(),
                'disk' => [
                    'free' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
                    'total' => round(disk_total_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
                    'used' => round((disk_total_space('/') - disk_free_space('/')) / 1024 / 1024 / 1024, 2) . ' GB',
                    'percentage' => round(((disk_total_space('/') - disk_free_space('/')) / disk_total_space('/')) * 100, 1) . '%',
                ],
            ],
        ];
        
        return view('monitoring.dashboard', compact('metrics'));
    })->name('telescope');
    
    Route::get('/logs', function () {
        abort_unless(auth()->user()->email === 'fabian@askproai.de', 403);
        
        $logs = DB::table('logs')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
            
        return view('monitoring.logs', compact('logs'));
    })->name('telescope.logs');
    
    Route::get('/queries', function () {
        abort_unless(auth()->user()->email === 'fabian@askproai.de', 403);
        
        $queries = Cache::get('recent_queries', []);
        
        return view('monitoring.queries', compact('queries'));
    })->name('telescope.queries');
});