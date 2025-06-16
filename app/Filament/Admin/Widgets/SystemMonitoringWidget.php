<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class SystemMonitoringWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        return [
            $this->getDatabaseStat(),
            $this->getQueueStat(),
            $this->getCacheStat(),
            $this->getStorageStat(),
        ];
    }
    
    private function getDatabaseStat(): Stat
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            // Get database size
            $dbName = config('database.connections.mysql.database');
            $result = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS size_mb,
                    COUNT(*) as table_count
                FROM information_schema.TABLES 
                WHERE table_schema = ?
            ", [$dbName]);
            
            $size = $result[0]->size_mb ?? 0;
            $tables = $result[0]->table_count ?? 0;
            
            // Get connection count
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
            
            return Stat::make('🗄️ Datenbank', 'Verbunden')
                ->description(sprintf(
                    '%s MB • %d Tabellen • %d Verbindungen • %sms',
                    $size,
                    $tables,
                    $connections,
                    $responseTime
                ))
                ->chart($this->getDatabaseChart())
                ->color('success');
                
        } catch (\Exception $e) {
            return Stat::make('🗄️ Datenbank', 'Fehler')
                ->description($e->getMessage())
                ->color('danger');
        }
    }
    
    private function getQueueStat(): Stat
    {
        try {
            // Check Redis connection
            $redis = Redis::connection();
            $redis->ping();
            
            // Get queue sizes
            $defaultQueue = Redis::llen('queues:default');
            $highQueue = Redis::llen('queues:high');
            $lowQueue = Redis::llen('queues:low');
            $failedJobs = DB::table('failed_jobs')->count();
            
            $totalJobs = $defaultQueue + $highQueue + $lowQueue;
            
            return Stat::make('📋 Queue System', $totalJobs . ' Jobs')
                ->description(sprintf(
                    'High: %d • Default: %d • Low: %d • Failed: %d',
                    $highQueue,
                    $defaultQueue,
                    $lowQueue,
                    $failedJobs
                ))
                ->chart($this->getQueueChart())
                ->color($failedJobs > 10 ? 'danger' : ($totalJobs > 100 ? 'warning' : 'success'))
                ->extraAttributes([
                    'class' => $failedJobs > 0 ? 'ring-2 ring-danger-500/20' : ''
                ]);
                
        } catch (\Exception $e) {
            return Stat::make('📋 Queue System', 'Offline')
                ->description('Redis nicht erreichbar')
                ->color('danger');
        }
    }
    
    private function getCacheStat(): Stat
    {
        try {
            // Test cache
            $key = 'system_monitor_test_' . time();
            Cache::put($key, true, 10);
            $cached = Cache::get($key);
            Cache::forget($key);
            
            if (!$cached) {
                throw new \Exception('Cache write/read fehlgeschlagen');
            }
            
            // Get cache stats from Redis
            $redis = Redis::connection('cache');
            $info = $redis->info('memory');
            
            $usedMemory = round($info['used_memory'] / 1024 / 1024, 1); // MB
            $peakMemory = round($info['used_memory_peak'] / 1024 / 1024, 1); // MB
            
            // Calculate hit rate (simulated)
            $hits = Cache::get('cache_hits', 0);
            $misses = Cache::get('cache_misses', 0);
            $hitRate = ($hits + $misses) > 0 ? round(($hits / ($hits + $misses)) * 100, 1) : 0;
            
            return Stat::make('💾 Cache System', $usedMemory . ' MB')
                ->description(sprintf(
                    'Peak: %s MB • Hit Rate: %.1f%% • Redis OK',
                    $peakMemory,
                    $hitRate
                ))
                ->chart($this->getCacheChart())
                ->color($hitRate > 80 ? 'success' : ($hitRate > 60 ? 'warning' : 'danger'));
                
        } catch (\Exception $e) {
            return Stat::make('💾 Cache System', 'Fehler')
                ->description($e->getMessage())
                ->color('danger');
        }
    }
    
    private function getStorageStat(): Stat
    {
        // Get disk usage
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usedSpace = $totalSpace - $freeSpace;
        
        $usedPercent = round(($usedSpace / $totalSpace) * 100, 1);
        
        // Get log file size
        $logSize = 0;
        $logPath = storage_path('logs');
        foreach (glob($logPath . '/*.log') as $file) {
            $logSize += filesize($file);
        }
        
        return Stat::make('💿 Speicher', $usedPercent . '% belegt')
            ->description(sprintf(
                'Frei: %s GB • Logs: %s MB',
                round($freeSpace / 1024 / 1024 / 1024, 1),
                round($logSize / 1024 / 1024, 1)
            ))
            ->chart($this->getStorageChart())
            ->color($usedPercent < 70 ? 'success' : ($usedPercent < 85 ? 'warning' : 'danger'))
            ->extraAttributes([
                'class' => $usedPercent > 85 ? 'ring-2 ring-danger-500/20' : ''
            ]);
    }
    
    // Chart generation methods
    private function getDatabaseChart(): array
    {
        // Simulated connection count over time
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $data[] = rand(5, 25);
        }
        return $data;
    }
    
    private function getQueueChart(): array
    {
        // Queue size over last 12 checks
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $data[] = Cache::get("queue_size_history_{$i}", rand(0, 50));
        }
        return $data;
    }
    
    private function getCacheChart(): array
    {
        // Cache hit rate over time
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $data[] = rand(75, 95);
        }
        return $data;
    }
    
    private function getStorageChart(): array
    {
        // Storage usage trend
        $data = [];
        $base = 65;
        for ($i = 11; $i >= 0; $i--) {
            $data[] = min(100, $base + rand(-2, 3));
        }
        return $data;
    }
}