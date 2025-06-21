<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\MCPCacheWarmer;
use App\Services\MCP\MCPQueryOptimizer;
use App\Services\MCP\MCPConnectionPoolManager;

class PerformanceOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:optimize 
                            {--analyze : Analyze slow queries}
                            {--cache : Warm caches}
                            {--pool : Optimize connection pool}
                            {--indexes : Create suggested indexes}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize application performance';

    /**
     * Execute the console command.
     */
    public function handle(
        MCPCacheWarmer $cacheWarmer,
        MCPQueryOptimizer $queryOptimizer,
        MCPConnectionPoolManager $poolManager
    ): int {
        $this->info('🚀 Starting Performance Optimization...');
        
        $dryRun = $this->option('dry-run');
        
        // Analyze queries if requested
        if ($this->option('analyze')) {
            $this->analyzeQueries($queryOptimizer, $dryRun);
        }
        
        // Warm caches if requested
        if ($this->option('cache')) {
            $this->warmCaches($cacheWarmer);
        }
        
        // Optimize connection pool if requested
        if ($this->option('pool')) {
            $this->optimizeConnectionPool($poolManager);
        }
        
        // Create indexes if requested
        if ($this->option('indexes')) {
            $this->createIndexes($queryOptimizer, $dryRun);
        }
        
        // If no specific option, run all optimizations
        if (!$this->option('analyze') && !$this->option('cache') && 
            !$this->option('pool') && !$this->option('indexes')) {
            $this->runAllOptimizations($cacheWarmer, $queryOptimizer, $poolManager, $dryRun);
        }
        
        $this->info('✅ Performance optimization completed!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Analyze slow queries
     */
    protected function analyzeQueries(MCPQueryOptimizer $optimizer, bool $dryRun): void
    {
        $this->info('\n📊 Analyzing queries...');
        
        // Start monitoring
        $optimizer->startMonitoring();
        
        // Wait for some queries to be captured
        $this->info('Monitoring queries for 10 seconds...');
        sleep(10);
        
        // Stop monitoring and get results
        $results = $optimizer->stopMonitoring();
        
        if (empty($results['slow_queries'])) {
            $this->info('No slow queries detected.');
            return;
        }
        
        $this->warn("Found {$results['total_slow_queries']} slow queries:");
        
        // Display slow queries
        $headers = ['Query', 'Time (ms)', 'Suggestions'];
        $rows = [];
        
        foreach ($results['slow_queries'] as $query) {
            $sql = substr($query['sql'], 0, 60) . '...';
            $suggestions = $results['suggestions'][$query['sql']] ?? [];
            $suggestionText = collect($suggestions)->pluck('message')->join("\n");
            
            $rows[] = [$sql, $query['time'], $suggestionText];
        }
        
        $this->table($headers, $rows);
        
        // Get database stats
        $stats = $optimizer->getDatabaseStats();
        
        if (!empty($stats['largest_tables'])) {
            $this->info('\n📈 Largest Tables:');
            $this->table(
                ['Table', 'Rows', 'Size (MB)'],
                collect($stats['largest_tables'])->map(function ($table) {
                    return [
                        $table->table_name,
                        number_format($table->table_rows),
                        $table->size_mb
                    ];
                })->toArray()
            );
        }
    }
    
    /**
     * Warm caches
     */
    protected function warmCaches(MCPCacheWarmer $warmer): void
    {
        $this->info('\n🔥 Warming caches...');
        
        $results = $warmer->warmAll();
        
        $this->info("Warmed {$results['warmed']} cache entries in {$results['duration']} seconds");
        
        if ($results['failed'] > 0) {
            $this->warn("Failed to warm {$results['failed']} entries");
        }
        
        // Display cache statistics
        $stats = $warmer->getStats();
        $this->table(
            ['Cache Type', 'Entries'],
            collect($stats)->map(function ($count, $type) {
                return [str_replace('_', ' ', ucfirst($type)), $count];
            })->toArray()
        );
    }
    
    /**
     * Optimize connection pool
     */
    protected function optimizeConnectionPool(MCPConnectionPoolManager $poolManager): void
    {
        $this->info('\n🔧 Optimizing connection pool...');
        
        $results = $poolManager->optimizePool();
        
        // Display current status
        if (!empty($results['usage'])) {
            $this->info('Current Connection Status:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Active Connections', $results['usage']['current_connections']],
                    ['Max Connections', $results['usage']['max_connections']],
                    ['Usage %', $results['usage']['connection_usage_percent'] . '%'],
                    ['Idle Connections', $results['usage']['idle_connections']],
                    ['Aborted Rate', $results['usage']['aborted_rate'] . '%']
                ]
            );
        }
        
        // Display recommendations
        if (!empty($results['recommendations'])) {
            $this->info('\n💡 Recommendations:');
            foreach ($results['recommendations'] as $rec) {
                $this->warn("- {$rec['reason']}: {$rec['action']} from {$rec['current']} to {$rec['recommended']}");
            }
        }
        
        // Display applied changes
        if (!empty($results['applied'])) {
            $this->info('\n✅ Applied Changes:');
            foreach ($results['applied'] as $change) {
                if ($change['status'] === 'applied') {
                    $this->info("- {$change['action']}: Applied successfully");
                } elseif ($change['status'] === 'manual_required') {
                    $this->warn("- {$change['action']}: Manual intervention required");
                    if (isset($change['command'])) {
                        $this->line("  Run: {$change['command']}");
                    }
                } else {
                    $this->error("- {$change['action']}: Failed - {$change['error']}");
                }
            }
        }
    }
    
    /**
     * Create suggested indexes
     */
    protected function createIndexes(MCPQueryOptimizer $optimizer, bool $dryRun): void
    {
        $this->info('\n🗂️ Creating suggested indexes...');
        
        $created = $optimizer->createSuggestedIndexes($dryRun);
        
        if (empty($created)) {
            $this->info('No indexes to create.');
            return;
        }
        
        $headers = ['Index SQL', 'Status'];
        $rows = collect($created)->map(function ($index) {
            return [
                substr($index['sql'], 0, 80) . '...',
                $index['status'] === 'created' ? '✅ Created' : 
                ($index['status'] === 'dry_run' ? '🔍 Would create' : '❌ Failed')
            ];
        })->toArray();
        
        $this->table($headers, $rows);
        
        if ($dryRun) {
            $this->warn('\nThis was a dry run. Use --no-dry-run to actually create indexes.');
        }
    }
    
    /**
     * Run all optimizations
     */
    protected function runAllOptimizations(
        MCPCacheWarmer $cacheWarmer,
        MCPQueryOptimizer $queryOptimizer,
        MCPConnectionPoolManager $poolManager,
        bool $dryRun
    ): void {
        $this->info('Running full performance optimization...');
        
        // 1. Warm caches
        $this->warmCaches($cacheWarmer);
        
        // 2. Optimize connection pool
        $this->optimizeConnectionPool($poolManager);
        
        // 3. Analyze queries (skip monitoring in full run)
        $this->info('\n📊 Getting database statistics...');
        $stats = $queryOptimizer->getDatabaseStats();
        if (!empty($stats['largest_tables'])) {
            $this->table(
                ['Table', 'Rows', 'Size (MB)'],
                collect($stats['largest_tables'])->take(5)->map(function ($table) {
                    return [
                        $table->table_name,
                        number_format($table->table_rows),
                        $table->size_mb
                    ];
                })->toArray()
            );
        }
        
        $this->info('\n💡 Tips for better performance:');
        $this->line('- Enable slow query log to identify problematic queries');
        $this->line('- Use "php artisan performance:optimize --analyze" to monitor queries');
        $this->line('- Run this command regularly to keep caches warm');
        $this->line('- Monitor connection pool usage during peak times');
    }
}