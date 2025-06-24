<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SystemHealthCheck extends Command
{
    protected $signature = 'system:health 
                            {--detailed : Show detailed information}
                            {--fix : Attempt to fix issues}';
    
    protected $description = 'Run comprehensive system health check';
    
    private array $issues = [];
    private array $warnings = [];
    
    public function handle()
    {
        $this->info('=== AskProAI System Health Check ===');
        $this->info('Time: ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();
        
        // Run all checks
        $this->checkPHP();
        $this->checkDatabase();
        $this->checkRedis();
        $this->checkQueue();
        $this->checkStorage();
        $this->checkExternalServices();
        $this->checkPerformance();
        
        // Display results
        $this->displayResults();
        
        // Fix issues if requested
        if ($this->option('fix') && count($this->issues) > 0) {
            $this->fixIssues();
        }
        
        return count($this->issues) === 0 ? Command::SUCCESS : Command::FAILURE;
    }
    
    private function checkPHP()
    {
        $this->info('1. PHP Configuration:');
        
        $memory = ini_get('memory_limit');
        $memoryMB = $this->convertToMB($memory);
        
        $this->line("   Memory Limit: $memory");
        if ($memoryMB < 512) {
            $this->issues[] = "PHP memory limit too low: $memory (should be at least 512M)";
        }
        
        $this->line("   Max Execution Time: " . ini_get('max_execution_time') . "s");
        $this->line("   PHP Version: " . PHP_VERSION);
        
        // Check required extensions
        $required = ['pdo', 'pdo_mysql', 'mbstring', 'xml', 'curl', 'json', 'bcmath', 'redis'];
        $missing = [];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (!empty($missing)) {
            $this->issues[] = "Missing PHP extensions: " . implode(', ', $missing);
        }
        
        $this->newLine();
    }
    
    private function checkDatabase()
    {
        $this->info('2. Database:');
        
        try {
            $pdo = DB::connection()->getPdo();
            $this->line('   ✓ Connection: OK');
            
            // Check database size
            $size = DB::select("SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()")[0]->size_mb;
            
            $this->line("   Database Size: {$size}MB");
            
            // Check slow queries
            $slowQueries = DB::table('calls')
                ->selectRaw('COUNT(*) as count')
                ->whereRaw('created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)')
                ->first();
            
            // Check pending migrations
            $pending = count(app('migrator')->pendingMigrations(
                app('migrator')->paths()
            ));
            
            if ($pending > 0) {
                $this->warnings[] = "$pending pending migrations";
                $this->line("   ⚠️  Pending Migrations: $pending");
            }
            
        } catch (\Exception $e) {
            $this->issues[] = "Database connection failed: " . $e->getMessage();
            $this->line('   ✗ Connection: FAILED');
        }
        
        $this->newLine();
    }
    
    private function checkRedis()
    {
        $this->info('3. Redis:');
        
        try {
            Redis::ping();
            $this->line('   ✓ Connection: OK');
            
            $info = Redis::info();
            $memory = $info['used_memory_human'] ?? 'Unknown';
            $this->line("   Memory Usage: $memory");
            
        } catch (\Exception $e) {
            $this->issues[] = "Redis connection failed: " . $e->getMessage();
            $this->line('   ✗ Connection: FAILED');
        }
        
        $this->newLine();
    }
    
    private function checkQueue()
    {
        $this->info('4. Queue System:');
        
        try {
            // Check Horizon status
            $horizonStatus = trim(shell_exec('php artisan horizon:status 2>&1'));
            
            if (str_contains($horizonStatus, 'Horizon is running')) {
                $this->line('   ✓ Horizon: Running');
            } else {
                $this->warnings[] = "Horizon not running";
                $this->line('   ⚠️  Horizon: Not Running');
            }
            
            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            if ($failedJobs > 0) {
                $this->warnings[] = "$failedJobs failed jobs in queue";
                $this->line("   ⚠️  Failed Jobs: $failedJobs");
            } else {
                $this->line('   ✓ Failed Jobs: 0');
            }
            
        } catch (\Exception $e) {
            $this->line('   ⚠️  Queue check failed: ' . $e->getMessage());
        }
        
        $this->newLine();
    }
    
    private function checkStorage()
    {
        $this->info('5. Storage:');
        
        // Check disk space
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsedPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
        
        $this->line("   Disk Usage: {$diskUsedPercent}%");
        if ($diskUsedPercent > 90) {
            $this->issues[] = "Disk space critically low: {$diskUsedPercent}% used";
        } elseif ($diskUsedPercent > 80) {
            $this->warnings[] = "Disk space warning: {$diskUsedPercent}% used";
        }
        
        // Check storage permissions
        $dirs = ['storage/app', 'storage/logs', 'storage/framework', 'bootstrap/cache'];
        foreach ($dirs as $dir) {
            if (!is_writable(base_path($dir))) {
                $this->issues[] = "Directory not writable: $dir";
            }
        }
        
        $this->newLine();
    }
    
    private function checkExternalServices()
    {
        $this->info('6. External Services:');
        
        // Check Cal.com
        try {
            $response = Http::timeout(5)
                ->withHeaders(['apiKey' => config('services.calcom.api_key')])
                ->get('https://api.cal.com/v2/event-types');
            
            if ($response->successful()) {
                $this->line('   ✓ Cal.com API: OK');
            } else {
                $this->warnings[] = "Cal.com API returned status: " . $response->status();
                $this->line('   ⚠️  Cal.com API: Status ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->line('   ⚠️  Cal.com API: Timeout/Error');
        }
        
        // Check Retell.ai
        try {
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.retell.api_key')])
                ->get('https://api.retell.ai/list-agents');
            
            if ($response->successful()) {
                $this->line('   ✓ Retell.ai API: OK');
            } else {
                $this->warnings[] = "Retell.ai API returned status: " . $response->status();
                $this->line('   ⚠️  Retell.ai API: Status ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->line('   ⚠️  Retell.ai API: Timeout/Error');
        }
        
        $this->newLine();
    }
    
    private function checkPerformance()
    {
        $this->info('7. Performance Metrics:');
        
        // Average response time (last hour)
        $avgResponseTime = DB::table('api_call_logs')
            ->where('created_at', '>', now()->subHour())
            ->avg('duration_ms');
        
        if ($avgResponseTime) {
            $this->line("   Avg API Response Time: " . round($avgResponseTime) . "ms");
            if ($avgResponseTime > 1000) {
                $this->warnings[] = "High average response time: " . round($avgResponseTime) . "ms";
            }
        }
        
        // Current connections
        try {
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
            $this->line("   MySQL Connections: $connections");
            
            if ($connections > 100) {
                $this->warnings[] = "High number of MySQL connections: $connections";
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        $this->newLine();
    }
    
    private function displayResults()
    {
        $this->info('=== Health Check Summary ===');
        
        if (empty($this->issues) && empty($this->warnings)) {
            $this->info('✓ System is healthy!');
            return;
        }
        
        if (!empty($this->issues)) {
            $this->error('Critical Issues (' . count($this->issues) . '):');
            foreach ($this->issues as $issue) {
                $this->line("  ✗ $issue");
            }
            $this->newLine();
        }
        
        if (!empty($this->warnings)) {
            $this->warn('Warnings (' . count($this->warnings) . '):');
            foreach ($this->warnings as $warning) {
                $this->line("  ⚠️  $warning");
            }
        }
        
        if ($this->option('detailed')) {
            $this->newLine();
            $this->info('Recommendations:');
            
            if (in_array("PHP memory limit too low", $this->issues)) {
                $this->line('  - Increase PHP memory_limit in /etc/php/8.3/fpm/php.ini');
            }
            
            if (in_array("Horizon not running", $this->warnings)) {
                $this->line('  - Start Horizon: php artisan horizon');
            }
            
            if (count($this->warnings) > 0) {
                foreach ($this->warnings as $warning) {
                    if (str_contains($warning, 'failed jobs')) {
                        $this->line('  - Review failed jobs: php artisan queue:failed');
                    }
                    if (str_contains($warning, 'pending migrations')) {
                        $this->line('  - Run migrations: php artisan migrate --force');
                    }
                }
            }
        }
    }
    
    private function fixIssues()
    {
        $this->newLine();
        
        if (!$this->confirm('Attempt to fix issues automatically?')) {
            return;
        }
        
        $fixed = 0;
        
        // Fix storage permissions
        foreach ($this->issues as $issue) {
            if (str_contains($issue, 'Directory not writable')) {
                preg_match('/Directory not writable: (.+)/', $issue, $matches);
                if (isset($matches[1])) {
                    $dir = base_path($matches[1]);
                    shell_exec("chmod -R 775 $dir");
                    shell_exec("chown -R www-data:www-data $dir");
                    $this->info("Fixed permissions for: $matches[1]");
                    $fixed++;
                }
            }
        }
        
        // Clear caches if performance issues
        if (count($this->warnings) > 0) {
            $this->call('optimize:clear');
            $this->info('Cleared all caches');
            $fixed++;
        }
        
        $this->info("Fixed $fixed issues automatically.");
    }
    
    private function convertToMB($size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) $size;
        
        return match($unit) {
            'g' => $value * 1024,
            'k' => $value / 1024,
            default => $value
        };
    }
}