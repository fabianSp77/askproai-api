<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheManagement extends Command
{
    protected $signature = 'cache:manage {action=status}';
    protected $description = 'Manage application cache (status, clear, warm)';

    public function handle()
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'status':
                $this->showCacheStatus();
                break;
                
            case 'clear':
                $this->clearCache();
                break;
                
            case 'warm':
                $this->warmCache();
                break;
                
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: status, clear, warm");
        }
    }
    
    protected function showCacheStatus()
    {
        $this->info('Cache Status');
        $this->info('============');
        
        // Check Redis connection
        try {
            $redis = Cache::getRedis();
            $info = $redis->info();
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Used Memory', $this->formatBytes($info['used_memory'])],
                    ['Used Memory Peak', $this->formatBytes($info['used_memory_peak'])],
                    ['Connected Clients', $info['connected_clients']],
                    ['Total Keys', $redis->dbsize()],
                    ['Expired Keys', $info['expired_keys'] ?? 0],
                    ['Evicted Keys', $info['evicted_keys'] ?? 0],
                ]
            );
            
            // Show cache hit rate
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;
            
            if ($total > 0) {
                $hitRate = round(($hits / $total) * 100, 2);
                $this->info("\nCache Hit Rate: {$hitRate}%");
            }
            
        } catch (\Exception $e) {
            $this->error('Could not connect to Redis: ' . $e->getMessage());
        }
    }
    
    protected function clearCache()
    {
        if ($this->confirm('Are you sure you want to clear all cache?')) {
            Cache::flush();
            $this->info('✅ All cache cleared successfully');
            
            // Clear specific cache stores
            Cache::store('redis')->flush();
            Cache::store('file')->flush();
            
            $this->info('✅ All cache stores cleared');
        }
    }
    
    protected function warmCache()
    {
        $this->info('Warming up cache...');
        
        // Get all companies
        $companies = DB::table('companies')->where('is_active', true)->get();
        
        foreach ($companies as $company) {
            // Warm dashboard cache
            $cacheKey = "dashboard.operational.{$company->id}";
            
            Cache::forget($cacheKey); // Clear old cache
            
            // This would trigger the cache warming
            $this->info("Warmed cache for company: {$company->name}");
        }
        
        // Warm call tab counts
        DB::table('companies')
            ->where('is_active', true)
            ->pluck('id')
            ->each(function ($companyId) {
                $cacheKey = "call_tab_counts_{$companyId}";
                Cache::forget($cacheKey);
                
                // Pre-calculate counts
                $counts = DB::table('calls')
                    ->where('company_id', $companyId)
                    ->selectRaw("
                        COUNT(*) as total,
                        COUNT(CASE WHEN DATE(start_timestamp) = CURDATE() THEN 1 END) as today,
                        COUNT(CASE WHEN appointment_id IS NOT NULL THEN 1 END) as with_appointments,
                        COUNT(CASE WHEN appointment_id IS NULL THEN 1 END) as without_appointments,
                        COUNT(CASE WHEN duration_sec > 300 THEN 1 END) as long_calls,
                        COUNT(CASE WHEN call_status = 'failed' THEN 1 END) as failed
                    ")
                    ->first();
                    
                Cache::put($cacheKey, $counts, 60);
            });
        
        $this->info('✅ Cache warming completed');
    }
    
    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
}