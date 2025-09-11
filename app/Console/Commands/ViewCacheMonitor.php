<?php

namespace App\Console\Commands;

use App\Services\ViewCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ViewCacheMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:monitor 
                            {--fix : Automatically fix issues when detected}
                            {--continuous : Run continuously with periodic checks}
                            {--interval=300 : Check interval in seconds (default: 5 minutes)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and maintain view cache health';

    /**
     * Execute the console command.
     */
    public function handle(ViewCacheService $cacheService): int
    {
        $autoFix = $this->option('fix');
        $continuous = $this->option('continuous');
        $interval = (int) $this->option('interval');
        
        $this->info('🔍 Starting view cache monitor...');
        
        if ($continuous) {
            $this->info("Running in continuous mode (checking every {$interval} seconds)");
            $this->runContinuous($cacheService, $autoFix, $interval);
        } else {
            $this->runOnce($cacheService, $autoFix);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Run a single health check
     */
    private function runOnce(ViewCacheService $cacheService, bool $autoFix): void
    {
        $health = $cacheService->checkHealth();
        
        $this->displayHealthStatus($health);
        
        if ($health['status'] !== 'healthy' && $autoFix) {
            $this->warn('Issues detected. Attempting auto-fix...');
            
            if ($cacheService->autoFix()) {
                $this->info('✅ Auto-fix completed successfully');
                
                // Re-check health
                $health = $cacheService->checkHealth();
                $this->displayHealthStatus($health);
            } else {
                $this->error('❌ Auto-fix failed. Manual intervention may be required.');
            }
        }
    }
    
    /**
     * Run continuous monitoring
     */
    private function runContinuous(ViewCacheService $cacheService, bool $autoFix, int $interval): void
    {
        $errorCount = 0;
        $maxErrors = 3;
        
        while (true) {
            $this->line('');
            $this->info('[' . now()->format('Y-m-d H:i:s') . '] Running health check...');
            
            $health = $cacheService->checkHealth();
            $this->displayHealthStatus($health);
            
            if ($health['status'] !== 'healthy') {
                $errorCount++;
                $this->warn("Error count: {$errorCount}/{$maxErrors}");
                
                if ($errorCount >= $maxErrors && $autoFix) {
                    $this->warn('Maximum error count reached. Triggering auto-fix...');
                    
                    if ($cacheService->autoFix()) {
                        $this->info('✅ Auto-fix completed successfully');
                        $errorCount = 0;
                    } else {
                        $this->error('❌ Auto-fix failed');
                        Log::critical('View cache auto-fix failed after multiple attempts');
                    }
                }
            } else {
                if ($errorCount > 0) {
                    $this->info('✅ System recovered. Resetting error count.');
                    $errorCount = 0;
                }
            }
            
            $this->info("Next check in {$interval} seconds... (Press Ctrl+C to stop)");
            sleep($interval);
        }
    }
    
    /**
     * Display health status in a formatted way
     */
    private function displayHealthStatus(array $health): void
    {
        $statusIcon = match($health['status']) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'degraded' => '🔶',
            'error' => '❌',
            default => '❓'
        };
        
        $this->line('');
        $this->line("Status: {$statusIcon} {$health['status']}");
        $this->line("Timestamp: {$health['timestamp']}");
        $this->line('');
        
        $this->table(
            ['Check', 'Status', 'Message'],
            array_map(function ($check) {
                return [
                    $check['name'],
                    $check['status'] ? '✅' : '❌',
                    $check['message']
                ];
            }, $health['checks'])
        );
    }
}