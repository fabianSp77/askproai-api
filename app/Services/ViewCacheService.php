<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Cache\RedisLock;
use Symfony\Component\Console\Output\NullOutput;

class ViewCacheService
{
    private const CACHE_KEY = 'view_cache_health';
    private const LOCK_KEY = 'view_cache_lock';
    private const LOCK_TTL = 30;
    private const REBUILD_LOCK_KEY = 'view_cache_rebuild_lock';
    private const REBUILD_LOCK_TTL = 60;
    
    /**
     * Check the health of the view cache system
     */
    public function checkHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Check view directory permissions
        $viewPath = storage_path('framework/views');
        if (!is_writable($viewPath)) {
            $health['status'] = 'error';
            $health['checks'][] = [
                'name' => 'view_directory_writable',
                'status' => false,
                'message' => 'View cache directory is not writable'
            ];
        } else {
            $health['checks'][] = [
                'name' => 'view_directory_writable',
                'status' => true,
                'message' => 'View cache directory is writable'
            ];
        }
        
        // Check Redis connectivity
        try {
            Redis::ping();
            $health['checks'][] = [
                'name' => 'redis_connection',
                'status' => true,
                'message' => 'Redis connection successful'
            ];
        } catch (\Exception $e) {
            $health['status'] = 'degraded';
            $health['checks'][] = [
                'name' => 'redis_connection',
                'status' => false,
                'message' => 'Redis connection failed: ' . $e->getMessage()
            ];
        }
        
        // Check for stale cache files
        $viewFiles = File::glob($viewPath . '/*.php');
        $staleCount = 0;
        $now = time();
        
        foreach ($viewFiles as $file) {
            if (file_exists($file)) {
                $mtime = @filemtime($file);
                if ($mtime !== false) {
                    $age = $now - $mtime;
                    if ($age > 86400) { // Older than 24 hours
                        $staleCount++;
                    }
                }
            }
        }
        
        if ($staleCount > 0) {
            $health['status'] = 'warning';
            $health['checks'][] = [
                'name' => 'stale_cache_files',
                'status' => false,
                'message' => "Found {$staleCount} stale cache files older than 24 hours"
            ];
        } else {
            $health['checks'][] = [
                'name' => 'stale_cache_files',
                'status' => true,
                'message' => 'No stale cache files found'
            ];
        }
        
        // Store health status in Redis
        Cache::put(self::CACHE_KEY, $health, now()->addMinutes(5));
        
