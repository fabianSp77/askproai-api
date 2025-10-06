<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitorCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor cache health and performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $issues = [];
        
        // Test cache connectivity
        try {
            $testKey = 'monitor_test_' . uniqid();
            Cache::put($testKey, 'test', 60);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($value !== 'test') {
                $issues[] = 'Cache read/write test failed';
            }
        } catch (\Exception $e) {
            $issues[] = 'Cache connection failed: ' . $e->getMessage();
            Log::error('Cache monitor error', ['error' => $e->getMessage()]);
        }
        
        // Check Redis memory if using Redis
        if (config('cache.default') === 'redis') {
            try {
                $redis = Cache::getRedis();
                $info = $redis->info('memory');
                $usedMemory = $info['used_memory_human'] ?? 'unknown';
                $maxMemory = $info['maxmemory_human'] ?? 'unlimited';
                
                $this->info("Redis Memory: $usedMemory / $maxMemory");
                
                // Alert if memory usage is high
                if ($maxMemory !== 'unlimited' && $maxMemory !== '0B') {
                    $usedBytes = $info['used_memory'] ?? 0;
                    $maxBytes = $info['maxmemory'] ?? 0;
                    if ($maxBytes > 0 && ($usedBytes / $maxBytes) > 0.9) {
                        $issues[] = 'Redis memory usage above 90%';
                    }
                }
            } catch (\Exception $e) {
                $issues[] = 'Redis monitoring failed: ' . $e->getMessage();
            }
        }
        
        // Log results
        $executionTime = round(microtime(true) - $startTime, 3);
        
        if (count($issues) > 0) {
            foreach ($issues as $issue) {
                $this->error($issue);
                Log::warning('Cache monitor issue', ['issue' => $issue]);
            }
            return Command::FAILURE;
        }
        
        $this->info("Cache monitoring completed in {$executionTime}s");
        Log::info('Cache monitor success', [
            'execution_time' => $executionTime,
            'timestamp' => Carbon::now()->toIso8601String()
        ]);
        
        return Command::SUCCESS;
    }
}