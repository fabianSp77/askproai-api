<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueryMonitor;
use App\Services\QueryOptimizer;
use Illuminate\Support\Facades\DB;

class AnalyzeQueryPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'query:analyze {--table= : Analyze specific table} {--clear : Clear statistics after analysis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database query performance and provide optimization suggestions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $queryMonitor = new QueryMonitor();
        $queryOptimizer = new QueryOptimizer();

        $this->info('Analyzing Query Performance...');
        $this->newLine();

        // Get and display query statistics
        $stats = $queryMonitor->getStats();
        
        if (empty($stats)) {
            $this->warn('No query statistics available. Enable query monitoring first with: php artisan query:monitor');
            return Command::FAILURE;
        }

        // Display overall statistics
        $this->displayOverallStats($stats);

        // Analyze patterns
        $this->info('Analyzing Query Patterns...');
        $patterns = $queryMonitor->analyzePatterns();
        
        $this->displayNPlusOneQueries($patterns['n_plus_one']);
        $this->displayMissingIndexes($patterns['missing_indexes']);
        $this->displayDuplicateQueries($patterns['duplicate_queries']);
        $this->displayExpensiveQueries($patterns['expensive_queries']);

        // Table-specific analysis
        if ($table = $this->option('table')) {
            $this->analyzeTable($table, $queryOptimizer);
        }

        // Display recent slow queries
        $this->displaySlowQueries($queryMonitor);

        // Display database statistics
        $this->displayDatabaseStats($queryOptimizer);

        // Clear statistics if requested
        if ($this->option('clear')) {
            $queryMonitor->clearStats();
            $this->info('Query statistics cleared.');
        }

        return Command::SUCCESS;
    }

    /**
     * Display overall statistics
     */
    private function displayOverallStats(array $stats): void
    {
        $this->info('Overall Query Statistics:');
        $headers = ['Table', 'Operation', 'Count', 'Avg Time (ms)', 'Max Time (ms)', 'Total Time (ms)'];
        $rows = [];

        foreach ($stats as $table => $operations) {
            foreach ($operations as $operation => $data) {
                $rows[] = [
                    $table,
                    $operation,
                    number_format($data['count']),
                    number_format($data['avg_time'], 2),
                    number_format($data['max_time'], 2),
                    number_format($data['total_time'], 2)
                ];
            }
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display N+1 query patterns
     */
    private function displayNPlusOneQueries(array $patterns): void
    {
        if (empty($patterns)) {
            return;
        }

        $this->error('N+1 Query Patterns Detected:');
        $headers = ['Pattern', 'Count', 'Total Time (ms)', 'Suggestion'];
        $rows = [];

        foreach ($patterns as $pattern) {
            $rows[] = [
                substr($pattern['pattern'], 0, 60) . '...',
                $pattern['count'],
                number_format($pattern['total_time'], 2),
                $pattern['suggestion']
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display missing indexes
     */
    private function displayMissingIndexes(array $indexes): void
    {
        if (empty($indexes)) {
            return;
        }

        $this->warn('Potential Missing Indexes:');
        $headers = ['Table', 'Column', 'Query Time (ms)', 'Suggestion'];
        $rows = [];

        foreach ($indexes as $index) {
            $rows[] = [
                $index['table'],
                $index['column'],
                number_format($index['query_time'], 2),
                $index['suggestion']
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display duplicate queries
     */
    private function displayDuplicateQueries(array $duplicates): void
    {
        if (empty($duplicates)) {
            return;
        }

        $this->warn('Duplicate Queries Detected:');
        $headers = ['Query', 'Count', 'Total Time (ms)', 'Suggestion'];
        $rows = [];

        foreach ($duplicates as $duplicate) {
            $rows[] = [
                substr($duplicate['sql'], 0, 60) . '...',
                $duplicate['count'],
                number_format($duplicate['total_time'], 2),
                $duplicate['suggestion']
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display expensive queries
     */
    private function displayExpensiveQueries(array $queries): void
    {
        if (empty($queries)) {
            return;
        }

        $this->error('Expensive Queries:');
        $headers = ['Table', 'Operation', 'Count', 'Avg Time (ms)', 'Max Time (ms)', 'Suggestion'];
        $rows = [];

        foreach ($queries as $query) {
            $rows[] = [
                $query['table'],
                $query['operation'],
                number_format($query['count']),
                number_format($query['avg_time'], 2),
                number_format($query['max_time'], 2),
                $query['suggestion']
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display slow queries
     */
    private function displaySlowQueries(QueryMonitor $monitor): void
    {
        $slowQueries = $monitor->getSlowQueries(10);
        
        if (empty($slowQueries)) {
            return;
        }

        $this->error('Recent Slow Queries:');
        $headers = ['Time (ms)', 'Query', 'Location', 'Date'];
        $rows = [];

        foreach ($slowQueries as $query) {
            $location = 'Unknown';
            if (!empty($query->backtrace)) {
                $frame = $query->backtrace[0] ?? [];
                $location = ($frame['file'] ?? 'Unknown') . ':' . ($frame['line'] ?? '?');
            }

            $rows[] = [
                number_format($query->time, 2),
                substr($query->sql, 0, 50) . '...',
                $location,
                $query->created_at
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * Display database statistics
     */
    private function displayDatabaseStats(QueryOptimizer $optimizer): void
    {
        $stats = $optimizer->getQueryStats();
        
        if (empty($stats)) {
            return;
        }

        $this->info('Database Performance Metrics:');
        $this->line('Slow Queries: ' . ($stats['slow_queries'] ?? 0));
        $this->line('Cache Hits: ' . ($stats['cache_hits'] ?? 0));
        $this->line('Cache Misses: ' . ($stats['cache_misses'] ?? 0));
        
        if (isset($stats['cache_hits']) && isset($stats['cache_misses'])) {
            $total = $stats['cache_hits'] + $stats['cache_misses'];
            $hitRate = $total > 0 ? ($stats['cache_hits'] / $total) * 100 : 0;
            $this->line('Cache Hit Rate: ' . number_format($hitRate, 2) . '%');
        }
        
        $this->line('Table Lock Waits: ' . ($stats['lock_waits'] ?? 0));
        $this->newLine();
    }

    /**
     * Analyze specific table
     */
    private function analyzeTable(string $table, QueryOptimizer $optimizer): void
    {
        $this->info("Analyzing table: {$table}");
        
        // Get table information
        try {
            // Table size
            $size = DB::select("
                SELECT 
                    table_rows AS row_count,
                    ROUND(data_length/1024/1024, 2) AS data_size_mb,
                    ROUND(index_length/1024/1024, 2) AS index_size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$table]);

            if (!empty($size)) {
                $this->line('Row Count: ' . number_format($size[0]->row_count));
                $this->line('Data Size: ' . $size[0]->data_size_mb . ' MB');
                $this->line('Index Size: ' . $size[0]->index_size_mb . ' MB');
            }

            // Indexes
            $indexes = DB::select("SHOW INDEXES FROM {$table}");
            
            $this->newLine();
            $this->info('Indexes:');
            $headers = ['Key Name', 'Column', 'Unique', 'Type'];
            $rows = [];

            foreach ($indexes as $index) {
                $rows[] = [
                    $index->Key_name,
                    $index->Column_name,
                    $index->Non_unique ? 'No' : 'Yes',
                    $index->Index_type
                ];
            }

            $this->table($headers, $rows);
            
            // Analyze sample query
            $this->newLine();
            $this->info('Sample Query Analysis:');
            
            $sampleQuery = DB::table($table)->limit(1);
            $analysis = $optimizer->analyzeQuery($sampleQuery);
            
            if (!empty($analysis['warnings'])) {
                $this->warn('Warnings:');
                foreach ($analysis['warnings'] as $warning) {
                    $this->line('  - ' . $warning);
                }
            }
            
            if (!empty($analysis['suggestions'])) {
                $this->info('Suggestions:');
                foreach ($analysis['suggestions'] as $suggestion) {
                    $this->line('  - ' . $suggestion);
                }
            }
            
        } catch (\Exception $e) {
            $this->error('Error analyzing table: ' . $e->getMessage());
        }
        
        $this->newLine();
    }
}