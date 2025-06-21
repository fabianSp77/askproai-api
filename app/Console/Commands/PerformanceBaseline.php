<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Company;
use App\Models\Appointment;
use Carbon\Carbon;

class PerformanceBaseline extends Command
{
    protected $signature = 'performance:baseline {--save : Save results to file}';
    protected $description = 'Measure performance baseline for the system';
    
    private array $results = [];
    
    public function handle()
    {
        $this->info('Starting Performance Baseline Measurement...');
        
        // 1. Database Performance
        $this->measureDatabasePerformance();
        
        // 2. Cache Performance
        $this->measureCachePerformance();
        
        // 3. Query Performance
        $this->measureQueryPerformance();
        
        // 4. API Response Times
        $this->measureApiPerformance();
        
        // 5. Queue Performance
        $this->measureQueuePerformance();
        
        // Display Results
        $this->displayResults();
        
        // Save if requested
        if ($this->option('save')) {
            $this->saveResults();
        }
    }
    
    private function measureDatabasePerformance()
    {
        $this->info("\n📊 Measuring Database Performance...");
        
        // Simple query
        $start = microtime(true);
        DB::select('SELECT 1');
        $this->results['db_ping'] = (microtime(true) - $start) * 1000;
        
        // Count query
        $start = microtime(true);
        $count = Appointment::count();
        $this->results['db_count'] = (microtime(true) - $start) * 1000;
        $this->results['total_appointments'] = $count;
        
        // Complex query with joins
        $start = microtime(true);
        DB::table('appointments as a')
            ->join('customers as c', 'a.customer_id', '=', 'c.id')
            ->join('staff as s', 'a.staff_id', '=', 's.id')
            ->join('branches as b', 'a.branch_id', '=', 'b.id')
            ->whereDate('a.start_time', today())
            ->select('a.id', 'c.name', 's.name', 'b.name')
            ->limit(100)
            ->get();
        $this->results['db_complex_query'] = (microtime(true) - $start) * 1000;
        
        // Connection pool test
        $connections = [];
        $start = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            DB::select('SELECT 1');
        }
        $this->results['db_10_queries'] = (microtime(true) - $start) * 1000;
        
