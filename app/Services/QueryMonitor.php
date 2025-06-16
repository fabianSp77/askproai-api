<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Events\QueryExecuted;

class QueryMonitor
{
    /**
     * Threshold for slow queries in milliseconds
     */
    private $slowQueryThreshold = 1000; // 1 second

    /**
     * Enable query monitoring
     */
    public function enable(): void
    {
        DB::listen(function (QueryExecuted $query) {
            $this->logQuery($query);
        });
    }

    /**
     * Log executed query
     */
    private function logQuery(QueryExecuted $query): void
    {
        $sql = $query->sql;
        $bindings = $query->bindings;
        $time = $query->time;

        // Replace bindings in SQL for better readability
        $fullSql = $this->replaceBindings($sql, $bindings);

        // Check if it's a slow query
        if ($time > $this->slowQueryThreshold) {
            $this->logSlowQuery($fullSql, $time, $query->connectionName);
        }

        // Store query statistics
        $this->updateQueryStats($sql, $time);

        // Store in recent queries cache
        $this->storeRecentQuery($fullSql, $time);
    }

    /**
     * Replace SQL bindings with actual values
     */
    private function replaceBindings(string $sql, array $bindings): string
    {
        foreach ($bindings as $binding) {
            if (is_string($binding)) {
                $binding = "'$binding'";
            } elseif (is_null($binding)) {
                $binding = 'NULL';
            } elseif (is_bool($binding)) {
                $binding = $binding ? 'TRUE' : 'FALSE';
            }
            
            $sql = preg_replace('/\?/', $binding, $sql, 1);
        }
        
        return $sql;
    }