        return $health;
    }
    
    /**
     * Safely clear and rebuild view cache
     */
    public function rebuild(): bool
    {
        // Use Redis lock to prevent concurrent rebuilds
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);
        
        if (!$lock->get()) {
            Log::warning('View cache rebuild already in progress');
            return false;
        }
        
        try {
            Log::info('Starting view cache rebuild');
            
            // Ensure proper permissions first
            $this->fixPermissions();
            
            // Clear all caches with timeout protection
            $this->safeArtisanCall('view:clear', 10);
            $this->safeArtisanCall('cache:clear', 10);
            $this->safeArtisanCall('config:clear', 10);
            $this->safeArtisanCall('route:clear', 10);
            
            // Clear OPcache
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            // Small delay to ensure filesystem sync
            usleep(100000); // 100ms
            
            // Rebuild caches with timeout protection
            $this->safeArtisanCall('config:cache', 15);
            $this->safeArtisanCall('route:cache', 15);
            $this->safeArtisanCall('view:cache', 20);
            
            // Warm up critical views
            $this->warmupViews();
            
            Log::info('View cache rebuild completed successfully');
            
            return true;
        } catch (\Exception $e) {
            Log::error('View cache rebuild failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Attempt recovery
            $this->attemptRecovery();
            
            return false;
        } finally {
            $lock->release();
        }
    }
    
    /**
     * Warm up critical views to prevent cold start issues
     */
    public function warmupViews(): void
    {
        $criticalViews = [
            'filament.admin.login',
            'filament.admin.pages.dashboard',
            'filament.admin.resources.list',
            'layouts.app',
            'components.layout',
        ];
        
        foreach ($criticalViews as $view) {
            try {
                if (view()->exists($view)) {
                    // Compile the view by rendering it
                    view($view)->render();
                    Log::debug("Warmed up view: {$view}");
                }
            } catch (\Exception $e) {
                Log::warning("Could not warm up view: {$view}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Auto-fix common view cache issues
     */
    public function autoFix(): bool
    {
        // Prevent concurrent auto-fixes
        $lock = Cache::lock('view_cache_autofix_lock', 10);
        
        if (!$lock->get()) {
            Log::info('Auto-fix already in progress by another process');
            return false;
        }
        
        Log::info('Attempting auto-fix of view cache issues');
        
        try {
            // Clear problematic cache files
            $viewPath = storage_path('framework/views');
            $files = File::glob($viewPath . '/*.php');
            
            foreach ($files as $file) {
                // Check if file is readable
                if (!is_readable($file)) {
                    File::delete($file);
                    Log::info("Deleted unreadable view cache file: {$file}");
                }
            }
            
            // Fix permissions
            $this->fixPermissions();
            
            // Clear and rebuild
            return $this->rebuild();
            
        } catch (\Exception $e) {
            Log::error('Auto-fix failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        } finally {
            $lock->release();
        }
    }
    
    /**
     * Fix file and directory permissions
     */
    private function fixPermissions(): void
    {
        $directories = [
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
            base_path('resources/css'),
            base_path('resources/js'),
            base_path('resources/views'),
        ];
        
        foreach ($directories as $dir) {
            try {
                if (!File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0775, true);
                }
                
                // Set proper permissions
                @chmod($dir, 0775);
                
                // Fix ownership if running as root
                if (function_exists('posix_getuid') && posix_getuid() === 0) {
                    @chown($dir, 'www-data');
                    @chgrp($dir, 'www-data');
                }
                
                // Fix permissions recursively for resource directories
                if (strpos($dir, 'resource') !== false) {
                    $this->fixPermissionsRecursive($dir);
                }
            } catch (\Exception $e) {
                Log::warning("Could not fix permissions for {$dir}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Fix permissions recursively for a directory
     */
    private function fixPermissionsRecursive(string $dir): void
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    @chmod($item->getPathname(), 0775);
                } else {
                    @chmod($item->getPathname(), 0664);
                }
                
                if (function_exists('posix_getuid') && posix_getuid() === 0) {
                    @chown($item->getPathname(), 'www-data');
                    @chgrp($item->getPathname(), 'www-data');
                }
            }
        } catch (\Exception $e) {
            Log::warning("Could not fix permissions recursively for {$dir}", [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Safely execute Artisan command with timeout
     */
    private function safeArtisanCall(string $command, int $timeout = 30): void
    {
        try {
            $startTime = time();
            
            // Set timeout for the command
            set_time_limit($timeout + 10);
            
            Artisan::call($command);
            
            $elapsed = time() - $startTime;
            if ($elapsed > $timeout) {
                Log::warning("Artisan command {$command} took longer than expected", [
                    'elapsed' => $elapsed,
                    'timeout' => $timeout
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to execute artisan command: {$command}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Attempt recovery after cache rebuild failure
     */
    private function attemptRecovery(): void
    {
        try {
            Log::info('Attempting cache recovery');
            
            // Clear view cache directory manually
            $viewPath = storage_path('framework/views');
            if (File::isDirectory($viewPath)) {
                $files = File::glob($viewPath . '/*.php');
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            
            // Ensure directories exist with proper permissions
            $this->fixPermissions();
            
            Log::info('Cache recovery completed');
        } catch (\Exception $e) {
            Log::error('Cache recovery failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if view cache is healthy
     */
    private function isHealthy(): bool
    {
        $viewPath = storage_path('framework/views');
        
        // Check if directory exists and is writable
        if (!is_dir($viewPath) || !is_writable($viewPath)) {
            return false;
        }
        
        // Check if we have compiled views
        $files = glob($viewPath . '/*.php');
        return count($files) > 0;
    }
    
    /**
     * Emergency fix when all else fails
     */
    private function emergencyFix(): void
    {
        Log::warning('Executing emergency view cache fix');
        
        // Force clear everything
        $viewPath = storage_path('framework/views');
        exec("rm -rf {$viewPath}/*.php 2>/dev/null");
        
        // Recreate directory with proper permissions
        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0775, true);
        }
        
        // Set ownership
        exec("chown -R www-data:www-data {$viewPath}");
        exec("chmod -R 775 {$viewPath}");
        
        // Clear OPcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}