<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Company;
use App\Services\QueryPerformanceMonitor;
use Illuminate\Console\Command;

class TestQueryPerformance extends Command
{
    protected $signature = 'test:query-performance';

    protected $description = 'Test query performance monitoring';

    protected QueryPerformanceMonitor $monitor;

    public function __construct(QueryPerformanceMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    public function handle()
    {
        $this->info('🔍 Testing Query Performance Monitoring...');

        // Enable monitoring
        $this->monitor->start();

        // 1. Simple query
        $this->info("\n1. Simple Query:");
        Company::first();

        // 2. Complex query with joins
        $this->info("\n2. Complex Query with Joins:");
        Call::with(['company', 'branch', 'customer'])
            ->whereNotNull('appointment_id')
            ->take(10)
            ->get();

        // 3. N+1 Problem Example
        $this->info("\n3. N+1 Problem Example:");
        $companies = Company::limit(10)->get();
        foreach ($companies as $company) {
            $company->calls()->count(); // This creates N+1
        }

        // 4. Duplicate queries
        $this->info("\n4. Duplicate Queries:");
        for ($i = 0; $i < 5; $i++) {
            Company::where('id', 1)->first();
        }

        // Get stats
        $stats = $this->monitor->getStats();

        $this->info("\n📊 Performance Statistics:");
        $this->info("Total Queries: {$stats['total_queries']}");
        $this->info("Total Time: {$stats['total_time_ms']}ms");
        $this->info("Average Time: {$stats['average_time_ms']}ms");
        $this->info("Slow Queries: {$stats['slow_queries']}");

        if (! empty($stats['duplicate_queries'])) {
            $this->warn("\n⚠️  Duplicate Queries Found:");
            foreach ($stats['duplicate_queries'] as $dup) {
                $this->warn("- Executed {$dup['count']} times: " . substr($dup['sql'], 0, 60) . '...');
            }
        }

        if (! empty($stats['n_plus_one_suspects'])) {
            $this->error("\n🚨 Potential N+1 Problems:");
            foreach ($stats['n_plus_one_suspects'] as $suspect) {
                $this->error("- Table '{$suspect['table']}': {$suspect['occurrences']} similar queries");
            }
        }

        // Generate HTML report
        $html = $this->monitor->generateHtmlReport();
        $filename = storage_path('logs/query-performance-' . date('Y-m-d-His') . '.html');
        file_put_contents($filename, $html);

        $this->info("\n📄 HTML Report saved to: " . basename($filename));

        return Command::SUCCESS;
    }
}
