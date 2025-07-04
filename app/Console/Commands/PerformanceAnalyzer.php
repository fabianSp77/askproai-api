<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PerformanceAnalyzer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:analyze 
                            {--table= : Analyze specific table}
                            {--query : Analyze slow queries}
                            {--index : Suggest index improvements}
                            {--cache : Analyze cache usage}
                            {--fix : Apply automatic fixes where possible}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analysiert Performance-Probleme und schlägt Optimierungen vor';

    protected array $findings = [];
    protected array $recommendations = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 AskProAI Performance Analyzer');
        $this->line('================================');

        if ($this->option('query')) {
            $this->analyzeSlowQueries();
        }

        if ($this->option('index')) {
            $this->analyzeIndexes();
        }

        if ($this->option('cache')) {
            $this->analyzeCacheUsage();
        }

        if ($table = $this->option('table')) {
            $this->analyzeTable($table);
        }

        // If no specific option, run all analyses
        if (!$this->option('query') && !$this->option('index') && !$this->option('cache') && !$this->option('table')) {
            $this->runCompleteAnalysis();
        }

        // Display results
        $this->displayResults();

        // Apply fixes if requested
        if ($this->option('fix') && count($this->recommendations) > 0) {
            $this->applyFixes();
        }

        return 0;
    }

    /**
     * Run complete performance analysis
     */
    protected function runCompleteAnalysis(): void
    {
        $this->info("\n📊 Running complete performance analysis...");
        
        $this->analyzeSlowQueries();
        $this->analyzeIndexes();
        $this->analyzeCacheUsage();
        $this->analyzeNPlusOneQueries();
        $this->analyzeTableSizes();
        $this->analyzeConnectionPool();
    }

    /**
     * Analyze slow queries
     */
    protected function analyzeSlowQueries(): void
    {
        $this->info("\n🔍 Analyzing slow queries...");

        // Enable query log temporarily
        DB::enableQueryLog();

        // Run some common queries to analyze
        $testQueries = [
            'appointments_today' => function() {
                return Appointment::with(['customer', 'staff', 'service'])
                    ->whereDate('starts_at', today())
                    ->get();
            },
            'recent_calls' => function() {
                return Call::with(['company', 'branch', 'phoneNumber'])
                    ->where('created_at', '>=', now()->subDays(7))
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get();
            },
            'active_companies' => function() {
                return Company::withCount(['appointments', 'calls', 'branches'])
                    ->where('subscription_status', 'active')
                    ->get();
            },
        ];

        foreach ($testQueries as $name => $query) {
            $start = microtime(true);
            $query();
            $duration = round((microtime(true) - $start) * 1000, 2);
            
            if ($duration > 100) {
                $this->findings[] = [
                    'type' => 'slow_query',
                    'name' => $name,
                    'duration' => $duration,
                    'severity' => $duration > 500 ? 'high' : 'medium',
                ];
            }
        }

        // Analyze query log
        $queries = collect(DB::getQueryLog());
        $slowQueries = $queries->filter(function ($query) {
            return $query['time'] > 50; // 50ms threshold
        });

        if ($slowQueries->isNotEmpty()) {
            foreach ($slowQueries as $query) {
                $this->findings[] = [
                    'type' => 'slow_query',
                    'sql' => $query['query'],
                    'time' => $query['time'],
                    'bindings' => $query['bindings'],
                ];
            }

            $this->recommendations[] = [
                'type' => 'query_optimization',
                'message' => "Found {$slowQueries->count()} slow queries",
                'action' => 'Review and optimize these queries',
            ];
        }

        DB::disableQueryLog();
    }

    /**
     * Analyze database indexes
     */
    protected function analyzeIndexes(): void
    {
        $this->info("\n🔍 Analyzing indexes...");

        $tables = [
            'appointments' => ['company_id', 'branch_id', 'staff_id', 'customer_id', 'starts_at', 'status'],
            'calls' => ['company_id', 'branch_id', 'phone_number_id', 'created_at', 'status'],
            'customers' => ['company_id', 'email', 'phone'],
            'staff' => ['company_id', 'branch_id', 'email'],
            'services' => ['company_id', 'branch_id', 'active'],
            'phone_numbers' => ['company_id', 'number', 'is_active'],
        ];

        foreach ($tables as $table => $recommendedIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            // Get existing indexes
            $existingIndexes = collect(DB::select("SHOW INDEX FROM $table"))
                ->pluck('Column_name')
                ->unique()
                ->toArray();

            // Find missing indexes
            $missingIndexes = array_diff($recommendedIndexes, $existingIndexes);

            if (!empty($missingIndexes)) {
                $this->findings[] = [
                    'type' => 'missing_index',
                    'table' => $table,
                    'columns' => $missingIndexes,
                ];

                foreach ($missingIndexes as $column) {
                    $this->recommendations[] = [
                        'type' => 'add_index',
                        'table' => $table,
                        'column' => $column,
                        'sql' => "ALTER TABLE `$table` ADD INDEX `idx_{$table}_{$column}` (`$column`);",
                    ];
                }
            }

            // Check for unused indexes (indexes not in recommended list)
            $potentiallyUnused = array_diff($existingIndexes, $recommendedIndexes, ['id', 'PRIMARY']);
            if (!empty($potentiallyUnused)) {
                $this->findings[] = [
                    'type' => 'unused_index',
                    'table' => $table,
                    'columns' => $potentiallyUnused,
                    'severity' => 'low',
                ];
            }
        }

        // Check for composite indexes opportunities
        $this->checkCompositeIndexes();
    }

    /**
     * Check for composite index opportunities
     */
    protected function checkCompositeIndexes(): void
    {
        $compositeRecommendations = [
            'appointments' => [
                ['company_id', 'starts_at'],
                ['staff_id', 'starts_at', 'status'],
            ],
            'calls' => [
                ['company_id', 'created_at'],
                ['phone_number_id', 'status'],
            ],
        ];

        foreach ($compositeRecommendations as $table => $indexes) {
            foreach ($indexes as $columns) {
                $indexName = 'idx_' . $table . '_' . implode('_', $columns);
                $columnList = implode(', ', array_map(function($col) { return "`$col`"; }, $columns));
                
                // Check if composite index exists
                $exists = DB::select("
                    SELECT COUNT(*) as count 
                    FROM information_schema.statistics 
                    WHERE table_schema = DATABASE() 
                    AND table_name = ? 
                    AND index_name = ?
                ", [$table, $indexName]);

                if ($exists[0]->count == 0) {
                    $this->recommendations[] = [
                        'type' => 'add_composite_index',
                        'table' => $table,
                        'columns' => $columns,
                        'sql' => "ALTER TABLE `$table` ADD INDEX `$indexName` ($columnList);",
                    ];
                }
            }
        }
    }

    /**
     * Analyze cache usage
     */
    protected function analyzeCacheUsage(): void
    {
        $this->info("\n🔍 Analyzing cache usage...");

        // Check Redis memory usage
        try {
            $info = \Illuminate\Support\Facades\Redis::info();
            $usedMemory = $info['used_memory'] ?? 0;
            $usedMemoryHuman = $info['used_memory_human'] ?? 'N/A';
            $evictedKeys = $info['evicted_keys'] ?? 0;

            if ($evictedKeys > 0) {
                $this->findings[] = [
                    'type' => 'cache_eviction',
                    'evicted_keys' => $evictedKeys,
                    'severity' => 'high',
                ];

                $this->recommendations[] = [
                    'type' => 'increase_cache_memory',
                    'message' => "Redis has evicted $evictedKeys keys. Consider increasing memory limit.",
                ];
            }

            // Check cache hit rate (if we're tracking it)
            $stats = Cache::get('cache_statistics', ['hits' => 0, 'misses' => 0]);
            if ($stats['hits'] + $stats['misses'] > 0) {
                $hitRate = round($stats['hits'] / ($stats['hits'] + $stats['misses']) * 100, 2);
                
                if ($hitRate < 80) {
                    $this->findings[] = [
                        'type' => 'low_cache_hit_rate',
                        'hit_rate' => $hitRate,
                        'severity' => 'medium',
                    ];

                    $this->recommendations[] = [
                        'type' => 'improve_cache_strategy',
                        'message' => "Cache hit rate is only {$hitRate}%. Review caching strategy.",
                    ];
                }
            }

        } catch (\Exception $e) {
            $this->error("Failed to analyze Redis: " . $e->getMessage());
        }

        // Suggest caching for expensive operations
        $this->suggestCachingOpportunities();
    }

    /**
     * Suggest caching opportunities
     */
    protected function suggestCachingOpportunities(): void
    {
        $cachingOpportunities = [
            'company_settings' => [
                'query' => 'Company::find($id)->settings',
                'ttl' => 3600,
                'key' => 'company:{id}:settings',
            ],
            'branch_working_hours' => [
                'query' => 'Branch::find($id)->working_hours',
                'ttl' => 86400,
                'key' => 'branch:{id}:working_hours',
            ],
            'service_list' => [
                'query' => 'Service::where("company_id", $id)->active()->get()',
                'ttl' => 1800,
                'key' => 'company:{id}:services:active',
            ],
            'staff_availability' => [
                'query' => 'Staff availability calculations',
                'ttl' => 300,
                'key' => 'staff:{id}:availability:{date}',
            ],
        ];

        foreach ($cachingOpportunities as $name => $config) {
            $this->recommendations[] = [
                'type' => 'add_caching',
                'name' => $name,
                'config' => $config,
                'message' => "Consider caching $name for {$config['ttl']} seconds",
            ];
        }
    }

    /**
     * Analyze N+1 queries
     */
    protected function analyzeNPlusOneQueries(): void
    {
        $this->info("\n🔍 Analyzing N+1 queries...");

        $models = [
            'Appointment' => ['customer', 'staff', 'service', 'branch'],
            'Call' => ['company', 'branch', 'phoneNumber', 'appointments'],
            'Company' => ['branches', 'phoneNumbers', 'staff'],
            'Branch' => ['company', 'staff', 'services'],
        ];

        foreach ($models as $model => $relations) {
            $modelClass = "App\\Models\\$model";
            
            if (!class_exists($modelClass)) {
                continue;
            }

            // Check for missing eager loading
            $this->recommendations[] = [
                'type' => 'eager_loading',
                'model' => $model,
                'relations' => $relations,
                'message' => "Always use with() for $model: ->with(" . json_encode($relations) . ")",
            ];
        }
    }

    /**
     * Analyze table sizes
     */
    protected function analyzeTableSizes(): void
    {
        $this->info("\n🔍 Analyzing table sizes...");

        $tables = DB::select("
            SELECT 
                table_name,
                table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                ROUND((data_length / 1024 / 1024), 2) AS data_mb,
                ROUND((index_length / 1024 / 1024), 2) AS index_mb
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
            LIMIT 10
        ");

        foreach ($tables as $table) {
            if ($table->size_mb > 100) {
                $this->findings[] = [
                    'type' => 'large_table',
                    'table' => $table->table_name,
                    'size_mb' => $table->size_mb,
                    'rows' => $table->table_rows,
                    'severity' => $table->size_mb > 1000 ? 'high' : 'medium',
                ];

                // Suggest partitioning for very large tables
                if ($table->table_rows > 1000000) {
                    $this->recommendations[] = [
                        'type' => 'table_partitioning',
                        'table' => $table->table_name,
                        'message' => "Consider partitioning table {$table->table_name} (has {$table->table_rows} rows)",
                    ];
                }

                // Suggest archiving for historical data
                if (in_array($table->table_name, ['calls', 'appointments', 'webhook_events'])) {
                    $this->recommendations[] = [
                        'type' => 'data_archiving',
                        'table' => $table->table_name,
                        'message' => "Consider archiving old data from {$table->table_name}",
                    ];
                }
            }
        }
    }

    /**
     * Analyze connection pool
     */
    protected function analyzeConnectionPool(): void
    {
        $this->info("\n🔍 Analyzing connection pool...");

        $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
        $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'");

        $current = (int) $connections[0]->Value;
        $max = (int) $maxConnections[0]->Value;
        $usage = round(($current / $max) * 100, 2);

        if ($usage > 80) {
            $this->findings[] = [
                'type' => 'high_connection_usage',
                'current' => $current,
                'max' => $max,
                'usage_percent' => $usage,
                'severity' => 'high',
            ];

            $this->recommendations[] = [
                'type' => 'increase_max_connections',
                'message' => "Connection pool usage is at {$usage}%. Consider increasing max_connections.",
                'sql' => "SET GLOBAL max_connections = " . ($max + 50) . ";",
            ];
        }

        // Check for connection leaks
        $longRunning = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.processlist 
            WHERE time > 300 
            AND command != 'Sleep'
        ");

        if ($longRunning[0]->count > 0) {
            $this->findings[] = [
                'type' => 'long_running_queries',
                'count' => $longRunning[0]->count,
                'severity' => 'high',
            ];
        }
    }

    /**
     * Display analysis results
     */
    protected function displayResults(): void
    {
        $this->newLine();
        $this->info('📊 Performance Analysis Results');
        $this->line('===============================');

        // Group findings by severity
        $findingsBySeverity = collect($this->findings)->groupBy('severity');

        if ($findingsBySeverity->has('high')) {
            $this->error("\n🔴 Critical Issues (" . $findingsBySeverity->get('high')->count() . ")");
            foreach ($findingsBySeverity->get('high') as $finding) {
                $this->displayFinding($finding);
            }
        }

        if ($findingsBySeverity->has('medium')) {
            $this->warn("\n🟡 Warnings (" . $findingsBySeverity->get('medium')->count() . ")");
            foreach ($findingsBySeverity->get('medium') as $finding) {
                $this->displayFinding($finding);
            }
        }

        if ($findingsBySeverity->has('low')) {
            $this->info("\n🔵 Suggestions (" . $findingsBySeverity->get('low')->count() . ")");
            foreach ($findingsBySeverity->get('low') as $finding) {
                $this->displayFinding($finding);
            }
        }

        // Display recommendations
        if (count($this->recommendations) > 0) {
            $this->newLine();
            $this->info('💡 Recommendations');
            $this->line('==================');

            foreach ($this->recommendations as $i => $rec) {
                $this->line(($i + 1) . ". " . $rec['message']);
                if (isset($rec['sql'])) {
                    $this->line("   SQL: " . $rec['sql']);
                }
            }
        }

        // Summary
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Findings', count($this->findings)],
                ['Critical Issues', $findingsBySeverity->get('high', collect())->count()],
                ['Warnings', $findingsBySeverity->get('medium', collect())->count()],
                ['Suggestions', $findingsBySeverity->get('low', collect())->count()],
                ['Recommendations', count($this->recommendations)],
            ]
        );
    }

    /**
     * Display a single finding
     */
    protected function displayFinding(array $finding): void
    {
        $message = match($finding['type']) {
            'slow_query' => "Slow query '{$finding['name']}' took {$finding['duration']}ms",
            'missing_index' => "Missing indexes on {$finding['table']}: " . implode(', ', $finding['columns']),
            'unused_index' => "Potentially unused indexes on {$finding['table']}: " . implode(', ', $finding['columns']),
            'large_table' => "Large table {$finding['table']}: {$finding['size_mb']}MB ({$finding['rows']} rows)",
            'cache_eviction' => "Cache evicted {$finding['evicted_keys']} keys",
            'low_cache_hit_rate' => "Low cache hit rate: {$finding['hit_rate']}%",
            'high_connection_usage' => "High DB connection usage: {$finding['usage_percent']}%",
            'long_running_queries' => "{$finding['count']} long-running queries detected",
            default => json_encode($finding),
        };

        $this->line("  - $message");
    }

    /**
     * Apply automatic fixes
     */
    protected function applyFixes(): void
    {
        $this->newLine();
        
        if (!$this->confirm('Do you want to apply recommended fixes?')) {
            return;
        }

        $applied = 0;
        $failed = 0;

        foreach ($this->recommendations as $rec) {
            if (!isset($rec['sql'])) {
                continue;
            }

            try {
                $this->info("Applying: " . $rec['message']);
                DB::statement($rec['sql']);
                $applied++;
                $this->info("✅ Applied successfully");
            } catch (\Exception $e) {
                $failed++;
                $this->error("❌ Failed: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Applied $applied fixes, $failed failed");
    }
}