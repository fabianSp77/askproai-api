<?php

namespace App\Services\ChaosMonkey;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChaosMonkeyService
{
    protected $enabled = false;
    protected $activeFailures = [];
    
    public function __construct()
    {
        $this->enabled = config('chaos.enabled', false);
    }
    
    /**
     * Simulate database failure for given duration
     */
    public function simulateDatabaseFailure(int $seconds): void
    {
        if (!$this->enabled) return;
        
        $this->activeFailures['database'] = [
            'type' => 'connection_failure',
            'until' => now()->addSeconds($seconds)
        ];
        
        Cache::put('chaos:database:failure', true, $seconds);
        Log::warning('Chaos Monkey: Database failure simulated', ['duration' => $seconds]);
    }
    
    /**
     * Restore database connection
     */
    public function restoreDatabase(): void
    {
        unset($this->activeFailures['database']);
        Cache::forget('chaos:database:failure');
        Log::info('Chaos Monkey: Database restored');
    }
    
    /**
     * Simulate memory leak
     */
    public function simulateMemoryLeak(int $bytes): void
    {
        if (!$this->enabled) return;
        
        $leak = str_repeat('X', $bytes);
        $this->activeFailures['memory'][] = $leak;
        
        Log::warning('Chaos Monkey: Memory leak simulated', ['bytes' => $bytes]);
    }
    
    /**
     * Create network partition between services
     */
    public function createNetworkPartition(array $partitions): void
    {
        if (!$this->enabled) return;
        
        Cache::put('chaos:network:partitions', $partitions, 300);
        Log::warning('Chaos Monkey: Network partition created', $partitions);
    }
    
    /**
     * Simulate CPU spike
     */
    public function simulateCpuSpike(int $percentage): void
    {
        if (!$this->enabled) return;
        
        Cache::put('chaos:cpu:spike', $percentage, 60);
        Log::warning('Chaos Monkey: CPU spike simulated', ['percentage' => $percentage]);
    }
    
    /**
     * Enable latency injection
     */
    public function enableLatencyInjection(array $config): void
    {
        if (!$this->enabled) return;
        
        Cache::put('chaos:latency:config', $config, 300);
        Log::warning('Chaos Monkey: Latency injection enabled', $config);
    }
    
    /**
     * Simulate low disk space
     */
    public function simulateLowDiskSpace(int $percentageFull): void
    {
        if (!$this->enabled) return;
        
        Cache::put('chaos:disk:full', $percentageFull, 300);
        Log::warning('Chaos Monkey: Low disk space simulated', ['percentage_full' => $percentageFull]);
    }
    
    /**
     * Simulate clock drift
     */
    public function simulateClockDrift(array $drifts): void
    {
        if (!$this->enabled) return;
        
        Cache::put('chaos:clock:drifts', $drifts, 300);
        Log::warning('Chaos Monkey: Clock drift simulated', $drifts);
    }
    
    /**
     * Create zombie process
     */
    public function createZombieProcess(string $name): void
    {
        if (!$this->enabled) return;
        
        $zombies = Cache::get('chaos:zombies', []);
        $zombies[] = ['name' => $name, 'pid' => rand(1000, 9999), 'created_at' => now()];
        Cache::put('chaos:zombies', $zombies, 300);
        
        Log::warning('Chaos Monkey: Zombie process created', ['name' => $name]);
    }
    
    /**
     * Enable full chaos mode
     */
    public function enableChaosMode(array $options): void
    {
        if (!$this->enabled) return;
        
        Cache::put('chaos:mode:full', $options, 300);
        Log::warning('Chaos Monkey: Full chaos mode enabled', $options);
    }
    
    /**
     * Check if failure is active
     */
    public function isFailureActive(string $type): bool
    {
        if (!$this->enabled) return false;
        
        return Cache::has("chaos:{$type}:failure") || 
               isset($this->activeFailures[$type]);
    }
}
