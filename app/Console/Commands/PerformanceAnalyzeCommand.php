<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueryOptimizer;
use App\Services\QueryMonitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PerformanceAnalyzeCommand extends Command
{
    protected $signature = 'performance:analyze 
                            {--table= : Analyze specific table}
                            {--queries : Analyze recent queries}
                            {--cache : Analyze cache performance}
                            {--report : Generate detailed report}';

    protected $description = 'Analyze application performance metrics and provide optimization suggestions';

    protected QueryOptimizer $optimizer;
    protected QueryMonitor $monitor;

    public function __construct(QueryOptimizer $optimizer, QueryMonitor $monitor)
    {
        parent::__construct();
        $this->optimizer = $optimizer;
        $this->monitor = $monitor;
    }

    public function handle()
    {
        $this->info('🚀 Running Performance Analysis...');
        $startTime = microtime(true);

        $results = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'metrics' => [],
            'recommendations' => []
        ];

        // Analyze database performance
        $this->info('Analyzing database performance...');
        $dbMetrics = $this->analyzeDatabasePerformance();
        $results['metrics']['database'] = $dbMetrics;

        // Analyze query performance
        if ($this->option('queries')) {
            $this->info('Analyzing recent queries...');
            $queryMetrics = $this->analyzeQueryPerformance();
            $results['metrics']['queries'] = $queryMetrics;
        }

        // Analyze cache performance
        if ($this->option('cache')) {
            $this->info('Analyzing cache performance...');
            $cacheMetrics = $this->analyzeCachePerformance();
            $results['metrics']['cache'] = $cacheMetrics;
        }

        // Analyze specific table
        if ($table = $this->option('table')) {
            $this->info("Analyzing table: $table...");
            $tableMetrics = $this->analyzeTable($table);
            $results['metrics']['table_analysis'] = $tableMetrics;
        }

        // Generate recommendations
        $results['recommendations'] = $this->generateRecommendations($results['metrics']);
        
        // Calculate performance score
        $results['performance_score'] = $this->calculatePerformanceScore($results['metrics']);
        $results['duration'] = round(microtime(true) - $startTime, 2) . 's';

        // Display results
        $this->displayResults($results);

        // Generate report if requested
        if ($this->option('report')) {
            $this->generateReport($results);
        }

        return 0;
    }

    private function analyzeDatabasePerformance(): array
    {
        $metrics = [];

        try {
            // Get database size
            $dbSize = DB::select("
                SELECT 
                    table_schema AS 'database',
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
                FROM information_schema.tables 
                WHERE table_schema = ?
                GROUP BY table_schema
            ", [config('database.connections.mysql.database')]);

            $metrics['database_size'] = $dbSize[0]->size_mb ?? 0;

            // Get table statistics
            $tableStats = DB::select("
                SELECT 
                    table_name,
                    table_rows,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    ROUND((index_length / (data_length + index_length)) * 100, 2) AS index_ratio
                FROM information_schema.tables
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ", [config('database.connections.mysql.database')]);

            $metrics['largest_tables'] = $tableStats;

            // Check for missing indexes
            $metrics['missing_indexes'] = $this->checkMissingIndexes();

            // Check connection pool usage
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $metrics['active_connections'] = $connections[0]->Value ?? 0;

        } catch (\Exception $e) {
            $metrics['error'] = 'Could not analyze database: ' . $e->getMessage();
        }

        return $metrics;
    }

    private function analyzeQueryPerformance(): array
    {
        $metrics = [];

        try {
            // Get slow queries from monitor
            $slowQueries = $this->monitor->getSlowQueries(10);
            $metrics['slow_queries'] = $slowQueries;

            // Get query statistics
            $stats = $this->monitor->getQueryStatistics();
            $metrics['query_stats'] = $stats;

            // Check for N+1 queries
            $n1Queries = $this->monitor->detectNPlusOneQueries();
            $metrics['n_plus_one_queries'] = count($n1Queries);

        } catch (\Exception $e) {
            $metrics['error'] = 'Could not analyze queries: ' . $e->getMessage();
        }

        return $metrics;
    }

    private function analyzeCachePerformance(): array
    {
        $metrics = [];

        try {
            $cacheDriver = config('cache.default');
            $metrics['driver'] = $cacheDriver;

            if ($cacheDriver === 'redis') {
                // Get Redis cache statistics
                $redis = Cache::getStore()->getRedis();
                $cacheInfo = $redis->info();
                
                $metrics['cache_hits'] = $cacheInfo['keyspace_hits'] ?? 0;
                $metrics['cache_misses'] = $cacheInfo['keyspace_misses'] ?? 0;
                $metrics['hit_rate'] = $metrics['cache_hits'] > 0 
                    ? round(($metrics['cache_hits'] / ($metrics['cache_hits'] + $metrics['cache_misses'])) * 100, 2) 
                    : 0;
                
                $metrics['memory_used'] = $cacheInfo['used_memory_human'] ?? 'Unknown';
                $metrics['total_keys'] = 0;
                
                // Count keys by database
                foreach ($cacheInfo as $key => $value) {
                    if (strpos($key, 'db') === 0 && is_string($value)) {
                        preg_match('/keys=(\d+)/', $value, $matches);
                        if (isset($matches[1])) {
                            $metrics['total_keys'] += (int)$matches[1];
                        }
                    }
                }
            } elseif ($cacheDriver === 'database') {
                // Get database cache statistics
                $cacheTable = config('cache.stores.database.table', 'cache');
                
                $totalEntries = DB::table($cacheTable)->count();
                $expiredEntries = DB::table($cacheTable)
                    ->where('expiration', '<', time())
                    ->count();
                $activeEntries = $totalEntries - $expiredEntries;
                
                $metrics['total_entries'] = $totalEntries;
                $metrics['active_entries'] = $activeEntries;
                $metrics['expired_entries'] = $expiredEntries;
                $metrics['hit_rate'] = 'N/A (Database cache does not track hit rates)';
                
                // Get cache table size
                $tableInfo = DB::select("
                    SELECT 
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                    FROM information_schema.tables
                    WHERE table_schema = ? AND table_name = ?
                ", [config('database.connections.mysql.database'), $cacheTable]);
                
                $metrics['table_size_mb'] = $tableInfo[0]->size_mb ?? 0;
            } else {
                $metrics['info'] = "Cache driver '$cacheDriver' does not provide statistics";
            }

        } catch (\Exception $e) {
            $metrics['error'] = 'Could not analyze cache: ' . $e->getMessage();
        }

        return $metrics;
    }

    private function analyzeTable(string $table): array
    {
        $metrics = [];

        try {
            // Get table structure
            $columns = DB::select("SHOW COLUMNS FROM $table");
            $metrics['columns'] = count($columns);

            // Get indexes
            $indexes = DB::select("SHOW INDEXES FROM $table");
            $metrics['indexes'] = count(array_unique(array_column($indexes, 'Key_name')));

            // Get table statistics
            $stats = DB::select("
                SELECT 
                    table_rows,
                    avg_row_length,
                    data_length,
                    index_length
                FROM information_schema.tables
                WHERE table_schema = ? AND table_name = ?
            ", [config('database.connections.mysql.database'), $table]);

            if (!empty($stats)) {
                $metrics['row_count'] = $stats[0]->table_rows;
                $metrics['avg_row_length'] = $stats[0]->avg_row_length;
                $metrics['data_size_mb'] = round($stats[0]->data_length / 1024 / 1024, 2);
                $metrics['index_size_mb'] = round($stats[0]->index_length / 1024 / 1024, 2);
            }

            // Check for optimization opportunities
            $metrics['recommendations'] = $this->optimizer->analyzeTable($table);

        } catch (\Exception $e) {
            $metrics['error'] = 'Could not analyze table: ' . $e->getMessage();
        }

        return $metrics;
    }

    private function checkMissingIndexes(): array
    {
        $missingIndexes = [];

        // Common queries that should have indexes
        $checkQueries = [
            ['table' => 'calls', 'column' => 'company_id'],
            ['table' => 'calls', 'column' => 'customer_id'],
            ['table' => 'appointments', 'column' => 'company_id'],
            ['table' => 'appointments', 'column' => 'staff_id'],
            ['table' => 'appointments', 'column' => 'start_at'],
            ['table' => 'customers', 'column' => 'company_id'],
            ['table' => 'customers', 'column' => 'phone_number'],
        ];

        foreach ($checkQueries as $check) {
            try {
                $indexes = DB::select("
                    SHOW INDEXES FROM {$check['table']} 
                    WHERE Column_name = ?
                ", [$check['column']]);

                if (empty($indexes)) {
                    $missingIndexes[] = [
                        'table' => $check['table'],
                        'column' => $check['column'],
                        'impact' => 'high'
                    ];
                }
            } catch (\Exception $e) {
                // Table might not exist
            }
        }

        return $missingIndexes;
    }

    private function generateRecommendations(array $metrics): array
    {
        $recommendations = [];

        // Database recommendations
        if (isset($metrics['database']['missing_indexes']) && !empty($metrics['database']['missing_indexes'])) {
            foreach ($metrics['database']['missing_indexes'] as $missing) {
                $recommendations[] = [
                    'type' => 'index',
                    'priority' => 'high',
                    'message' => "Add index on {$missing['table']}.{$missing['column']} for better query performance"
                ];
            }
        }

        // Cache recommendations
        if (isset($metrics['cache']['hit_rate']) && $metrics['cache']['hit_rate'] < 80) {
            $recommendations[] = [
                'type' => 'cache',
                'priority' => 'medium',
                'message' => "Cache hit rate is low ({$metrics['cache']['hit_rate']}%). Consider caching more data or increasing cache TTL"
            ];
        }

        // Query recommendations
        if (isset($metrics['queries']['n_plus_one_queries']) && $metrics['queries']['n_plus_one_queries'] > 0) {
            $recommendations[] = [
                'type' => 'query',
                'priority' => 'high',
                'message' => "Detected {$metrics['queries']['n_plus_one_queries']} N+1 query patterns. Use eager loading to optimize"
            ];
        }

        // Table size recommendations
        if (isset($metrics['database']['largest_tables'])) {
            foreach ($metrics['database']['largest_tables'] as $table) {
                if ($table->size_mb > 1000) {
                    $recommendations[] = [
                        'type' => 'table',
                        'priority' => 'medium',
                        'message' => "Table {$table->table_name} is large ({$table->size_mb}MB). Consider partitioning or archiving old data"
                    ];
                }
            }
        }

        return $recommendations;
    }

    private function calculatePerformanceScore(array $metrics): int
    {
        $score = 100;

        // Deduct points for issues
        if (isset($metrics['database']['missing_indexes'])) {
            $score -= count($metrics['database']['missing_indexes']) * 5;
        }

        if (isset($metrics['cache']['hit_rate']) && $metrics['cache']['hit_rate'] < 80) {
            $score -= (80 - $metrics['cache']['hit_rate']) / 2;
        }

        if (isset($metrics['queries']['n_plus_one_queries'])) {
            $score -= $metrics['queries']['n_plus_one_queries'] * 3;
        }

        return max(0, $score);
    }

    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('                 PERFORMANCE ANALYSIS RESULTS                    ');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Database metrics
        if (isset($results['metrics']['database'])) {
            $this->info('DATABASE PERFORMANCE:');
            $this->line("  Database Size: {$results['metrics']['database']['database_size']}MB");
            $this->line("  Active Connections: {$results['metrics']['database']['active_connections']}");
            
            if (isset($results['metrics']['database']['largest_tables'])) {
                $this->line("  Largest Tables:");
                foreach ($results['metrics']['database']['largest_tables'] as $table) {
                    $this->line("    - {$table->table_name}: {$table->size_mb}MB ({$table->table_rows} rows)");
                }
            }
            $this->newLine();
        }

        // Cache metrics
        if (isset($results['metrics']['cache'])) {
            $this->info('CACHE PERFORMANCE:');
            $this->line("  Driver: {$results['metrics']['cache']['driver']}");
            
            if ($results['metrics']['cache']['driver'] === 'redis') {
                $this->line("  Hit Rate: {$results['metrics']['cache']['hit_rate']}%");
                $this->line("  Memory Used: {$results['metrics']['cache']['memory_used']}");
                $this->line("  Total Keys: {$results['metrics']['cache']['total_keys']}");
            } elseif ($results['metrics']['cache']['driver'] === 'database') {
                $this->line("  Total Entries: {$results['metrics']['cache']['total_entries']}");
                $this->line("  Active Entries: {$results['metrics']['cache']['active_entries']}");
                $this->line("  Expired Entries: {$results['metrics']['cache']['expired_entries']}");
                $this->line("  Table Size: {$results['metrics']['cache']['table_size_mb']}MB");
            }
            $this->newLine();
        }

        // Recommendations
        if (!empty($results['recommendations'])) {
            $this->info('RECOMMENDATIONS:');
            foreach ($results['recommendations'] as $rec) {
                $icon = $rec['priority'] === 'high' ? '❗' : '💡';
                $color = $rec['priority'] === 'high' ? 'error' : 'comment';
                $this->$color("  $icon [{$rec['priority']}] {$rec['message']}");
            }
            $this->newLine();
        }

        // Performance score
        $scoreColor = $results['performance_score'] >= 80 ? 'info' 
            : ($results['performance_score'] >= 60 ? 'comment' : 'error');
        
        $this->info('SUMMARY:');
        $this->$scoreColor("  Performance Score: {$results['performance_score']}/100");
        $this->line("  Analysis Duration: {$results['duration']}");
    }

    private function generateReport(array $results): void
    {
        $filename = 'performance_analysis_' . Carbon::now()->format('Y-m-d_His') . '.json';
        $path = storage_path('app/performance_reports/' . $filename);
        
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, json_encode($results, JSON_PRETTY_PRINT));
        
        $this->newLine();
        $this->info("📊 Detailed report saved to: $path");
    }
}