<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SimpleCacheWarmup extends Command
{
    protected $signature = 'cache:simple-warmup';
    protected $description = 'Simple cache warmup for critical data';

    public function handle()
    {
        $this->info('🔥 Starting Simple Cache Warmup...');
        $startTime = microtime(true);
        
        // Warm critical queries
        $this->warmCriticalCaches();
        
        $duration = round(microtime(true) - $startTime, 2);
        $this->info("✅ Cache warmup completed in {$duration} seconds");
    }

    private function warmCriticalCaches()
    {
        // Cache user count
        Cache::remember('stats:users:total', 3600, function () {
            return DB::table('users')->count();
        });
        $this->info('✓ Cached user stats');
        
        // Cache customer count
        Cache::remember('stats:customers:total', 3600, function () {
            return DB::table('customers')->count();
        });
        $this->info('✓ Cached customer stats');
        
        // Cache today's appointments
        Cache::remember('appointments:today:count', 600, function () {
            return DB::table('appointments')
                ->whereDate('start_time', today())
                ->count();
        });
        $this->info('✓ Cached appointment stats');
        
        // Cache recent calls
        Cache::remember('calls:recent:count', 600, function () {
            return DB::table('calls')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
        });
        $this->info('✓ Cached call stats');
        
        // Cache service list
        Cache::remember('services:all', 86400, function () {
            return DB::table('services')
                ->orderBy('name')
                ->get();
        });
        $this->info('✓ Cached services');
        
        // Cache staff list
        Cache::remember('staff:all', 86400, function () {
            return DB::table('staff')
                ->orderBy('name')
                ->get();
        });
        $this->info('✓ Cached staff');
        
        // Pre-warm common lookups
        $users = DB::table('users')->limit(10)->get();
        foreach ($users as $user) {
            Cache::remember("user:email:{$user->email}", 3600, function () use ($user) {
                return $user;
            });
        }
        $this->info('✓ Pre-warmed user lookups');
        
        $customers = DB::table('customers')->limit(20)->get();
        foreach ($customers as $customer) {
            if ($customer->email) {
                Cache::remember("customer:email:{$customer->email}", 3600, function () use ($customer) {
                    return $customer;
                });
            }
        }
        $this->info('✓ Pre-warmed customer lookups');
    }
}