<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Monitor database queries in development
 * Detects N+1 problems and slow queries
 */
class QueryMonitor
{
    private array $queries = [];
    private float $startTime;
    
    public function handle(Request $request, Closure $next)
    {
        // Only monitor in development
        if (!app()->environment('local', 'development')) {
            return $next($request);
        }
        
        $this->startTime = microtime(true);
        $this->queries = [];
        
        // Start listening to queries
        DB::listen(function($query) {
            $this->queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ];
        });
        
        $response = $next($request);
        
        // Analyze queries after response
        $this->analyzeQueries($request);
        
        // Add debug headers in development
        if ($request->wantsJson()) {
            $response->headers->set('X-Query-Count', count($this->queries));
            $response->headers->set('X-Total-Query-Time', array_sum(array_column($this->queries, 'time')));
            $response->headers->set('X-Response-Time', (microtime(true) - $this->startTime) * 1000);
        }
        
        return $response;
    }
    
    private function analyzeQueries(Request $request)
    {
        $queryCount = count($this->queries);
        $totalTime = array_sum(array_column($this->queries, 'time'));
        $duplicates = $this->findDuplicateQueries();
        $slowQueries = $this->findSlowQueries();
        $possibleN1 = $this->detectN1Problems();
        
        // Log warnings for problematic patterns
        if ($queryCount > 50) {
            Log::warning('High query count detected', [
                'url' => $request->fullUrl(),
                'count' => $queryCount,
                'total_time_ms' => $totalTime,
            ]);
        }
        
        if (!empty($duplicates)) {
            Log::warning('Duplicate queries detected', [
                'url' => $request->fullUrl(),
                'duplicates' => $duplicates,
            ]);
        }
        
        if (!empty($slowQueries)) {
            Log::warning('Slow queries detected', [
                'url' => $request->fullUrl(),
                'slow_queries' => $slowQueries,
            ]);
        }
        
        if (!empty($possibleN1)) {
            Log::warning('Possible N+1 problem detected', [
                'url' => $request->fullUrl(),
                'patterns' => $possibleN1,
            ]);
        }
        
        // Detailed debug log in development
        if (app()->environment('local')) {
            Log::debug('Query Monitor Summary', [
                'url' => $request->fullUrl(),
                'total_queries' => $queryCount,
                'total_time_ms' => $totalTime,
                'avg_time_ms' => $queryCount > 0 ? $totalTime / $queryCount : 0,
                'duplicate_count' => count($duplicates),
                'slow_count' => count($slowQueries),
                'response_time_ms' => (microtime(true) - $this->startTime) * 1000,
            ]);
        }
    }
    
    private function findDuplicateQueries(): array
    {
        $normalized = [];
        $duplicates = [];
        
        foreach ($this->queries as $query) {
            // Normalize query by removing specific IDs
            $normalized_sql = preg_replace('/\b\d+\b/', '?', $query['sql']);
            
            if (!isset($normalized[$normalized_sql])) {
                $normalized[$normalized_sql] = 0;
            }
            
            $normalized[$normalized_sql]++;
            
            if ($normalized[$normalized_sql] > 1) {
                $duplicates[$normalized_sql] = $normalized[$normalized_sql];
            }
        }
        
        return $duplicates;
    }
    
    private function findSlowQueries(float $threshold = 50): array
    {
        $slow = [];
        
        foreach ($this->queries as $query) {
            if ($query['time'] > $threshold) {
                $slow[] = [
                    'sql' => $query['sql'],
                    'time_ms' => $query['time'],
                ];
            }
        }
        
        return $slow;
    }
    
    private function detectN1Problems(): array
    {
        $patterns = [];
        $tables = [];
        
        // Group queries by table
        foreach ($this->queries as $query) {
            if (preg_match('/from\s+`?(\w+)`?/i', $query['sql'], $matches)) {
                $table = $matches[1];
                
                if (!isset($tables[$table])) {
                    $tables[$table] = 0;
                }
                
                $tables[$table]++;
            }
        }
        
        // Look for tables with many similar queries
        foreach ($tables as $table => $count) {
            if ($count > 10) {
                // Check if these are likely N+1 queries
                $selectPattern = "/select .+ from `?{$table}`? where .+ = \?/i";
                $matches = 0;
                
                foreach ($this->queries as $query) {
                    if (preg_match($selectPattern, $query['sql'])) {
                        $matches++;
                    }
                }
                
                if ($matches > 5) {
                    $patterns[] = [
                        'table' => $table,
                        'count' => $matches,
                        'likely_cause' => 'Missing eager loading or join',
                    ];
                }
            }
        }
        
        return $patterns;
    }
}