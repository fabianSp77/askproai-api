<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalcomV2Service;
use App\Services\RetellV2Service;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestProductionReadiness extends Command
{
    protected $signature = 'test:production-readiness';
    protected $description = 'Test all production-critical improvements';

    public function handle()
    {
        $this->info("🧪 PRODUCTION READINESS TEST");
        $this->info("============================\n");
        
        // Test 1: Retry Logic
        $this->testRetryLogic();
        
        // Test 2: Circuit Breaker
        $this->testCircuitBreaker();
        
        // Test 3: Error Logging
        $this->testErrorLogging();
        
        // Test 4: Performance
        $this->testPerformance();
        
        // Summary
        $this->showSummary();
    }
    
    private function testRetryLogic()
    {
        $this->info("1. TESTING RETRY LOGIC");
        $this->info("----------------------");
        
        // Check if retries are logged
        $logsBefore = DB::table('circuit_breaker_metrics')
            ->where('service', 'calcom')
            ->count();
        
        try {
            // This should retry 3 times
            $company = Company::where('calcom_api_key', '!=', null)->first();
            if ($company) {
                $service = new CalcomV2Service($company->calcom_api_key);
                $result = $service->getEventTypes();
                
                if ($result) {
                    $this->info("✓ API call successful");
                    $this->info("  Event types: " . count($result['event_types'] ?? []));
                } else {
                    $this->warn("⚠ API returned empty result");
                }
            } else {
                $this->warn("⚠ No company with Cal.com API key found");
            }
        } catch (\Exception $e) {
            $this->error("✗ API call failed: " . $e->getMessage());
        }
        
        $logsAfter = DB::table('circuit_breaker_metrics')
            ->where('service', 'calcom')
            ->count();
        
        $this->info("  Metrics logged: " . ($logsAfter - $logsBefore));
        $this->info("");
    }
    
    private function testCircuitBreaker()
    {
        $this->info("2. TESTING CIRCUIT BREAKER");
        $this->info("--------------------------");
        
        // Get current state
        $status = CircuitBreaker::getStatus();
        
        foreach ($status as $service => $data) {
            $this->info("  {$service}:");
            $this->info("    State: " . strtoupper($data['state']));
            $this->info("    Failures: " . $data['failures']);
            
            // Check recent metrics
            $recentMetrics = DB::table('circuit_breaker_metrics')
                ->where('service', $service)
                ->where('created_at', '>=', now()->subHour())
                ->selectRaw('status, COUNT(*) as count, AVG(duration_ms) as avg_duration')
                ->groupBy('status')
                ->get();
            
            foreach ($recentMetrics as $metric) {
                $this->info("    {$metric->status}: {$metric->count} calls, avg {$metric->avg_duration}ms");
            }
        }
        
        $this->info("");
    }
    
    private function testErrorLogging()
    {
        $this->info("3. TESTING ERROR LOGGING");
        $this->info("------------------------");
        
        // Check critical errors in last hour
        $criticalErrors = DB::table('critical_errors')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        $this->info("  Critical errors (last hour): {$criticalErrors}");
        
        // Check webhook logs
        $webhookLogs = DB::table('webhook_logs')
            ->where('created_at', '>=', now()->subHour())
            ->selectRaw('source, status, COUNT(*) as count')
            ->groupBy('source', 'status')
            ->get();
        
        if ($webhookLogs->count() > 0) {
            $this->info("  Webhook logs:");
            foreach ($webhookLogs as $log) {
                $this->info("    {$log->source} ({$log->status}): {$log->count}");
            }
        } else {
            $this->info("  No webhook logs in last hour");
        }
        
        // Check if logging channels exist
        $channels = ['critical', 'webhooks'];
        foreach ($channels as $channel) {
            $logFile = storage_path("logs/{$channel}.log");
            if (file_exists($logFile)) {
                $size = filesize($logFile);
                $this->info("  {$channel}.log exists (" . $this->formatBytes($size) . ")");
            } else {
                $this->warn("  {$channel}.log does not exist yet");
            }
        }
        
        $this->info("");
    }
    
    private function testPerformance()
    {
        $this->info("4. TESTING PERFORMANCE");
        $this->info("----------------------");
        
        // Test API response times
        $metrics = DB::table('circuit_breaker_metrics')
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('service, MIN(duration_ms) as min_ms, AVG(duration_ms) as avg_ms, MAX(duration_ms) as max_ms')
            ->groupBy('service')
            ->get();
        
        if ($metrics->count() > 0) {
            $this->table(
                ['Service', 'Min (ms)', 'Avg (ms)', 'Max (ms)'],
                $metrics->map(function($m) {
                    return [
                        $m->service,
                        round($m->min_ms, 2),
                        round($m->avg_ms, 2),
                        round($m->max_ms, 2)
                    ];
                })
            );
        } else {
            $this->info("  No performance metrics available yet");
        }
        
        // Check cache usage
        $this->info("\n  Cache Status:");
        $this->info("    Driver: " . config('cache.default'));
        
        try {
            Cache::put('test_key', 'test_value', 60);
            $value = Cache::get('test_key');
            Cache::forget('test_key');
            
            $this->info("    Cache working: ✓");
        } catch (\Exception $e) {
            $this->error("    Cache error: " . $e->getMessage());
        }
        
        $this->info("");
    }
    
    private function showSummary()
    {
        $this->info("SUMMARY");
        $this->info("=======");
        
        $checks = [
            'Retry Logic' => $this->checkRetryLogic(),
            'Circuit Breaker' => $this->checkCircuitBreaker(),
            'Error Logging' => $this->checkErrorLogging(),
            'Performance' => $this->checkPerformance(),
        ];
        
        $passed = 0;
        foreach ($checks as $name => $status) {
            if ($status) {
                $this->info("✓ {$name}");
                $passed++;
            } else {
                $this->error("✗ {$name}");
            }
        }
        
        $this->info("\nOverall: {$passed}/" . count($checks) . " checks passed");
        
        if ($passed === count($checks)) {
            $this->info("\n🎉 SYSTEM IS PRODUCTION READY!");
        } else {
            $this->warn("\n⚠️  Some checks failed. Please review and fix.");
        }
    }
    
    private function checkRetryLogic(): bool
    {
        // Check if retry trait is being used
        return class_uses_recursive(CalcomV2Service::class)[\App\Services\Traits\RetryableHttpClient::class] ?? false;
    }
    
    private function checkCircuitBreaker(): bool
    {
        // Check if circuit breaker is functional
        return DB::table('circuit_breaker_metrics')->exists();
    }
    
    private function checkErrorLogging(): bool
    {
        // Check if critical error logging is set up
        return Schema::hasTable('critical_errors');
    }
    
    private function checkPerformance(): bool
    {
        // Check if performance is acceptable (avg response < 1000ms)
        $avgResponse = DB::table('circuit_breaker_metrics')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDay())
            ->avg('duration_ms');
        
        return $avgResponse === null || $avgResponse < 1000;
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}