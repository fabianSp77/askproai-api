<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\QueryExecuted;

class QueryMonitor
{
    protected static array $queries = [];
    protected static bool $monitoring = false;
    protected static array $n1Patterns = [];
    
    /**
     * Start monitoring queries
     */
    public static function start(): void
    {
        if (static::$monitoring) {
            return;
        }
        
        static::$monitoring = true;
        static::$queries = [];
        static::$n1Patterns = [];
        
        DB::listen(function (QueryExecuted $query) {
            static::recordQuery($query);
        });
    }
    
    /**
     * Stop monitoring and return results
     */
    public static function stop(): array
    {
        static::$monitoring = false;
        
        return [
            'total_queries' => count(static::$queries),
            'total_time_ms' => array_sum(array_column(static::$queries, 'time')),
            'n1_queries' => static::detectN1Queries(),
            'slow_queries' => static::getSlowQueries(),
            'duplicate_queries' => static::getDuplicateQueries(),
        ];
    }
    
    /**
     * Record a query
     */
    protected static function recordQuery(QueryExecuted $query): void
    {
        if (!static::$monitoring) {
            return;
        }
        
        $sql = str_replace(['?'], ['%s'], $query->sql);
        $sql = vsprintf($sql, $query->bindings);
        
        static::$queries[] = [
            'sql' => $sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'connection' => $query->connectionName,
        ];
        
        // Log slow queries immediately
        if ($query->time > config('database.connections.mysql.slow_query_time', 2000)) {
            Log::warning('Slow query detected', [
                'sql' => $sql,
                'time_ms' => $query->time,
                'connection' => $query->connectionName,
            ]);
        }
    }
    
    /**
     * Detect N+1 queries
     */
    protected static function detectN1Queries(): array
    {
        $patterns = [];
        $n1Queries = [];
        
        foreach (static::$queries as $query) {
            // Extract table name and where clause pattern
            if (preg_match('/select .* from `?(\w+)`? where .* = \d+/i', $query['sql'], $matches)) {
                $table = $matches[1];
                $pattern = preg_replace('/= \d+/', '= ?', $query['sql']);
                
                if (!isset($patterns[$pattern])) {
                    $patterns[$pattern] = [
                        'count' => 0,
                        'table' => $table,
                        'queries' => [],
                    ];
                }
                
                $patterns[$pattern]['count']++;
                $patterns[$pattern]['queries'][] = $query['sql'];
            }
        }
        
        // Filter patterns that appear more than once (potential N+1)
        foreach ($patterns as $pattern => $data) {
            if ($data['count'] > 1) {
                $n1Queries[] = [
                    'pattern' => $pattern,
                    'table' => $data['table'],
                    'count' => $data['count'],
                    'sample_queries' => array_slice($data['queries'], 0, 3),
                ];
            }
        }
        
        return $n1Queries;
    }
    
    /**
     * Get slow queries
     */
    protected static function getSlowQueries(float $threshold = 100): array
    {
        return array_filter(static::$queries, fn($query) => $query['time'] > $threshold);
    }
    
    /**
     * Get duplicate queries
     */
    protected static function getDuplicateQueries(): array
    {
        $queryCount = [];
        
        foreach (static::$queries as $query) {
            $key = $query['sql'];
            if (!isset($queryCount[$key])) {
                $queryCount[$key] = [
                    'count' => 0,
                    'total_time' => 0,
                    'sql' => $query['sql'],
                ];
            }
            $queryCount[$key]['count']++;
            $queryCount[$key]['total_time'] += $query['time'];
        }
        
        // Filter queries that appear more than once
        return array_filter($queryCount, fn($data) => $data['count'] > 1);
    }
    
    /**
     * Log N+1 query warning
     */
    public static function logN1Warning(string $model, string $relation): void
    {
        if (config('app.debug')) {
            Log::warning('Potential N+1 query detected', [
                'model' => $model,
                'relation' => $relation,
                'suggestion' => "Add ->with('{$relation}') to your query",
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);
        }
    }
    
    /**
     * Get query statistics
     */
    public static function getStats(): array
    {
        return [
            'total_queries' => count(static::$queries),
            'total_time_ms' => round(array_sum(array_column(static::$queries, 'time')), 2),
            'average_time_ms' => count(static::$queries) > 0 
                ? round(array_sum(array_column(static::$queries, 'time')) / count(static::$queries), 2)
                : 0,
            'queries_by_type' => static::getQueriesByType(),
        ];
    }
    
    /**
     * Get queries grouped by type
     */
    protected static function getQueriesByType(): array
    {
        $types = [
            'select' => 0,
            'insert' => 0,
            'update' => 0,
            'delete' => 0,
            'other' => 0,
        ];
        
        foreach (static::$queries as $query) {
            $sql = strtolower(trim($query['sql']));
            
            if (str_starts_with($sql, 'select')) {
                $types['select']++;
            } elseif (str_starts_with($sql, 'insert')) {
                $types['insert']++;
            } elseif (str_starts_with($sql, 'update')) {
                $types['update']++;
            } elseif (str_starts_with($sql, 'delete')) {
                $types['delete']++;
            } else {
                $types['other']++;
            }
        }
        
        return $types;
    }
}