<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryPerformanceMonitor
{
    private array $queries = [];

    private float $startTime;

    private bool $enabled;

    public function __construct()
    {
        $this->enabled = config('app.debug', false) || config('monitoring.query_performance', false);
        $this->startTime = microtime(true);
    }

    /**
     * Start monitoring queries.
     */
    public function start(): void
    {
        if (! $this->enabled) {
            return;
        }

        DB::listen(function ($query) {
            $sql = $query->sql;
            $bindings = $query->bindings;
            $time = $query->time;

            // Replace bindings in SQL
            $fullQuery = vsprintf(str_replace('?', "'%s'", $sql), $bindings);

            $this->queries[] = [
                'sql' => $sql,
                'full_query' => $fullQuery,
                'bindings' => $bindings,
                'time' => $time,
                'connection' => $query->connectionName,
                'backtrace' => $this->getSimpleBacktrace(),
            ];

            // Log slow queries
            if ($time > 100) { // > 100ms
                $this->logSlowQuery($fullQuery, $time);
            }
        });
    }

    /**
     * Get all executed queries.
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Get query statistics.
     */
    public function getStats(): array
    {
        $totalQueries = count($this->queries);
        $totalTime = array_sum(array_column($this->queries, 'time'));
        $slowQueries = array_filter($this->queries, fn ($q) => $q['time'] > 100);

        return [
            'total_queries' => $totalQueries,
            'total_time_ms' => round($totalTime, 2),
            'average_time_ms' => $totalQueries > 0 ? round($totalTime / $totalQueries, 2) : 0,
            'slow_queries' => count($slowQueries),
            'slowest_query' => $this->getSlowestQuery(),
            'duplicate_queries' => $this->findDuplicateQueries(),
            'n_plus_one_suspects' => $this->detectNPlusOne(),
        ];
    }

    /**
     * Get the slowest query.
     */
    private function getSlowestQuery(): ?array
    {
        if (empty($this->queries)) {
            return null;
        }

        $slowest = array_reduce($this->queries, function ($carry, $item) {
            return (! $carry || $item['time'] > $carry['time']) ? $item : $carry;
        });

        return [
            'query' => $slowest['full_query'],
            'time_ms' => $slowest['time'],
            'location' => $slowest['backtrace'][0] ?? 'Unknown',
        ];
    }

    /**
     * Find duplicate queries.
     */
    private function findDuplicateQueries(): array
    {
        $queryCount = [];

        foreach ($this->queries as $query) {
            $key = md5($query['sql']);
            if (! isset($queryCount[$key])) {
                $queryCount[$key] = [
                    'sql' => $query['sql'],
                    'count' => 0,
                    'total_time' => 0,
                ];
            }
            $queryCount[$key]['count']++;
            $queryCount[$key]['total_time'] += $query['time'];
        }

        // Filter only duplicates
        return array_filter($queryCount, fn ($q) => $q['count'] > 1);
    }

    /**
     * Detect potential N+1 query problems.
     */
    private function detectNPlusOne(): array
    {
        $suspects = [];
        $patterns = [];

        foreach ($this->queries as $query) {
            // Extract table and pattern
            if (preg_match('/from\s+`?(\w+)`?\s+where/i', $query['sql'], $matches)) {
                $table = $matches[1];
                $pattern = preg_replace('/\d+/', 'N', $query['sql']);

                if (! isset($patterns[$pattern])) {
                    $patterns[$pattern] = [
                        'table' => $table,
                        'count' => 0,
                        'example' => $query['sql'],
                    ];
                }
                $patterns[$pattern]['count']++;
            }
        }

        // Find patterns that repeat more than 5 times
        foreach ($patterns as $pattern => $data) {
            if ($data['count'] > 5) {
                $suspects[] = [
                    'table' => $data['table'],
                    'occurrences' => $data['count'],
                    'example' => $data['example'],
                ];
            }
        }

        return $suspects;
    }

    /**
     * Log slow query.
     */
    private function logSlowQuery(string $query, float $time): void
    {
        $logData = [
            'query' => $query,
            'time_ms' => $time,
            'backtrace' => $this->getSimpleBacktrace(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Log to file
        Log::channel('slow-queries')->warning('Slow query detected', $logData);

        // Store in cache for dashboard
        $slowQueries = Cache::get('slow_queries', []);
        array_unshift($slowQueries, $logData);
        $slowQueries = array_slice($slowQueries, 0, 100); // Keep last 100
        Cache::put('slow_queries', $slowQueries, now()->addHours(24));
    }

    /**
     * Get simplified backtrace.
     */
    private function getSimpleBacktrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $simplified = [];

        foreach ($trace as $frame) {
            if (isset($frame['file']) && ! str_contains($frame['file'], 'vendor/')) {
                $simplified[] = basename($frame['file']) . ':' . ($frame['line'] ?? '?');
                if (count($simplified) >= 3) {
                    break;
                }
            }
        }

        return $simplified;
    }

    /**
     * Generate HTML report.
     */
    public function generateHtmlReport(): string
    {
        $stats = $this->getStats();

        $html = '<div style="font-family: monospace; padding: 20px; background: #f5f5f5;">';
        $html .= '<h2>Query Performance Report</h2>';

        // Stats
        $html .= '<h3>Statistics</h3>';
        $html .= '<ul>';
        $html .= "<li>Total Queries: {$stats['total_queries']}</li>";
        $html .= "<li>Total Time: {$stats['total_time_ms']}ms</li>";
        $html .= "<li>Average Time: {$stats['average_time_ms']}ms</li>";
        $html .= "<li>Slow Queries (>100ms): {$stats['slow_queries']}</li>";
        $html .= '</ul>';

        // Slowest Query
        if ($stats['slowest_query']) {
            $html .= '<h3>Slowest Query</h3>';
            $html .= '<pre style="background: #fff; padding: 10px; border: 1px solid #ddd;">';
            $html .= htmlspecialchars($stats['slowest_query']['query']);
            $html .= "\nTime: {$stats['slowest_query']['time_ms']}ms";
            $html .= "\nLocation: {$stats['slowest_query']['location']}";
            $html .= '</pre>';
        }

        // Duplicate Queries
        if (! empty($stats['duplicate_queries'])) {
            $html .= '<h3>Duplicate Queries</h3>';
            foreach ($stats['duplicate_queries'] as $dup) {
                $html .= '<div style="background: #fff; padding: 10px; margin: 5px 0; border: 1px solid #ddd;">';
                $html .= "<strong>Executed {$dup['count']} times (Total: {$dup['total_time']}ms)</strong><br>";
                $html .= '<pre>' . htmlspecialchars($dup['sql']) . '</pre>';
                $html .= '</div>';
            }
        }

        // N+1 Suspects
        if (! empty($stats['n_plus_one_suspects'])) {
            $html .= '<h3>Potential N+1 Problems</h3>';
            foreach ($stats['n_plus_one_suspects'] as $suspect) {
                $html .= '<div style="background: #ffe6e6; padding: 10px; margin: 5px 0; border: 1px solid #ffcccc;">';
                $html .= "<strong>Table: {$suspect['table']} - {$suspect['occurrences']} similar queries</strong><br>";
                $html .= '<pre>' . htmlspecialchars($suspect['example']) . '</pre>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
    }
}