        $this->line("✓ Database ping: {$this->results['db_ping']}ms");
        $this->line("✓ Count query: {$this->results['db_count']}ms ({$count} records)");
        $this->line("✓ Complex query: {$this->results['db_complex_query']}ms");
        $this->line("✓ 10 queries: {$this->results['db_10_queries']}ms");
    }
    
    private function measureCachePerformance()
    {
        $this->info("\n💾 Measuring Cache Performance...");
        
        // Write performance
        $data = str_repeat('x', 1024); // 1KB data
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            Cache::put("perf_test_{$i}", $data, 60);
        }
        $this->results['cache_write_100'] = (microtime(true) - $start) * 1000;
        
        // Read performance
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            Cache::get("perf_test_{$i}");
        }
        $this->results['cache_read_100'] = (microtime(true) - $start) * 1000;
        
        // Cache hit rate
        $info = \Redis::info();
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $this->results['cache_hit_rate'] = ($hits + $misses) > 0 
            ? round(($hits / ($hits + $misses)) * 100, 2) 
            : 0;
        
        // Cleanup
        for ($i = 0; $i < 100; $i++) {
            Cache::forget("perf_test_{$i}");
        }
        
        $this->line("✓ Write 100 keys: {$this->results['cache_write_100']}ms");
        $this->line("✓ Read 100 keys: {$this->results['cache_read_100']}ms");
        $this->line("✓ Cache hit rate: {$this->results['cache_hit_rate']}%");
    }
    
    private function measureQueryPerformance()
    {
        $this->info("\n🔍 Measuring Query Performance...");
        
        $company = Company::first();
        if (!$company) {
            $this->warn("No company found for query tests");
            return;
        }
        
        // Test N+1 scenario (bad)
        $start = microtime(true);
        $appointments = Appointment::where('company_id', $company->id)
            ->whereDate('start_time', today())
            ->limit(10)
            ->get();
        
        foreach ($appointments as $appointment) {
            $appointment->customer->name;
            $appointment->staff->name;
            $appointment->branch->name;
        }
        $this->results['query_n_plus_1'] = (microtime(true) - $start) * 1000;
        
        // Test eager loading (good)
        $start = microtime(true);
        $appointments = Appointment::with(['customer', 'staff', 'branch'])
            ->where('company_id', $company->id)
            ->whereDate('start_time', today())
            ->limit(10)
            ->get();
        
        foreach ($appointments as $appointment) {
            $appointment->customer->name;
            $appointment->staff->name;
            $appointment->branch->name;
        }
        $this->results['query_eager_load'] = (microtime(true) - $start) * 1000;
        
        // Calculate improvement
        $improvement = $this->results['query_n_plus_1'] > 0
            ? round((($this->results['query_n_plus_1'] - $this->results['query_eager_load']) / $this->results['query_n_plus_1']) * 100, 2)
            : 0;
        
        $this->line("✓ N+1 query: {$this->results['query_n_plus_1']}ms");
        $this->line("✓ Eager loading: {$this->results['query_eager_load']}ms");
        $this->line("✓ Improvement: {$improvement}%");
    }
    
    private function measureApiPerformance()
    {
        $this->info("\n🌐 Measuring API Performance...");
        
        $baseUrl = config('app.url');
        
        // Health check endpoint
        try {
            $start = microtime(true);
            $response = Http::timeout(10)->get("{$baseUrl}/api/health");
            $this->results['api_health'] = (microtime(true) - $start) * 1000;
            $this->results['api_health_status'] = $response->status();
        } catch (\Exception $e) {
            $this->results['api_health'] = -1;
            $this->results['api_health_status'] = 0;
        }
        
        $this->line("✓ Health endpoint: {$this->results['api_health']}ms");
    }
    
    private function measureQueuePerformance()
    {
        $this->info("\n📬 Measuring Queue Performance...");
        
        // Check queue sizes
        $queues = ['default', 'webhooks-high-priority', 'webhooks-medium-priority'];
        $totalSize = 0;
        
        foreach ($queues as $queue) {
            $size = \Redis::llen("queues:{$queue}");
            $this->results["queue_{$queue}_size"] = $size;
            $totalSize += $size;
        }
        
        // Failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        $this->results['failed_jobs'] = $failedJobs;
        
        $this->line("✓ Total queue size: {$totalSize}");
        $this->line("✓ Failed jobs: {$failedJobs}");
    }
    
    private function displayResults()
    {
        $this->info("\n📊 PERFORMANCE BASELINE SUMMARY");
        $this->info("================================");
        
        $this->table(
            ['Metric', 'Value', 'Status'],
            [
                ['Database Ping', $this->results['db_ping'] . 'ms', $this->getStatus($this->results['db_ping'], 10, 50)],
                ['Simple Count Query', $this->results['db_count'] . 'ms', $this->getStatus($this->results['db_count'], 50, 200)],
                ['Complex Join Query', $this->results['db_complex_query'] . 'ms', $this->getStatus($this->results['db_complex_query'], 100, 500)],
                ['Cache Write (100 ops)', $this->results['cache_write_100'] . 'ms', $this->getStatus($this->results['cache_write_100'], 50, 200)],
                ['Cache Read (100 ops)', $this->results['cache_read_100'] . 'ms', $this->getStatus($this->results['cache_read_100'], 20, 100)],
                ['Cache Hit Rate', $this->results['cache_hit_rate'] . '%', $this->getStatus(100 - $this->results['cache_hit_rate'], 20, 50)],
                ['N+1 Query Issue', $this->results['query_n_plus_1'] . 'ms', '⚠️'],
                ['Optimized Query', $this->results['query_eager_load'] . 'ms', '✅'],
                ['Failed Jobs', $this->results['failed_jobs'], $this->getStatus($this->results['failed_jobs'], 10, 100)],
            ]
        );
        
        $this->info("\n🎯 Performance Score: " . $this->calculateScore() . "/100");
    }
    
    private function getStatus($value, $good, $bad): string
    {
        if ($value <= $good) return '✅';
        if ($value <= $bad) return '⚠️';
        return '❌';
    }
    
    private function calculateScore(): int
    {
        $score = 100;
        
        // Deduct points for slow responses
        if ($this->results['db_ping'] > 10) $score -= 5;
        if ($this->results['db_ping'] > 50) $score -= 10;
        
        if ($this->results['db_complex_query'] > 100) $score -= 5;
        if ($this->results['db_complex_query'] > 500) $score -= 10;
        
        if ($this->results['cache_hit_rate'] < 80) $score -= 10;
        if ($this->results['cache_hit_rate'] < 50) $score -= 20;
        
        if ($this->results['failed_jobs'] > 10) $score -= 5;
        if ($this->results['failed_jobs'] > 100) $score -= 15;
        
        return max(0, $score);
    }
    
    private function saveResults()
    {
        $filename = storage_path('performance-baseline-' . date('Y-m-d-H-i-s') . '.json');
        
        $data = [
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'results' => $this->results,
            'score' => $this->calculateScore(),
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        $this->info("\n💾 Results saved to: {$filename}");
    }
}