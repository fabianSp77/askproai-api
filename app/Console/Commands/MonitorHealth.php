<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class MonitorHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor application health and detect 500 errors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $alerts = [];
        $checks = [
            'database' => false,
            'cache' => false,
            'endpoints' => false,
            'error_rate' => false
        ];
        
        // 1. Database connectivity check
        try {
            DB::select('SELECT 1');
            $checks['database'] = true;
            $this->info('✓ Database connection OK');
        } catch (\Exception $e) {
            $alerts[] = '🔴 Database connection failed: ' . $e->getMessage();
            Log::critical('Health check: Database down', ['error' => $e->getMessage()]);
        }
        
        // 2. Cache connectivity check
        try {
            Cache::put('health_check', time(), 60);
            Cache::get('health_check');
            $checks['cache'] = true;
            $this->info('✓ Cache connection OK');
        } catch (\Exception $e) {
            $alerts[] = '🔴 Cache connection failed: ' . $e->getMessage();
            Log::critical('Health check: Cache down', ['error' => $e->getMessage()]);
        }
        
        // 3. Check critical endpoints
        $endpoints = [
            'https://api.askproai.de/api/health',
            'https://api.askproai.de/login',
            'https://api.askproai.de/admin/login'
        ];
        
        $endpointErrors = 0;
        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(5)->get($endpoint);
                if ($response->status() >= 500) {
                    $endpointErrors++;
                    $alerts[] = "🔴 Endpoint $endpoint returned {$response->status()}";
                    Log::error('Health check: 500 error', [
                        'endpoint' => $endpoint,
                        'status' => $response->status()
                    ]);
                } else {
                    $this->info("✓ Endpoint $endpoint: {$response->status()}");
                }
            } catch (\Exception $e) {
                $endpointErrors++;
                $alerts[] = "🔴 Endpoint $endpoint failed: " . $e->getMessage();
            }
        }
        
        $checks['endpoints'] = $endpointErrors === 0;
        
        // 4. Check error rate in logs
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $recentLogs = shell_exec("tail -1000 $logFile 2>/dev/null | grep -c 'ERROR' || echo 0");
            $errorCount = intval(trim($recentLogs));
            
            if ($errorCount > 50) {
                $alerts[] = "🟡 High error rate: $errorCount errors in recent logs";
                Log::warning('Health check: High error rate', ['count' => $errorCount]);
            } else {
                $checks['error_rate'] = true;
                $this->info("✓ Error rate normal: $errorCount errors");
            }
        }
        
        // 5. Check queue worker status
        try {
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            
            if ($failedJobs > 10) {
                $alerts[] = "🟡 Failed jobs: $failedJobs";
            }
            
            if ($queueSize > 100) {
                $alerts[] = "🟡 Queue backlog: $queueSize jobs";
            }
            
            $this->info("✓ Queue: $queueSize pending, $failedJobs failed");
        } catch (\Exception $e) {
            $this->warn('Could not check queue status');
        }
        
        // Send alerts if critical issues found
        $executionTime = round(microtime(true) - $startTime, 3);
        
        if (count($alerts) > 0) {
            // Log all alerts
            foreach ($alerts as $alert) {
                $this->error($alert);
            }
            
            // Store alert state for external monitoring
            Cache::put('health_alerts', [
                'alerts' => $alerts,
                'timestamp' => Carbon::now()->toIso8601String(),
                'checks' => $checks
            ], 3600);
            
            // Critical alert if database or multiple services down
            $falseCount = 0;
            foreach ($checks as $check) {
                if ($check === false) {
                    $falseCount++;
                }
            }
            if (!$checks['database'] || $falseCount > 2) {
                Log::critical('SYSTEM HEALTH CRITICAL', [
                    'alerts' => $alerts,
                    'checks' => $checks
                ]);
                
                // Could trigger external alerting here (email, SMS, etc.)
                $this->error('⚠️  CRITICAL: Multiple system failures detected!');
            }
            
            return Command::FAILURE;
        }
        
        // Clear previous alerts if everything OK
        Cache::forget('health_alerts');
        
        $this->info("\n✅ Health check passed in {$executionTime}s");
        Log::info('Health check success', [
            'execution_time' => $executionTime,
            'timestamp' => Carbon::now()->toIso8601String()
        ]);
        
        return Command::SUCCESS;
    }
}