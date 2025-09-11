<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Services\ViewCacheService;

class ViewCacheHealthCheck extends Command
{
    protected $signature = 'view:health-check 
                            {--fix : Automatically fix any issues found}
                            {--detailed : Show detailed output}';
    
    protected $description = 'Check view cache health and optionally fix issues';
    
    private ViewCacheService $cacheService;
    private array $issues = [];
    private array $metrics = [];
    
    public function __construct(ViewCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }
    
    public function handle(): int
    {
        $this->info('=========================================');
        $this->info('Laravel View Cache Health Check');
        $this->info('=========================================');
        
        // Perform health checks
        $this->checkViewCacheDirectory();
        $this->checkPermissions();
        $this->checkDiskSpace();
        $this->checkCompiledViews();
        $this->checkOPcache();
        $this->checkFilamentCaches();
        $this->checkRecentErrors();
        
        // Display results
        $this->displayResults();
        
        // Fix issues if requested
        if ($this->option('fix') && count($this->issues) > 0) {
            $this->fixIssues();
        }
        
        // Store metrics in Redis for monitoring
        $this->storeMetrics();
        
        // Return appropriate exit code
        return count($this->issues) > 0 ? 1 : 0;
    }
    
    private function checkViewCacheDirectory(): void
    {
        $this->line('→ Checking view cache directory...');
        
        $viewPath = storage_path('framework/views');
        
        if (!is_dir($viewPath)) {
            $this->issues[] = [
                'type' => 'directory_missing',
                'message' => 'View cache directory does not exist',
                'path' => $viewPath
            ];
            return;
        }
        
        if (!is_writable($viewPath)) {
            $this->issues[] = [
                'type' => 'directory_not_writable',
                'message' => 'View cache directory is not writable',
                'path' => $viewPath
            ];
        }
        
        // Check file count
        $files = glob($viewPath . '/*.php');
        $fileCount = count($files);
        $this->metrics['view_cache_files'] = $fileCount;
        
        if ($fileCount === 0) {
            $this->warn('  ⚠ No compiled views found');
        } else {
            $this->info("  ✓ Found {$fileCount} compiled views");
        }
        
        // Check for orphaned files
        $orphaned = 0;
        foreach ($files as $file) {
            if (filemtime($file) < time() - 86400 * 7) { // Older than 7 days
                $orphaned++;
            }
        }
        
        if ($orphaned > 0) {
            $this->issues[] = [
                'type' => 'orphaned_files',
                'message' => "Found {$orphaned} orphaned view cache files",
                'count' => $orphaned
            ];
        }
    }
    
