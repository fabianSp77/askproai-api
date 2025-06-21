<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Events\QueryExecuted;

class MCPQueryOptimizer
{
    protected array $slowQueries = [];
    protected array $suggestions = [];
    protected float $slowQueryThreshold = 100; // milliseconds
    protected bool $monitoring = false;
    
    /**
     * Start monitoring queries
     */
    public function startMonitoring(): void
    {
        $this->monitoring = true;
        $this->slowQueries = [];
        $this->suggestions = [];
        
        DB::listen(function (QueryExecuted $query) {
            if (!$this->monitoring) {
                return;
            }
            
            if ($query->time > $this->slowQueryThreshold) {
                $this->analyzeSlowQuery($query);
            }
        });
        
        Log::info('[MCPQueryOptimizer] Started monitoring queries');
    }
    
    /**
     * Stop monitoring and return analysis
     */
    public function stopMonitoring(): array
    {
        $this->monitoring = false;
        
        return [
            'slow_queries' => $this->slowQueries,
            'suggestions' => $this->suggestions,
            'total_slow_queries' => count($this->slowQueries)
        ];
    }
    
    /**
     * Analyze a slow query
     */
    protected function analyzeSlowQuery(QueryExecuted $query): void
    {
        $sql = $query->sql;
        $bindings = $query->bindings;
        $time = $query->time;
        
        // Store slow query
        $this->slowQueries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'connection' => $query->connectionName
        ];
        
        // Generate suggestions
        $this->generateSuggestions($sql, $bindings, $time);
        
