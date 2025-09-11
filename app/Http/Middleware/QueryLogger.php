<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QueryLogger
{
    /**
     * Handle an incoming request and log slow database queries.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in production unless explicitly enabled
        if (app()->environment('production') && !config('app.enable_query_logging', false)) {
            return $next($request);
        }

        // Track the start time
        $startTime = microtime(true);
        $initialQueryCount = $this->getQueryCount();

        // Listen for database queries
        $queries = [];
        $slowQueries = [];

        DB::listen(function ($query) use (&$queries, &$slowQueries) {
            $executionTime = $query->time;
            $sql = $this->formatSql($query->sql, $query->bindings);
            
            $queryData = [
                'sql' => $sql,
                'time' => $executionTime,
                'bindings' => $query->bindings,
                'connection' => $query->connectionName,
            ];

            $queries[] = $queryData;

            // Log slow queries (>100ms)
            if ($executionTime > 100) {
                $slowQueries[] = $queryData;
                
                Log::warning('Slow Query Detected', [
                    'sql' => $sql,
                    'time' => $executionTime . 'ms',
                    'connection' => $query->connectionName,
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'user_agent' => request()->userAgent(),
                    'ip' => request()->ip(),
                ]);
            }
        });

        // Process the request
        $response = $next($request);

        // Calculate metrics
        $endTime = microtime(true);
        $totalTime = round(($endTime - $startTime) * 1000, 2);
        $finalQueryCount = $this->getQueryCount();
        $queryCount = $finalQueryCount - $initialQueryCount;
        $totalQueryTime = array_sum(array_column($queries, 'time'));

        // Add performance headers in development
        if (app()->environment(['local', 'development', 'testing'])) {
            $response->headers->set('X-Query-Count', $queryCount);
            $response->headers->set('X-Query-Time', $totalQueryTime . 'ms');
            $response->headers->set('X-Response-Time', $totalTime . 'ms');
            
            if (count($slowQueries) > 0) {
                $response->headers->set('X-Slow-Queries', count($slowQueries));
            }
        }

        // Log request summary if there are performance concerns
        if ($queryCount > 20 || $totalQueryTime > 500 || count($slowQueries) > 0) {
            Log::info('Performance Alert', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'query_count' => $queryCount,
                'total_query_time' => $totalQueryTime . 'ms',
                'response_time' => $totalTime . 'ms',
                'slow_queries_count' => count($slowQueries),
                'memory_usage' => $this->formatBytes(memory_get_peak_usage(true)),
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id ?? null,
            ]);
        }

        // Log N+1 query detection
        if ($queryCount > 50) {
            Log::warning('Potential N+1 Query Problem Detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'query_count' => $queryCount,
                'queries' => array_slice($queries, 0, 10), // First 10 queries for analysis
            ]);
        }

        return $response;
    }

    /**
     * Get current query count from connection.
     */
    private function getQueryCount(): int
    {
        return collect(DB::getConnections())
            ->sum(fn($connection) => count($connection->getQueryLog()));
    }

    /**
     * Format SQL query with bindings for logging.
     */
    private function formatSql(string $sql, array $bindings): string
    {
        if (empty($bindings)) {
            return $sql;
        }

        // Replace parameter placeholders with actual values
        $formattedSql = $sql;
        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
            $formattedSql = preg_replace('/\?/', $value, $formattedSql, 1);
        }

        return $formattedSql;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}