    /**
     * Log slow query
     */
    private function logSlowQuery(string $sql, float $time, string $connection): void
    {
        Log::channel('slow_queries')->warning('Slow query detected', [
            'sql' => $sql,
            'time' => $time . 'ms',
            'connection' => $connection,
            'backtrace' => $this->getQueryBacktrace(),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Also store in database for analysis
        DB::table('slow_query_log')->insert([
            'sql' => $sql,
            'time' => $time,
            'connection' => $connection,
            'backtrace' => json_encode($this->getQueryBacktrace()),
            'created_at' => now()
        ]);
    }

    /**
     * Get query backtrace for debugging
     */
    private function getQueryBacktrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $relevantTrace = [];

        foreach ($trace as $frame) {
            if (isset($frame['file']) && !str_contains($frame['file'], 'vendor/')) {
                $relevantTrace[] = [
                    'file' => str_replace(base_path(), '', $frame['file']),
                    'line' => $frame['line'] ?? null,
                    'function' => $frame['function'] ?? null,
                    'class' => $frame['class'] ?? null
                ];
            }
        }

        return array_slice($relevantTrace, 0, 5); // Keep only top 5 frames
    }

    /**
     * Update query statistics
     */
    private function updateQueryStats(string $sql, float $time): void
    {
        $table = $this->extractTableName($sql);
        $operation = $this->extractOperation($sql);
        
        $key = "query_stats:{$table}:{$operation}";
        
        $stats = Cache::get($key, [
            'count' => 0,
            'total_time' => 0,
            'avg_time' => 0,
            'max_time' => 0,
            'min_time' => PHP_INT_MAX
        ]);

        $stats['count']++;
        $stats['total_time'] += $time;
        $stats['avg_time'] = $stats['total_time'] / $stats['count'];
        $stats['max_time'] = max($stats['max_time'], $time);
        $stats['min_time'] = min($stats['min_time'], $time);

        Cache::put($key, $stats, now()->addHours(24));
    }

    /**
     * Extract table name from SQL
     */
    private function extractTableName(string $sql): string
    {
        // Simple extraction for common queries
        if (preg_match('/FROM\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/UPDATE\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/INSERT\s+INTO\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/DELETE\s+FROM\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        
        return 'unknown';
    }

    /**
     * Extract operation type from SQL
     */
    private function extractOperation(string $sql): string
    {
        $sql = strtoupper(trim($sql));
        
        if (strpos($sql, 'SELECT') === 0) return 'SELECT';
        if (strpos($sql, 'INSERT') === 0) return 'INSERT';
        if (strpos($sql, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($sql, 'DELETE') === 0) return 'DELETE';
        
        return 'OTHER';
    }

    /**
     * Store recent query for analysis
     */
    private function storeRecentQuery(string $sql, float $time): void
    {
        $queries = Cache::get('recent_queries', []);
        
        array_unshift($queries, [
            'sql' => $sql,
            'time' => $time,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        // Keep only last 100 queries
        $queries = array_slice($queries, 0, 100);
        
        Cache::put('recent_queries', $queries, now()->addHours(1));
    }

    /**
     * Get query statistics
     */
    public function getStats(): array
    {
        $stats = [];
        $keys = Cache::get('query_stat_keys', []);
        
        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data) {
                $parts = explode(':', $key);
                $table = $parts[1] ?? 'unknown';
                $operation = $parts[2] ?? 'unknown';
                
                $stats[$table][$operation] = $data;
            }
        }
        
        return $stats;
    }

    /**
     * Get recent queries
     */
    public function getRecentQueries(int $limit = 50): array
    {
        $queries = Cache::get('recent_queries', []);
        return array_slice($queries, 0, $limit);
    }

    /**
     * Get slow queries
     */
    public function getSlowQueries(int $limit = 50): array
    {
        return DB::table('slow_query_log')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($query) {
                $query->backtrace = json_decode($query->backtrace, true);
                return $query;
            })
            ->toArray();
    }

    /**
     * Clear query statistics
     */
    public function clearStats(): void
    {
        $keys = Cache::get('query_stat_keys', []);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        Cache::forget('query_stat_keys');
        Cache::forget('recent_queries');
    }

    /**
     * Analyze query patterns
     */
    public function analyzePatterns(): array
    {
        $analysis = [
            'n_plus_one' => $this->detectNPlusOneQueries(),
            'missing_indexes' => $this->detectMissingIndexes(),
            'duplicate_queries' => $this->detectDuplicateQueries(),
            'expensive_queries' => $this->detectExpensiveQueries()
        ];
        
        return $analysis;
    }

    /**
     * Detect N+1 query patterns
     */
    private function detectNPlusOneQueries(): array
    {
        $queries = $this->getRecentQueries(200);
        $patterns = [];
        
        // Group similar queries
        $grouped = [];
        foreach ($queries as $query) {
            $pattern = preg_replace('/\d+/', 'N', $query['sql']);
            $grouped[$pattern][] = $query;
        }
        
        // Find patterns with many similar queries
        foreach ($grouped as $pattern => $queries) {
            if (count($queries) > 10) {
                $patterns[] = [
                    'pattern' => $pattern,
                    'count' => count($queries),
                    'total_time' => array_sum(array_column($queries, 'time')),
                    'suggestion' => 'Consider using eager loading to avoid N+1 queries'
                ];
            }
        }
        
        return $patterns;
    }

    /**
     * Detect potential missing indexes
     */
    private function detectMissingIndexes(): array
    {
        $slowQueries = $this->getSlowQueries(100);
        $missingIndexes = [];
        
        foreach ($slowQueries as $query) {
            // Look for WHERE clauses without indexes
            if (preg_match('/WHERE.*?(\w+)\s*=/', $query->sql, $matches)) {
                $column = $matches[1];
                $table = $this->extractTableName($query->sql);
                
                $missingIndexes[] = [
                    'table' => $table,
                    'column' => $column,
                    'query_time' => $query->time,
                    'suggestion' => "Consider adding index on {$table}.{$column}"
                ];
            }
        }
        
        return array_unique($missingIndexes, SORT_REGULAR);
    }

    /**
     * Detect duplicate queries
     */
    private function detectDuplicateQueries(): array
    {
        $queries = $this->getRecentQueries(200);
        $duplicates = [];
        $seen = [];
        
        foreach ($queries as $query) {
            if (isset($seen[$query['sql']])) {
                if (!isset($duplicates[$query['sql']])) {
                    $duplicates[$query['sql']] = [
                        'count' => 1,
                        'total_time' => $seen[$query['sql']]['time']
                    ];
                }
                $duplicates[$query['sql']]['count']++;
                $duplicates[$query['sql']]['total_time'] += $query['time'];
            } else {
                $seen[$query['sql']] = $query;
            }
        }
        
        return array_map(function ($sql, $data) {
            return [
                'sql' => $sql,
                'count' => $data['count'],
                'total_time' => $data['total_time'],
                'suggestion' => 'Consider caching this query result'
            ];
        }, array_keys($duplicates), $duplicates);
    }

    /**
     * Detect expensive queries
     */
    private function detectExpensiveQueries(): array
    {
        $stats = $this->getStats();
        $expensive = [];
        
        foreach ($stats as $table => $operations) {
            foreach ($operations as $operation => $data) {
                if ($data['avg_time'] > 500 || $data['max_time'] > 2000) {
                    $expensive[] = [
                        'table' => $table,
                        'operation' => $operation,
                        'avg_time' => $data['avg_time'],
                        'max_time' => $data['max_time'],
                        'count' => $data['count'],
                        'suggestion' => 'Optimize this query or add appropriate indexes'
                    ];
                }
            }
        }
        
        return $expensive;
    }

    /**
     * Set slow query threshold
     */
    public function setSlowQueryThreshold(int $milliseconds): void
    {
        $this->slowQueryThreshold = $milliseconds;
    }
}