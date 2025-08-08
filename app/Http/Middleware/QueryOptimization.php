<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QueryOptimization
{
    /**
     * Query threshold in milliseconds for logging slow queries
     */
    private const SLOW_QUERY_THRESHOLD = 100;
    
    /**
     * Maximum number of queries allowed per request
     */
    private const MAX_QUERIES_WARNING = 50;
    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enable query logging for monitoring
        if (config('app.debug') || $request->has('debug_queries')) {
            DB::enableQueryLog();
        }
        
        // Set database connection optimizations
        $this->optimizeDatabaseConnection();
        
        // Process the request
        $response = $next($request);
        
        // Analyze and log query performance
        if (DB::logging()) {
            $this->analyzeQueries($request);
        }
        
        return $response;
    }
    
    /**
     * Optimize database connection settings
     */
    private function optimizeDatabaseConnection(): void
    {
        // Set MySQL session variables for better performance
        try {
            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            DB::statement("SET SESSION query_cache_type = ON");
            
            // Increase buffer sizes for complex queries
            DB::statement("SET SESSION sort_buffer_size = 2097152"); // 2MB
            DB::statement("SET SESSION read_buffer_size = 262144"); // 256KB
            DB::statement("SET SESSION join_buffer_size = 262144"); // 256KB
            
            // Optimize for read-heavy workload on call pages
            if (request()->is('admin/calls*')) {
                DB::statement("SET SESSION read_rnd_buffer_size = 524288"); // 512KB
            }
        } catch (\Exception $e) {
            // Log but don't fail if optimization fails
            Log::warning('Failed to optimize database connection: ' . $e->getMessage());
        }
    }
    
    /**
     * Analyze executed queries and log performance issues
     */
    private function analyzeQueries(Request $request): void
    {
        $queries = DB::getQueryLog();
        $totalQueries = count($queries);
        $slowQueries = [];
        $duplicateQueries = [];
        $totalTime = 0;
        
        // Group queries to find duplicates
        $queryGroups = [];
        
        foreach ($queries as $query) {
            $sql = $query['query'];
            $time = $query['time'];
            $totalTime += $time;
            
            // Track slow queries
            if ($time > self::SLOW_QUERY_THRESHOLD) {
                $slowQueries[] = [
                    'sql' => $sql,
                    'bindings' => $query['bindings'],
                    'time' => $time,
                ];
            }
            
            // Track duplicate queries
            $queryKey = md5($sql . serialize($query['bindings']));
            if (!isset($queryGroups[$queryKey])) {
                $queryGroups[$queryKey] = [
                    'sql' => $sql,
                    'bindings' => $query['bindings'],
                    'count' => 0,
                    'total_time' => 0,
                ];
            }
            $queryGroups[$queryKey]['count']++;
            $queryGroups[$queryKey]['total_time'] += $time;
        }
        
        // Find actual duplicates (executed more than once)
        foreach ($queryGroups as $group) {
            if ($group['count'] > 1) {
                $duplicateQueries[] = $group;
            }
        }
        
        // Log performance warnings
        if ($totalQueries > self::MAX_QUERIES_WARNING) {
            Log::warning('High query count detected', [
                'url' => $request->fullUrl(),
                'total_queries' => $totalQueries,
                'total_time' => round($totalTime, 2) . 'ms',
                'user_id' => auth()->id(),
            ]);
        }
        
        if (!empty($slowQueries)) {
            Log::warning('Slow queries detected', [
                'url' => $request->fullUrl(),
                'slow_queries' => array_slice($slowQueries, 0, 5), // Log top 5 slow queries
                'user_id' => auth()->id(),
            ]);
        }
        
        if (!empty($duplicateQueries)) {
            Log::warning('Duplicate queries detected (N+1 problem)', [
                'url' => $request->fullUrl(),
                'duplicates' => array_map(function($q) {
                    return [
                        'sql' => substr($q['sql'], 0, 100) . '...',
                        'count' => $q['count'],
                        'total_time' => round($q['total_time'], 2) . 'ms',
                    ];
                }, array_slice($duplicateQueries, 0, 5)),
                'user_id' => auth()->id(),
            ]);
        }
        
        // Add query stats to response headers in debug mode
        if (config('app.debug')) {
            header('X-DB-Query-Count: ' . $totalQueries);
            header('X-DB-Query-Time: ' . round($totalTime, 2) . 'ms');
            header('X-DB-Slow-Queries: ' . count($slowQueries));
            header('X-DB-Duplicate-Queries: ' . count($duplicateQueries));
        }
    }
    
    /**
     * Get optimization suggestions based on query patterns
     */
    public function getOptimizationSuggestions(array $queries): array
    {
        $suggestions = [];
        
        // Check for missing indexes
        foreach ($queries as $query) {
            if (stripos($query['sql'], 'WHERE') !== false && $query['time'] > 50) {
                if (stripos($query['sql'], 'calls') !== false) {
                    $suggestions[] = 'Consider adding indexes on frequently queried columns in calls table';
                }
            }
        }
        
        // Check for SELECT * queries
        foreach ($queries as $query) {
            if (stripos($query['sql'], 'SELECT *') !== false) {
                $suggestions[] = 'Avoid SELECT * queries, specify only needed columns';
            }
        }
        
        // Check for missing eager loading
        $tableAccessCount = [];
        foreach ($queries as $query) {
            if (preg_match('/FROM\s+`?(\w+)`?/i', $query['sql'], $matches)) {
                $table = $matches[1];
                $tableAccessCount[$table] = ($tableAccessCount[$table] ?? 0) + 1;
            }
        }
        
        foreach ($tableAccessCount as $table => $count) {
            if ($count > 10) {
                $suggestions[] = "Table '{$table}' accessed {$count} times - consider eager loading";
            }
        }
        
        return array_unique($suggestions);
    }
}