    private function checkPermissions(): void
    {
        $this->line('→ Checking permissions...');
        
        $paths = [
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('logs'),
            base_path('bootstrap/cache')
        ];
        
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            
            $stat = stat($path);
            $owner = posix_getpwuid($stat['uid'])['name'] ?? 'unknown';
            $group = posix_getgrgid($stat['gid'])['name'] ?? 'unknown';
            
            if ($owner !== 'www-data' || $group !== 'www-data') {
                $this->issues[] = [
                    'type' => 'incorrect_ownership',
                    'message' => "Incorrect ownership for {$path}",
                    'current' => "{$owner}:{$group}",
                    'expected' => 'www-data:www-data'
                ];
            }
            
            if ($this->option('detailed')) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $this->line("  {$path}: {$owner}:{$group} ({$perms})");
            }
        }
        
        if (count($this->issues) === 0) {
            $this->info('  ✓ Permissions correct');
        }
    }
    
    private function checkDiskSpace(): void
    {
        $this->line('→ Checking disk space...');
        
        $path = storage_path();
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $used = $total - $free;
        $percentage = round(($used / $total) * 100, 2);
        
        $this->metrics['disk_usage_percentage'] = $percentage;
        $this->metrics['disk_free_gb'] = round($free / 1073741824, 2);
        
        if ($percentage > 90) {
            $this->issues[] = [
                'type' => 'low_disk_space',
                'message' => "Low disk space: {$percentage}% used",
                'free_gb' => round($free / 1073741824, 2)
            ];
            $this->error("  ✗ Disk usage critical: {$percentage}%");
        } elseif ($percentage > 80) {
            $this->warn("  ⚠ Disk usage warning: {$percentage}%");
        } else {
            $this->info("  ✓ Disk usage normal: {$percentage}%");
        }
    }
    
    private function checkCompiledViews(): void
    {
        $this->line('→ Checking compiled views integrity...');
        
        $viewPath = storage_path('framework/views');
        $files = glob($viewPath . '/*.php');
        $errors = 0;
        
        foreach ($files as $file) {
            // Check if file is readable
            if (!is_readable($file)) {
                $errors++;
                continue;
            }
            
            // Check for PHP syntax errors
            $output = shell_exec("php -l {$file} 2>&1");
            if (strpos($output, 'No syntax errors') === false) {
                $errors++;
                if ($this->option('detailed')) {
                    $this->error("  Syntax error in: " . basename($file));
                }
            }
        }
        
        if ($errors > 0) {
            $this->issues[] = [
                'type' => 'corrupted_views',
                'message' => "Found {$errors} corrupted view files",
                'count' => $errors
            ];
            $this->error("  ✗ Found {$errors} corrupted views");
        } else {
            $this->info('  ✓ All compiled views valid');
        }
    }
    
    private function checkOPcache(): void
    {
        $this->line('→ Checking OPcache...');
        
        if (!function_exists('opcache_get_status')) {
            $this->warn('  ⚠ OPcache not available');
            return;
        }
        
        $status = opcache_get_status();
        if ($status === false) {
            $this->warn('  ⚠ OPcache disabled');
            return;
        }
        
        $hitRate = round($status['opcache_statistics']['opcache_hit_rate'] ?? 0, 2);
        $memoryUsage = round($status['memory_usage']['used_memory'] / $status['memory_usage']['free_memory'] * 100, 2);
        
        $this->metrics['opcache_hit_rate'] = $hitRate;
        $this->metrics['opcache_memory_usage'] = $memoryUsage;
        
        if ($hitRate < 90) {
            $this->warn("  ⚠ OPcache hit rate low: {$hitRate}%");
        } else {
            $this->info("  ✓ OPcache hit rate: {$hitRate}%");
        }
    }
    
    private function checkFilamentCaches(): void
    {
        $this->line('→ Checking Filament caches...');
        
        $cacheFiles = [
            'bootstrap/cache/filament/panels/admin.php',
            'bootstrap/cache/blade-icons.php',
            'bootstrap/cache/livewire-components.php'
        ];
        
        $missing = [];
        foreach ($cacheFiles as $file) {
            $fullPath = base_path($file);
            if (!file_exists($fullPath)) {
                $missing[] = $file;
            }
        }
        
        if (count($missing) > 0) {
            $this->issues[] = [
                'type' => 'missing_filament_cache',
                'message' => 'Missing Filament cache files',
                'files' => $missing
            ];
            $this->warn('  ⚠ Missing ' . count($missing) . ' Filament cache files');
        } else {
            $this->info('  ✓ All Filament caches present');
        }
    }
    
    private function checkRecentErrors(): void
    {
        $this->line('→ Checking recent errors...');
        
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) {
            $this->info('  ✓ No error log found');
            return;
        }
        
        // Check last 100 lines for view cache errors
        $lines = $this->tailFile($logFile, 100);
        $viewErrors = 0;
        $recentTime = time() - 3600; // Last hour
        
        foreach ($lines as $line) {
            if (strpos($line, 'filemtime(): stat failed') !== false ||
                strpos($line, 'View cache error') !== false) {
                // Try to extract timestamp
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $timestamp = strtotime($matches[1]);
                    if ($timestamp > $recentTime) {
                        $viewErrors++;
                    }
                }
            }
        }
        
        $this->metrics['recent_view_errors'] = $viewErrors;
        
        if ($viewErrors > 0) {
            $this->issues[] = [
                'type' => 'recent_errors',
                'message' => "Found {$viewErrors} view cache errors in the last hour",
                'count' => $viewErrors
            ];
            $this->error("  ✗ Found {$viewErrors} recent view cache errors");
        } else {
            $this->info('  ✓ No recent view cache errors');
        }
    }
    
    private function displayResults(): void
    {
        $this->info('');
        $this->info('=========================================');
        $this->info('Health Check Results');
        $this->info('=========================================');
        
        if (count($this->issues) === 0) {
            $this->info('✅ All checks passed! View cache is healthy.');
        } else {
            $this->error('❌ Found ' . count($this->issues) . ' issue(s):');
            foreach ($this->issues as $issue) {
                $this->error('  • ' . $issue['message']);
            }
        }
        
        if ($this->option('verbose')) {
            $this->info('');
            $this->info('Metrics:');
            $this->table(
                ['Metric', 'Value'],
                array_map(fn($k, $v) => [$k, $v], array_keys($this->metrics), $this->metrics)
            );
        }
    }
    
    private function fixIssues(): void
    {
        $this->info('');
        $this->info('=========================================');
        $this->info('Attempting to fix issues...');
        $this->info('=========================================');
        
        foreach ($this->issues as $issue) {
            switch ($issue['type']) {
                case 'directory_missing':
                case 'directory_not_writable':
                case 'incorrect_ownership':
                    $this->fixPermissions();
                    break;
                    
                case 'orphaned_files':
                case 'corrupted_views':
                    $this->cleanViewCache();
                    break;
                    
                case 'missing_filament_cache':
                    $this->rebuildFilamentCache();
                    break;
                    
                case 'recent_errors':
                    $this->clearAndRebuildCache();
                    break;
            }
        }
        
        $this->info('✅ Fix attempts completed');
    }
    
    private function fixPermissions(): void
    {
        $this->info('→ Fixing permissions...');
        $this->call('cache:clear');
        exec('chown -R www-data:www-data ' . storage_path());
        exec('chmod -R 775 ' . storage_path());
        exec('chmod -R 775 ' . base_path('bootstrap/cache'));
    }
    
    private function cleanViewCache(): void
    {
        $this->info('→ Cleaning view cache...');
        $this->call('view:clear');
        
        // Remove orphaned files
        $viewPath = storage_path('framework/views');
        $files = glob($viewPath . '/*.php');
        foreach ($files as $file) {
            if (filemtime($file) < time() - 86400 * 7) {
                unlink($file);
            }
        }
        
        $this->call('view:cache');
    }
    
    private function rebuildFilamentCache(): void
    {
        $this->info('→ Rebuilding Filament cache...');
        
        $commands = [
            'filament:cache-components',
            'filament:optimize',
            'icons:cache',
            'livewire:discover'
        ];
        
        foreach ($commands as $command) {
            try {
                $this->call($command);
            } catch (\Exception $e) {
                // Command might not exist
            }
        }
    }
    
    private function clearAndRebuildCache(): void
    {
        $this->info('→ Clearing and rebuilding all caches...');
        $this->cacheService->rebuild();
    }
    
    private function storeMetrics(): void
    {
        try {
            $key = 'view_cache_health:metrics';
            $this->metrics['timestamp'] = time();
            $this->metrics['issues_count'] = count($this->issues);
            
            Redis::setex($key, 3600, json_encode($this->metrics));
            
            // Also log to monitoring system
            Log::channel('monitoring')->info('View cache health check', $this->metrics);
        } catch (\Exception $e) {
            // Silently fail if Redis is not available
        }
    }
    
    private function tailFile($file, $lines = 100): array
    {
        $result = [];
        $fp = fopen($file, 'r');
        if (!$fp) {
            return $result;
        }
        
        $pos = -2;
        $t = ' ';
        $currentLine = '';
        
        while ($lines > 0) {
            while ($t !== "\n") {
                if (!fseek($fp, $pos, SEEK_END)) {
                    $t = fgetc($fp);
                    $currentLine = $t . $currentLine;
                    $pos--;
                } else {
                    rewind($fp);
                    break;
                }
            }
            
            if ($currentLine !== '') {
                $result[] = $currentLine;
                $lines--;
            }
            
            $currentLine = '';
            $t = ' ';
        }
        
        fclose($fp);
        return array_reverse($result);
    }
}