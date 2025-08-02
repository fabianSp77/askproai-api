<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;

class DatabaseOptimizationService
{
    protected $slowQueries = [];
    protected $missingIndexes = [];
    protected $inefficientQueries = [];
    protected $monitoring = false;

    /**
     * Start monitoring queries.
     */
    public function startMonitoring(): void
    {
        if ($this->monitoring) {
            return;
        }

        $this->monitoring = true;
        $this->slowQueries = [];
        $this->missingIndexes = [];
        $this->inefficientQueries = [];

        DB::listen(function ($query) {
            $this->analyzeQuery($query);
        });
    }

    /**
     * Stop monitoring and return analysis.
     */
    public function stopMonitoring(): array
    {
        $this->monitoring = false;
        
        return [
            'slow_queries' => $this->slowQueries,
            'missing_indexes' => $this->missingIndexes,
            'inefficient_queries' => $this->inefficientQueries,
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    /**
     * Analyze a single query.
     */
    protected function analyzeQuery($query): void
    {
        // Check if query is slow
        if ($query->time > config('performance.query_optimization.slow_query_threshold', 100)) {
            $this->recordSlowQuery($query);
        }

        // Analyze query plan
        $this->analyzeQueryPlan($query);

        // Check for missing indexes
        $this->checkMissingIndexes($query);

        // Detect inefficient patterns
        $this->detectInefficientPatterns($query);
    }

    /**
     * Record slow query.
     */
    protected function recordSlowQuery($query): void
    {
        $this->slowQueries[] = [
            'sql' => $this->formatSql($query->sql),
            'bindings' => $query->bindings,
            'time' => $query->time,
            'connection' => $query->connectionName,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
            'timestamp' => now()->toIso8601String(),
        ];

        // Log to slow query log
        Log::channel('slow_queries')->warning('Slow query detected', [
            'sql' => $query->sql,
            'time' => $query->time,
            'bindings' => $query->bindings,
        ]);
    }

    /**
     * Analyze query execution plan.
     */
    protected function analyzeQueryPlan($query): void
    {
        // Skip non-SELECT queries
        if (!stripos(trim($query->sql), 'select') === 0) {
            return;
        }

        try {
            $explain = DB::select('EXPLAIN ' . $query->sql, $query->bindings);
            
            foreach ($explain as $row) {
                // Check for full table scans
                if (isset($row->type) && $row->type === 'ALL' && isset($row->rows) && $row->rows > 1000) {
                    $this->inefficientQueries[] = [
                        'type' => 'full_table_scan',
                        'table' => $row->table ?? 'unknown',
                        'rows' => $row->rows,
                        'sql' => $this->formatSql($query->sql),
                    ];
                }

                // Check for filesort
                if (isset($row->Extra) && str_contains($row->Extra, 'filesort')) {
                    $this->inefficientQueries[] = [
                        'type' => 'filesort',
                        'table' => $row->table ?? 'unknown',
                        'sql' => $this->formatSql($query->sql),
                    ];
                }

                // Check for temporary tables
                if (isset($row->Extra) && str_contains($row->Extra, 'Using temporary')) {
                    $this->inefficientQueries[] = [
                        'type' => 'temporary_table',
                        'table' => $row->table ?? 'unknown',
                        'sql' => $this->formatSql($query->sql),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail for now
        }
    }

    /**
     * Check for missing indexes.
     */
    protected function checkMissingIndexes($query): void
    {
        // Extract WHERE conditions
        $whereConditions = $this->extractWhereConditions($query->sql);
        
        // Extract JOIN conditions
        $joinConditions = $this->extractJoinConditions($query->sql);
        
        // Extract ORDER BY columns
        $orderByColumns = $this->extractOrderByColumns($query->sql);

        // Check each table involved
        $tables = $this->extractTables($query->sql);
        
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $indexes = $this->getTableIndexes($table);
            $columns = Schema::getColumnListing($table);

            // Check WHERE conditions
            foreach ($whereConditions as $column) {
                if (in_array($column, $columns) && !$this->hasIndex($indexes, $column)) {
                    $this->suggestIndex($table, [$column], 'where');
                }
            }

            // Check JOIN conditions
            foreach ($joinConditions as $column) {
                if (in_array($column, $columns) && !$this->hasIndex($indexes, $column)) {
                    $this->suggestIndex($table, [$column], 'join');
                }
            }

            // Check ORDER BY columns
            if (!empty($orderByColumns) && !empty($whereConditions)) {
                $compositeIndex = array_merge($whereConditions, $orderByColumns);
                if (!$this->hasCompositeIndex($indexes, $compositeIndex)) {
                    $this->suggestIndex($table, $compositeIndex, 'composite');
                }
            }
        }
    }

    /**
     * Detect inefficient query patterns.
     */
    protected function detectInefficientPatterns($query): void
    {
        $sql = strtolower($query->sql);

        // Detect SELECT *
        if (preg_match('/select\s+\*\s+from/i', $sql)) {
            $this->inefficientQueries[] = [
                'type' => 'select_star',
                'sql' => $this->formatSql($query->sql),
                'recommendation' => 'Specify only the columns you need instead of SELECT *',
            ];
        }

        // Detect LIKE with leading wildcard
        if (preg_match('/like\s+[\'"]%/i', $sql)) {
            $this->inefficientQueries[] = [
                'type' => 'leading_wildcard',
                'sql' => $this->formatSql($query->sql),
                'recommendation' => 'Leading wildcards prevent index usage. Consider full-text search.',
            ];
        }

        // Detect OR conditions
        if (preg_match('/where.*\sor\s/i', $sql)) {
            $this->inefficientQueries[] = [
                'type' => 'or_condition',
                'sql' => $this->formatSql($query->sql),
                'recommendation' => 'OR conditions can prevent index usage. Consider using UNION or IN.',
            ];
        }

        // Detect functions on indexed columns
        if (preg_match('/where.*(?:year|month|date|lower|upper)\s*\(/i', $sql)) {
            $this->inefficientQueries[] = [
                'type' => 'function_on_column',
                'sql' => $this->formatSql($query->sql),
                'recommendation' => 'Functions on columns prevent index usage. Consider functional indexes.',
            ];
        }

        // Detect N+1 queries
        $this->detectNPlusOneQueries($query);
    }

    /**
     * Detect N+1 query problems.
     */
    protected function detectNPlusOneQueries($query): void
    {
        static $recentQueries = [];
        static $lastCleanup = null;

        // Cleanup old queries every minute
        if ($lastCleanup === null || $lastCleanup->diffInSeconds(now()) > 60) {
            $recentQueries = [];
            $lastCleanup = now();
        }

        // Hash the query pattern
        $pattern = $this->getQueryPattern($query->sql);
        $hash = md5($pattern);

        if (!isset($recentQueries[$hash])) {
            $recentQueries[$hash] = [
                'pattern' => $pattern,
                'count' => 0,
                'first_seen' => now(),
                'example_sql' => $query->sql,
            ];
        }

        $recentQueries[$hash]['count']++;

        // If we see the same pattern more than 10 times in a short period, it's likely N+1
        if ($recentQueries[$hash]['count'] > 10 && 
            $recentQueries[$hash]['first_seen']->diffInSeconds(now()) < 5) {
            
            $this->inefficientQueries[] = [
                'type' => 'n_plus_one',
                'pattern' => $pattern,
                'count' => $recentQueries[$hash]['count'],
                'sql' => $this->formatSql($query->sql),
                'recommendation' => 'Use eager loading to avoid N+1 queries',
            ];
        }
    }

    /**
     * Generate optimization recommendations.
     */
    public function generateRecommendations(): array
    {
        $recommendations = [];

        // Recommend indexes
        $indexGroups = [];
        foreach ($this->missingIndexes as $index) {
            $key = $index['table'] . '_' . implode('_', $index['columns']);
            if (!isset($indexGroups[$key])) {
                $indexGroups[$key] = $index;
            }
        }

        foreach ($indexGroups as $index) {
            $recommendations[] = [
                'type' => 'index',
                'priority' => 'high',
                'table' => $index['table'],
                'columns' => $index['columns'],
                'reason' => $index['reason'],
                'sql' => $this->generateIndexSql($index['table'], $index['columns']),
            ];
        }

        // Recommend query rewrites
        $queryTypes = [];
        foreach ($this->inefficientQueries as $query) {
            if (!isset($queryTypes[$query['type']])) {
                $queryTypes[$query['type']] = [];
            }
            $queryTypes[$query['type']][] = $query;
        }

        foreach ($queryTypes as $type => $queries) {
            $recommendations[] = [
                'type' => 'query_rewrite',
                'priority' => $type === 'n_plus_one' ? 'high' : 'medium',
                'issue' => $type,
                'count' => count($queries),
                'examples' => array_slice($queries, 0, 3),
            ];
        }

        // Recommend caching for frequently executed queries
        $recommendations[] = [
            'type' => 'caching',
            'priority' => 'medium',
            'slow_queries' => count($this->slowQueries),
            'suggestion' => 'Consider caching results of slow or frequently executed queries',
        ];

        return $recommendations;
    }

    /**
     * Optimize a specific query.
     */
    public function optimizeQuery(string $sql, array $bindings = []): array
    {
        $suggestions = [];

        // Analyze the query
        $this->startMonitoring();
        DB::select($sql, $bindings);
        $analysis = $this->stopMonitoring();

        // Generate specific suggestions
        if (!empty($analysis['missing_indexes'])) {
            $suggestions[] = [
                'type' => 'add_indexes',
                'indexes' => $analysis['missing_indexes'],
            ];
        }

        // Suggest query rewrites
        $rewritten = $this->rewriteQuery($sql);
        if ($rewritten !== $sql) {
            $suggestions[] = [
                'type' => 'rewrite',
                'original' => $sql,
                'optimized' => $rewritten,
            ];
        }

        return [
            'analysis' => $analysis,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Create missing indexes.
     */
    public function createIndex(string $table, array $columns, string $name = null): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $name = $name ?: $this->generateIndexName($table, $columns);

        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                $table->index($columns, $name);
            });

            Log::info('Created index', [
                'table' => $table,
                'columns' => $columns,
                'name' => $name,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create index', [
                'table' => $table,
                'columns' => $columns,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Analyze database health.
     */
    public function analyzeDatabaseHealth(): array
    {
        $health = [
            'tables' => [],
            'overall_score' => 100,
            'issues' => [],
            'recommendations' => [],
        ];

        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();

        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_{$dbName}"};
            
            // Get table info
            $tableInfo = $this->analyzeTable($tableName);
            $health['tables'][$tableName] = $tableInfo;

            // Check for issues
            if ($tableInfo['rows'] > 100000 && empty($tableInfo['indexes'])) {
                $health['issues'][] = "Table {$tableName} has {$tableInfo['rows']} rows but no indexes";
                $health['overall_score'] -= 10;
            }

            if ($tableInfo['fragmentation'] > 20) {
                $health['issues'][] = "Table {$tableName} is {$tableInfo['fragmentation']}% fragmented";
                $health['recommendations'][] = "OPTIMIZE TABLE {$tableName}";
                $health['overall_score'] -= 5;
            }
        }

        // Check connection pool usage
        $connections = DB::select('SHOW PROCESSLIST');
        $activeConnections = count($connections);
        $maxConnections = DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value;
        
        $connectionUsage = ($activeConnections / $maxConnections) * 100;
        if ($connectionUsage > 80) {
            $health['issues'][] = "High connection usage: {$connectionUsage}%";
            $health['recommendations'][] = "Consider increasing max_connections or implementing connection pooling";
            $health['overall_score'] -= 15;
        }

        return $health;
    }

    /**
     * Helper methods
     */
    protected function formatSql(string $sql): string
    {
        // Remove excess whitespace
        $sql = preg_replace('/\s+/', ' ', $sql);
        
        // Truncate very long queries
        if (strlen($sql) > 500) {
            $sql = substr($sql, 0, 500) . '...';
        }

        return trim($sql);
    }

    protected function extractWhereConditions(string $sql): array
    {
        $columns = [];
        
        // Simple pattern matching for WHERE conditions
        if (preg_match_all('/where\s+(\w+)\s*=/i', $sql, $matches)) {
            $columns = array_merge($columns, $matches[1]);
        }

        return array_unique($columns);
    }

    protected function extractJoinConditions(string $sql): array
    {
        $columns = [];
        
        // Extract JOIN ON conditions
        if (preg_match_all('/on\s+\w+\.(\w+)\s*=\s*\w+\.(\w+)/i', $sql, $matches)) {
            $columns = array_merge($columns, $matches[1], $matches[2]);
        }

        return array_unique($columns);
    }

    protected function extractOrderByColumns(string $sql): array
    {
        $columns = [];
        
        // Extract ORDER BY columns
        if (preg_match('/order\s+by\s+([\w,\s]+)/i', $sql, $matches)) {
            $orderBy = preg_split('/,\s*/', $matches[1]);
            foreach ($orderBy as $col) {
                $col = trim(preg_replace('/\s+(asc|desc)$/i', '', $col));
                if ($col) {
                    $columns[] = $col;
                }
            }
        }

        return $columns;
    }

    protected function extractTables(string $sql): array
    {
        $tables = [];
        
        // Extract FROM table
        if (preg_match('/from\s+(\w+)/i', $sql, $matches)) {
            $tables[] = $matches[1];
        }

        // Extract JOIN tables
        if (preg_match_all('/join\s+(\w+)/i', $sql, $matches)) {
            $tables = array_merge($tables, $matches[1]);
        }

        return array_unique($tables);
    }

    protected function getTableIndexes(string $table): array
    {
        $indexes = [];
        
        try {
            $result = DB::select("SHOW INDEX FROM {$table}");
            foreach ($result as $row) {
                $indexName = $row->Key_name;
                if (!isset($indexes[$indexName])) {
                    $indexes[$indexName] = [];
                }
                $indexes[$indexName][] = $row->Column_name;
            }
        } catch (\Exception $e) {
            // Table might not exist
        }

        return $indexes;
    }

    protected function hasIndex(array $indexes, string $column): bool
    {
        foreach ($indexes as $indexColumns) {
            if ($indexColumns[0] === $column) {
                return true;
            }
        }
        return false;
    }

    protected function hasCompositeIndex(array $indexes, array $columns): bool
    {
        foreach ($indexes as $indexColumns) {
            if ($indexColumns === $columns) {
                return true;
            }
        }
        return false;
    }

    protected function suggestIndex(string $table, array $columns, string $reason): void
    {
        $this->missingIndexes[] = [
            'table' => $table,
            'columns' => $columns,
            'reason' => $reason,
            'name' => $this->generateIndexName($table, $columns),
        ];
    }

    protected function generateIndexName(string $table, array $columns): string
    {
        return 'idx_' . $table . '_' . implode('_', $columns);
    }

    protected function generateIndexSql(string $table, array $columns): string
    {
        $columnList = implode(', ', array_map(function ($col) {
            return "`{$col}`";
        }, $columns));
        
        $indexName = $this->generateIndexName($table, $columns);
        
        return "CREATE INDEX `{$indexName}` ON `{$table}` ({$columnList});";
    }

    protected function getQueryPattern(string $sql): string
    {
        // Replace values with placeholders to identify patterns
        $pattern = preg_replace('/\d+/', '?', $sql);
        $pattern = preg_replace('/\'[^\']*\'/', '?', $pattern);
        $pattern = preg_replace('/\"[^\"]*\"/', '?', $pattern);
        
        return $pattern;
    }

    protected function rewriteQuery(string $sql): string
    {
        $rewritten = $sql;

        // Replace SELECT * with specific columns (would need table analysis)
        if (preg_match('/select\s+\*\s+from\s+(\w+)/i', $sql, $matches)) {
            // In real implementation, would get actual columns
            // $columns = Schema::getColumnListing($matches[1]);
            // $rewritten = str_replace('SELECT *', 'SELECT ' . implode(', ', $columns), $rewritten);
        }

        // Convert OR to IN where possible
        $rewritten = preg_replace(
            '/(\w+)\s*=\s*\'([^\']+)\'\s+or\s+\1\s*=\s*\'([^\']+)\'/i',
            "$1 IN ('$2', '$3')",
            $rewritten
        );

        return $rewritten;
    }

    protected function analyzeTable(string $table): array
    {
        $info = [
            'rows' => 0,
            'size' => 0,
            'indexes' => [],
            'fragmentation' => 0,
        ];

        try {
            // Get row count
            $count = DB::select("SELECT COUNT(*) as count FROM {$table}");
            $info['rows'] = $count[0]->count ?? 0;

            // Get table status
            $status = DB::select("SHOW TABLE STATUS LIKE '{$table}'");
            if (!empty($status)) {
                $info['size'] = $status[0]->Data_length + $status[0]->Index_length;
                $info['fragmentation'] = $status[0]->Data_free > 0 
                    ? round(($status[0]->Data_free / $info['size']) * 100, 2)
                    : 0;
            }

            // Get indexes
            $info['indexes'] = $this->getTableIndexes($table);
        } catch (\Exception $e) {
            // Ignore errors
        }

        return $info;
    }
}