        // Log slow query
        Log::warning('[MCPQueryOptimizer] Slow query detected', [
            'sql' => $sql,
            'time' => $time,
            'bindings' => $bindings
        ]);
    }
    
    /**
     * Generate optimization suggestions
     */
    protected function generateSuggestions(string $sql, array $bindings, float $time): void
    {
        $suggestions = [];
        
        // Check for missing indexes
        if ($this->needsIndex($sql)) {
            $suggestions[] = $this->suggestIndex($sql);
        }
        
        // Check for N+1 queries
        if ($this->isNPlusOneQuery($sql)) {
            $suggestions[] = [
                'type' => 'n_plus_one',
                'message' => 'Potential N+1 query detected. Consider eager loading relationships.',
                'severity' => 'high'
            ];
        }
        
        // Check for missing limits
        if ($this->needsLimit($sql)) {
            $suggestions[] = [
                'type' => 'missing_limit',
                'message' => 'Query returns unbounded results. Consider adding LIMIT.',
                'severity' => 'medium'
            ];
        }
        
        // Check for full table scans
        if ($this->isFullTableScan($sql)) {
            $suggestions[] = [
                'type' => 'full_table_scan',
                'message' => 'Query may be performing full table scan. Add WHERE clause or index.',
                'severity' => 'high'
            ];
        }
        
        // Check for unnecessary JOINs
        if ($this->hasUnnecessaryJoins($sql)) {
            $suggestions[] = [
                'type' => 'unnecessary_joins',
                'message' => 'Query contains multiple JOINs. Verify all are necessary.',
                'severity' => 'medium'
            ];
        }
        
        if (!empty($suggestions)) {
            $this->suggestions[$sql] = $suggestions;
        }
    }
    
    /**
     * Check if query needs an index
     */
    protected function needsIndex(string $sql): bool
    {
        // Check for WHERE clauses without indexes
        if (preg_match('/WHERE\s+(\w+)\s*=/', $sql, $matches)) {
            $column = $matches[1];
            
            // Common columns that should have indexes
            $indexableColumns = [
                'company_id', 'branch_id', 'customer_id', 'staff_id',
                'phone_number', 'email', 'status', 'created_at'
            ];
            
            return in_array($column, $indexableColumns);
        }
        
        return false;
    }
    
    /**
     * Suggest index for query
     */
    protected function suggestIndex(string $sql): array
    {
        preg_match('/FROM\s+`?(\w+)`?/', $sql, $tableMatch);
        preg_match('/WHERE\s+(\w+)\s*=/', $sql, $columnMatch);
        
        $table = $tableMatch[1] ?? 'unknown';
        $column = $columnMatch[1] ?? 'unknown';
        
        return [
            'type' => 'missing_index',
            'message' => "Consider adding index on {$table}.{$column}",
            'severity' => 'high',
            'sql' => "CREATE INDEX idx_{$table}_{$column} ON {$table}({$column});"
        ];
    }
    
    /**
     * Check for N+1 query pattern
     */
    protected function isNPlusOneQuery(string $sql): bool
    {
        static $recentQueries = [];
        
        // Simple pattern matching for similar queries
        $pattern = preg_replace('/\d+/', '?', $sql);
        
        if (isset($recentQueries[$pattern])) {
            $recentQueries[$pattern]++;
            
            if ($recentQueries[$pattern] > 5) {
                return true;
            }
        } else {
            $recentQueries[$pattern] = 1;
        }
        
        // Clean up old patterns
        if (count($recentQueries) > 100) {
            $recentQueries = array_slice($recentQueries, -50, null, true);
        }
        
        return false;
    }
    
    /**
     * Check if query needs LIMIT
     */
    protected function needsLimit(string $sql): bool
    {
        return stripos($sql, 'SELECT') !== false 
            && stripos($sql, 'LIMIT') === false
            && stripos($sql, 'COUNT(') === false
            && stripos($sql, 'SUM(') === false
            && stripos($sql, 'AVG(') === false;
    }
    
    /**
     * Check for full table scan
     */
    protected function isFullTableScan(string $sql): bool
    {
        return stripos($sql, 'SELECT') !== false
            && stripos($sql, 'WHERE') === false
            && stripos($sql, 'LIMIT') === false;
    }
    
    /**
     * Check for unnecessary JOINs
     */
    protected function hasUnnecessaryJoins(string $sql): bool
    {
        $joinCount = substr_count(strtoupper($sql), 'JOIN');
        return $joinCount > 3;
    }
    
    /**
     * Get query execution plan
     */
    public function explainQuery(string $sql, array $bindings = []): array
    {
        try {
            $explained = DB::select("EXPLAIN {$sql}", $bindings);
            return $this->analyzeExecutionPlan($explained);
        } catch (\Exception $e) {
            Log::error('[MCPQueryOptimizer] Failed to explain query', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Analyze execution plan
     */
    protected function analyzeExecutionPlan(array $plan): array
    {
        $analysis = [
            'issues' => [],
            'suggestions' => [],
            'estimated_rows' => 0
        ];
        
        foreach ($plan as $row) {
            // Check for table scans
            if (isset($row->type) && $row->type === 'ALL') {
                $analysis['issues'][] = "Full table scan on {$row->table}";
                $analysis['suggestions'][] = "Add index to {$row->table}";
            }
            
            // Check for filesort
            if (isset($row->Extra) && strpos($row->Extra, 'filesort') !== false) {
                $analysis['issues'][] = 'Query uses filesort';
                $analysis['suggestions'][] = 'Consider adding index for ORDER BY columns';
            }
            
            // Check for temporary tables
            if (isset($row->Extra) && strpos($row->Extra, 'temporary') !== false) {
                $analysis['issues'][] = 'Query creates temporary table';
                $analysis['suggestions'][] = 'Optimize query to avoid temporary tables';
            }
            
            // Sum estimated rows
            if (isset($row->rows)) {
                $analysis['estimated_rows'] += (int)$row->rows;
            }
        }
        
        return $analysis;
    }
    
    /**
     * Automatically create suggested indexes
     */
    public function createSuggestedIndexes(bool $dryRun = true): array
    {
        $created = [];
        
        foreach ($this->suggestions as $sql => $suggestions) {
            foreach ($suggestions as $suggestion) {
                if ($suggestion['type'] === 'missing_index' && isset($suggestion['sql'])) {
                    if ($dryRun) {
                        $created[] = [
                            'sql' => $suggestion['sql'],
                            'status' => 'dry_run'
                        ];
                    } else {
                        try {
                            DB::statement($suggestion['sql']);
                            $created[] = [
                                'sql' => $suggestion['sql'],
                                'status' => 'created'
                            ];
                            Log::info('[MCPQueryOptimizer] Created index', [
                                'sql' => $suggestion['sql']
                            ]);
                        } catch (\Exception $e) {
                            $created[] = [
                                'sql' => $suggestion['sql'],
                                'status' => 'failed',
                                'error' => $e->getMessage()
                            ];
                            Log::error('[MCPQueryOptimizer] Failed to create index', [
                                'sql' => $suggestion['sql'],
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
        }
        
        return $created;
    }
    
    /**
     * Get database statistics
     */
    public function getDatabaseStats(): array
    {
        $stats = [];
        
        try {
            // Get table sizes
            $tables = DB::select("
                SELECT 
                    table_name,
                    table_rows,
                    data_length,
                    index_length,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ");
            
            $stats['largest_tables'] = $tables;
            
            // Get index usage
            $indexes = DB::select("
                SELECT 
                    table_name,
                    index_name,
                    cardinality
                FROM information_schema.STATISTICS
                WHERE table_schema = DATABASE()
                    AND cardinality > 0
                ORDER BY cardinality DESC
                LIMIT 20
            ");
            
            $stats['most_used_indexes'] = $indexes;
            
            // Get slow query log status
            $slowLogStatus = DB::select("SHOW VARIABLES LIKE 'slow_query_log'");
            $stats['slow_query_log_enabled'] = $slowLogStatus[0]->Value ?? 'OFF';
            
        } catch (\Exception $e) {
            Log::error('[MCPQueryOptimizer] Failed to get database stats', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $stats;
    }
}