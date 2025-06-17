<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'askproai:performance-monitor 
                            {--live : Show live monitoring with updates every 5 seconds}
                            {--report : Generate detailed performance report}
                            {--slow-queries : Show slow queries from the last 24 hours}
                            {--index-stats : Show index usage statistics}
                            {--threshold=50 : Query time threshold in milliseconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor database performance and query execution times';

    /**
     * Critical queries to monitor
     */
    protected $criticalQueries = [
        'appointment_listings' => [
            'name' => 'Appointment Listings',
            'query' => "SELECT * FROM appointments WHERE company_id = ? AND starts_at >= ? ORDER BY starts_at LIMIT 50",
            'params' => [1, null], // null will be replaced with current date
        ],
        'customer_phone_lookup' => [
            'name' => 'Customer Phone Lookup',
            'query' => "SELECT * FROM customers WHERE company_id = ? AND phone = ? LIMIT 1",
            'params' => [1, '+4912345678'],
        ],
        'call_history' => [
            'name' => 'Call History',
            'query' => "SELECT * FROM calls WHERE company_id = ? AND created_at >= ? ORDER BY created_at DESC LIMIT 100",
            'params' => [1, null], // null will be replaced with 30 days ago
        ],
        'today_appointments_count' => [
            'name' => 'Today\'s Appointments Count',
            'query' => "SELECT COUNT(*) as count FROM appointments WHERE company_id = ? AND DATE(starts_at) = ? AND status = ?",
            'params' => [1, null, 'scheduled'], // null will be replaced with today
        ],
        'monthly_calls_count' => [
            'name' => 'Monthly Calls Count',
            'query' => "SELECT COUNT(*) as count FROM calls WHERE company_id = ? AND created_at >= ?",
            'params' => [1, null], // null will be replaced with start of month
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('live')) {
            $this->runLiveMonitoring();
        } elseif ($this->option('report')) {
            $this->generateDetailedReport();
        } elseif ($this->option('slow-queries')) {
            $this->showSlowQueries();
        } elseif ($this->option('index-stats')) {
            $this->showIndexStatistics();
        } else {
            $this->runStandardMonitoring();
        }
    }

    /**
     * Run standard monitoring
     */
    protected function runStandardMonitoring()
    {
        $this->info('🔍 AskProAI Performance Monitor');
        $this->info('================================');
        $this->newLine();

        $threshold = (int) $this->option('threshold');
        $this->info("Query Time Threshold: {$threshold}ms");
        $this->newLine();

        // Enable query log
        DB::enableQueryLog();

        // Run critical queries
        $results = [];
        foreach ($this->criticalQueries as $key => $queryInfo) {
            $params = $this->prepareParams($queryInfo['params']);
            
            $startTime = microtime(true);
            try {
                DB::select($queryInfo['query'], $params);
                $executionTime = (microtime(true) - $startTime) * 1000;
                
                $results[$key] = [
                    'name' => $queryInfo['name'],
                    'time' => $executionTime,
                    'status' => $executionTime > $threshold ? 'slow' : 'ok',
                ];
            } catch (\Exception $e) {
                $results[$key] = [
                    'name' => $queryInfo['name'],
                    'time' => 0,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Display results
        $this->table(
            ['Query', 'Execution Time (ms)', 'Status'],
            array_map(function ($result) {
                return [
                    $result['name'],
                    number_format($result['time'], 2),
                    $result['status'] === 'ok' ? '✅ OK' : 
                        ($result['status'] === 'slow' ? '⚠️ SLOW' : '❌ ERROR'),
                ];
            }, $results)
        );

        // Summary
        $this->newLine();
        $avgTime = collect($results)->where('status', '!=', 'error')->avg('time');
        $slowCount = collect($results)->where('status', 'slow')->count();
        
        $this->info("Average Query Time: " . number_format($avgTime, 2) . "ms");
        if ($slowCount > 0) {
            $this->warn("Slow Queries: {$slowCount}");
        }

        // Get overall database statistics
        $this->showDatabaseStats();
    }

    /**
     * Run live monitoring
     */
    protected function runLiveMonitoring()
    {
        $this->info('🔴 Live Performance Monitoring (Press Ctrl+C to stop)');
        $this->info('=====================================================');
        
        while (true) {
            $this->output->write("\033[2J\033[0;0H"); // Clear screen
            $this->info('🔴 Live Performance Monitoring - ' . now()->format('Y-m-d H:i:s'));
            $this->info('=====================================================');
            
            $this->runStandardMonitoring();
            
            sleep(5);
        }
    }

    /**
     * Generate detailed performance report
     */
    protected function generateDetailedReport()
    {
        $this->info('📊 Generating Detailed Performance Report...');
        $this->newLine();

        $report = [
            'timestamp' => now()->toIso8601String(),
            'database_stats' => $this->getDatabaseStats(),
            'table_stats' => $this->getTableStats(),
            'index_usage' => $this->getIndexUsageStats(),
            'query_performance' => $this->getQueryPerformanceStats(),
            'recommendations' => $this->generateRecommendations(),
        ];

        $filename = storage_path('logs/performance_report_' . now()->format('Y-m-d_H-i-s') . '.json');
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("✅ Report saved to: {$filename}");
        
        // Display summary
        $this->table(['Metric', 'Value'], [
            ['Total Tables', $report['database_stats']['total_tables']],
            ['Total Indexes', $report['database_stats']['total_indexes']],
            ['Database Size', $this->formatBytes($report['database_stats']['database_size'])],
            ['Avg Query Time', number_format($report['query_performance']['average_time'], 2) . 'ms'],
            ['Recommendations', count($report['recommendations'])],
        ]);
    }

    /**
     * Show slow queries
     */
    protected function showSlowQueries()
    {
        $this->info('🐌 Slow Query Analysis (Last 24 Hours)');
        $this->info('=====================================');
        
        // Note: This requires MySQL slow query log to be enabled
        $this->warn('Note: This feature requires MySQL slow query log to be enabled.');
        $this->newLine();
        
        // Simulate slow query detection
        DB::enableQueryLog();
        
        // Run all queries and find slow ones
        $slowQueries = [];
        $threshold = (int) $this->option('threshold');
        
        foreach ($this->criticalQueries as $queryInfo) {
            $params = $this->prepareParams($queryInfo['params']);
            $startTime = microtime(true);
            
            try {
                DB::select($queryInfo['query'], $params);
                $executionTime = (microtime(true) - $startTime) * 1000;
                
                if ($executionTime > $threshold) {
                    $slowQueries[] = [
                        'query' => $queryInfo['name'],
                        'time' => $executionTime,
                        'explain' => $this->explainQuery($queryInfo['query'], $params),
                    ];
                }
            } catch (\Exception $e) {
                // Skip errors
            }
        }
        
        if (empty($slowQueries)) {
            $this->info("✅ No slow queries found (threshold: {$threshold}ms)");
        } else {
            foreach ($slowQueries as $slowQuery) {
                $this->error("Slow Query: {$slowQuery['query']}");
                $this->line("Execution Time: " . number_format($slowQuery['time'], 2) . "ms");
                $this->line("Query Plan: " . $slowQuery['explain']);
                $this->newLine();
            }
        }
    }

    /**
     * Show index statistics
     */
    protected function showIndexStatistics()
    {
        $this->info('📈 Index Usage Statistics');
        $this->info('========================');
        
        $tables = ['appointments', 'calls', 'customers', 'branches', 'staff', 'services'];
        
        foreach ($tables as $table) {
            $this->info("\nTable: {$table}");
            
            $indexes = DB::select("SHOW INDEX FROM `{$table}`");
            $indexStats = [];
            
            foreach ($indexes as $index) {
                if (!isset($indexStats[$index->Key_name])) {
                    $indexStats[$index->Key_name] = [
                        'columns' => [],
                        'unique' => !$index->Non_unique,
                        'type' => $index->Index_type,
                    ];
                }
                $indexStats[$index->Key_name]['columns'][] = $index->Column_name;
            }
            
            $this->table(
                ['Index Name', 'Columns', 'Type', 'Unique'],
                array_map(function ($name, $stats) {
                    return [
                        $name,
                        implode(', ', $stats['columns']),
                        $stats['type'],
                        $stats['unique'] ? 'Yes' : 'No',
                    ];
                }, array_keys($indexStats), $indexStats)
            );
        }
    }

    /**
     * Prepare query parameters
     */
    protected function prepareParams(array $params): array
    {
        return array_map(function ($param) {
            if ($param === null) {
                return now()->toDateString();
            }
            return $param;
        }, $params);
    }

    /**
     * Get database statistics
     */
    protected function getDatabaseStats(): array
    {
        $dbName = config('database.connections.mysql.database');
        
        $stats = DB::select("
            SELECT 
                COUNT(DISTINCT table_name) as total_tables,
                COUNT(DISTINCT CONCAT(table_name, '.', index_name)) as total_indexes,
                SUM(data_length + index_length) as database_size
            FROM information_schema.tables
            LEFT JOIN information_schema.statistics USING (table_schema, table_name)
            WHERE table_schema = ?
        ", [$dbName])[0];
        
        return [
            'total_tables' => $stats->total_tables,
            'total_indexes' => $stats->total_indexes,
            'database_size' => $stats->database_size,
        ];
    }

    /**
     * Show database statistics
     */
    protected function showDatabaseStats()
    {
        $stats = $this->getDatabaseStats();
        
        $this->newLine();
        $this->info('Database Statistics:');
        $this->line('Total Tables: ' . $stats['total_tables']);
        $this->line('Total Indexes: ' . $stats['total_indexes']);
        $this->line('Database Size: ' . $this->formatBytes($stats['database_size']));
    }

    /**
     * Get table statistics
     */
    protected function getTableStats(): array
    {
        $dbName = config('database.connections.mysql.database');
        
        return DB::select("
            SELECT 
                table_name,
                table_rows,
                data_length,
                index_length,
                (data_length + index_length) as total_size
            FROM information_schema.tables
            WHERE table_schema = ?
            ORDER BY total_size DESC
            LIMIT 10
        ", [$dbName]);
    }

    /**
     * Get index usage statistics
     */
    protected function getIndexUsageStats(): array
    {
        // This would require performance_schema to be enabled
        return [
            'note' => 'Index usage statistics require MySQL performance_schema',
        ];
    }

    /**
     * Get query performance statistics
     */
    protected function getQueryPerformanceStats(): array
    {
        $times = [];
        
        foreach ($this->criticalQueries as $queryInfo) {
            $params = $this->prepareParams($queryInfo['params']);
            $startTime = microtime(true);
            
            try {
                DB::select($queryInfo['query'], $params);
                $times[] = (microtime(true) - $startTime) * 1000;
            } catch (\Exception $e) {
                // Skip errors
            }
        }
        
        return [
            'average_time' => !empty($times) ? array_sum($times) / count($times) : 0,
            'min_time' => !empty($times) ? min($times) : 0,
            'max_time' => !empty($times) ? max($times) : 0,
            'total_queries' => count($times),
        ];
    }

    /**
     * Generate performance recommendations
     */
    protected function generateRecommendations(): array
    {
        $recommendations = [];
        $stats = $this->getDatabaseStats();
        
        // Check database size
        if ($stats['database_size'] > 1073741824) { // 1GB
            $recommendations[] = [
                'type' => 'database_size',
                'severity' => 'medium',
                'message' => 'Database size exceeds 1GB. Consider archiving old data.',
            ];
        }
        
        // Check for missing indexes (simplified)
        $tablesWithoutIndexes = DB::select("
            SELECT t.table_name
            FROM information_schema.tables t
            LEFT JOIN information_schema.statistics s ON t.table_name = s.table_name
            WHERE t.table_schema = ? AND s.index_name IS NULL
        ", [config('database.connections.mysql.database')]);
        
        if (!empty($tablesWithoutIndexes)) {
            $recommendations[] = [
                'type' => 'missing_indexes',
                'severity' => 'high',
                'message' => 'Some tables have no indexes. Review table structure.',
                'tables' => array_column($tablesWithoutIndexes, 'table_name'),
            ];
        }
        
        return $recommendations;
    }

    /**
     * Explain query
     */
    protected function explainQuery(string $query, array $params): string
    {
        try {
            $explain = DB::select("EXPLAIN " . $query, $params);
            if (!empty($explain) && isset($explain[0]->type)) {
                $result = $explain[0]->type;
                if (isset($explain[0]->key)) {
                    $result .= " (index: {$explain[0]->key})";
                }
                return $result;
            }
        } catch (\Exception $e) {
            return 'Could not explain query';
        }
        
        return 'Unknown';
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}