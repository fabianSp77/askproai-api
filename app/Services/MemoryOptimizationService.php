<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class MemoryOptimizationService
{
    /**
     * Optimize database queries to reduce memory usage
     */
    public function optimizeDatabaseQueries(): void
    {
        // Enable query log monitoring
        DB::disableQueryLog(); // Disable to save memory
        
        // Set connection options for memory efficiency
        try {
            DB::connection()->getPdo()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        } catch (\Exception $e) {
            Log::warning('Could not set unbuffered query mode: ' . $e->getMessage());
        }
        
        // Clear query cache periodically (only if we have privileges)
        try {
            DB::connection()->unprepared('FLUSH TABLES');
        } catch (\Exception $e) {
            // Silently fail if we don't have privileges
            Log::info('Could not flush tables (normal for restricted users)');
        }
    }
    
    /**
     * Optimize eager loading to prevent N+1 queries
     */
    public function optimizeEagerLoading(): void
    {
        // Set global constraints on eager loading
        \App\Models\Call::addGlobalScope('limited', function ($builder) {
            $builder->limit(config('memory-optimization.database.eager_load_limit', 100));
        });
        
        \App\Models\Appointment::addGlobalScope('recent', function ($builder) {
            $builder->where('created_at', '>', now()->subDays(30));
        });
    }
    
    /**
     * Clear unused cache to free memory
     */
    public function clearUnusedCache(): void
    {
        // Clear expired cache entries
        Cache::store('redis')->flush();
        
        // Clear view cache
        Artisan::call('view:clear');
        
        // Clear compiled classes
        Artisan::call('clear-compiled');
    }
    
    /**
     * Monitor memory usage and log warnings
     */
    public function monitorMemoryUsage(): array
    {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->getMemoryLimit();
        
        $percentage = ($usage / $limit) * 100;
        
        if ($percentage > 80) {
            Log::warning('High memory usage detected', [
                'current' => $this->formatBytes($usage),
                'peak' => $this->formatBytes($peak),
                'limit' => $this->formatBytes($limit),
                'percentage' => round($percentage, 2) . '%'
            ]);
            
            // Trigger garbage collection
            gc_collect_cycles();
        }
        
        return [
            'current' => $this->formatBytes($usage),
            'peak' => $this->formatBytes($peak),
            'limit' => $this->formatBytes($limit),
            'percentage' => round($percentage, 2) . '%'
        ];
    }
    
    /**
     * Get memory limit in bytes
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit == -1) {
            return PHP_INT_MAX;
        }
        
        preg_match('/^(\d+)(.)$/', $limit, $matches);
        
        $value = (int) $matches[1];
        $unit = strtolower($matches[2] ?? 'b');
        
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
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
    
    /**
     * Optimize widget loading for dashboard
     */
    public function optimizeWidgetLoading(): void
    {
        // Implement lazy loading for widgets
        app()->bind('filament.widgets.lazy', function () {
            return config('memory-optimization.widgets.lazy_load', true);
        });
        
        // Set widget cache
        config(['filament.widgets.cache_ttl' => config('memory-optimization.widgets.cache_ttl', 600)]);
    }
}