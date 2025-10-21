<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Database Performance Monitor
 *
 * Real-time query performance tracking and analysis
 *
 * FEATURES:
 * 1. Slow query detection (configurable threshold)
 * 2. N+1 query detection via query pattern analysis
 * 3. Index usage statistics
 * 4. Table bloat monitoring
 * 5. Connection pool monitoring
 * 6. Performance baseline comparison
 *
 * USAGE:
 * ```php
 * // In AppServiceProvider::boot()
 * DatabasePerformanceMonitor::enable();
 *
 * // Get performance report
 * $report = DatabasePerformanceMonitor::getReport();
 * ```
 *
 * @author Database Optimization Expert
 * @date 2025-10-18
 */
class DatabasePerformanceMonitor
{
    // Slow query threshold in milliseconds
    private const SLOW_QUERY_THRESHOLD = 100;

    // N+1 detection: same query pattern repeated
    private const N_PLUS_1_THRESHOLD = 5;

    // Query pattern cache
    private static array $queryPatterns = [];
    private static array $slowQueries = [];
    private static int $totalQueries = 0;
    private static float $totalTime = 0;

    /**
     * Enable query monitoring
     * Call this in AppServiceProvider::boot()
     */
    public static function enable(): void
    {
        if (!config('app.debug') && !config('database.monitor_performance', false)) {
            return;
        }

        DB::listen(function ($query) {
            self::analyzeQuery($query);
        });

        Log::info('📊 Database Performance Monitor ENABLED');
    }

    /**
     * Analyze a query for performance issues
     *
     * @param object $query
     * @return void
     */
    private static function analyzeQuery(object $query): void
    {
        self::$totalQueries++;
        self::$totalTime += $query->time;

        // Slow query detection
        if ($query->time > self::SLOW_QUERY_THRESHOLD) {
            self::logSlowQuery($query);
        }

        // N+1 detection
        $pattern = self::normalizeQuery($query->sql);
        if (!isset(self::$queryPatterns[$pattern])) {
            self::$queryPatterns[$pattern] = [
                'count' => 0,
                'total_time' => 0,
                'example_sql' => $query->sql,
            ];
        }

        self::$queryPatterns[$pattern]['count']++;
        self::$queryPatterns[$pattern]['total_time'] += $query->time;

        // Alert on N+1
        if (self::$queryPatterns[$pattern]['count'] === self::N_PLUS_1_THRESHOLD) {
            self::logNPlusOne($pattern, self::$queryPatterns[$pattern]);
        }
    }

    /**
     * Log slow query
     *
     * @param object $query
     * @return void
     */
    private static function logSlowQuery(object $query): void
    {
        self::$slowQueries[] = [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::warning('🐌 SLOW QUERY DETECTED', [
            'sql' => $query->sql,
            'time_ms' => round($query->time, 2),
            'threshold_ms' => self::SLOW_QUERY_THRESHOLD,
            'bindings' => $query->bindings,
        ]);
    }

    /**
     * Log N+1 query pattern
     *
     * @param string $pattern
     * @param array $data
     * @return void
     */
    private static function logNPlusOne(string $pattern, array $data): void
    {
        Log::warning('🔄 N+1 QUERY DETECTED', [
            'pattern' => $pattern,
            'executions' => $data['count'],
            'total_time_ms' => round($data['total_time'], 2),
            'avg_time_ms' => round($data['total_time'] / $data['count'], 2),
            'example' => $data['example_sql'],
            'recommendation' => 'Use eager loading with ->with() relationship',
        ]);
    }

    /**
     * Normalize query for pattern matching
     * Replaces parameters with placeholders
     *
     * @param string $sql
     * @return string
     */
    private static function normalizeQuery(string $sql): string
    {
        // Remove numbers
        $pattern = preg_replace('/\d+/', '?', $sql);

        // Remove string literals
        $pattern = preg_replace("/'[^']*'/", '?', $pattern);

        // Remove extra whitespace
        $pattern = preg_replace('/\s+/', ' ', $pattern);

        return trim($pattern);
    }

    /**
     * Get performance report
     *
     * @return array
     */
    public static function getReport(): array
    {
        return [
            'summary' => [
                'total_queries' => self::$totalQueries,
                'total_time_ms' => round(self::$totalTime, 2),
                'avg_time_ms' => self::$totalQueries > 0
                    ? round(self::$totalTime / self::$totalQueries, 2)
                    : 0,
                'slow_queries' => count(self::$slowQueries),
            ],
            'slow_queries' => self::$slowQueries,
            'query_patterns' => self::getTopPatterns(),
            'n_plus_one_candidates' => self::getNPlusOneCandidates(),
        ];
    }

    /**
     * Get top query patterns by execution count
     *
     * @param int $limit
     * @return array
     */
    private static function getTopPatterns(int $limit = 10): array
    {
        $patterns = self::$queryPatterns;

        usort($patterns, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($patterns, 0, $limit);
    }

    /**
     * Get N+1 query candidates
     *
     * @return array
     */
    private static function getNPlusOneCandidates(): array
    {
        return array_filter(self::$queryPatterns, function ($data) {
            return $data['count'] >= self::N_PLUS_1_THRESHOLD;
        });
    }

    /**
     * Get index usage statistics (PostgreSQL)
     *
     * @param string $table
     * @return array
     */
    public static function getIndexUsage(string $table = 'appointments'): array
    {
        if (DB::getDriverName() !== 'pgsql') {
            return ['error' => 'PostgreSQL only'];
        }

        $cacheKey = "db:index_usage:{$table}";

        return Cache::remember($cacheKey, 300, function () use ($table) {
            $indexes = DB::select("
                SELECT
                    schemaname,
                    tablename,
                    indexname,
                    idx_scan as scans,
                    idx_tup_read as tuples_read,
                    idx_tup_fetch as tuples_fetched,
                    pg_size_pretty(pg_relation_size(indexrelid)) as size
                FROM pg_stat_user_indexes
                WHERE tablename = ?
                ORDER BY idx_scan DESC
            ", [$table]);

            return array_map(function ($index) {
                return [
                    'name' => $index->indexname,
                    'scans' => $index->scans,
                    'tuples_read' => $index->tuples_read,
                    'tuples_fetched' => $index->tuples_fetched,
                    'size' => $index->size,
                    'efficiency' => $index->scans > 0
                        ? round(($index->tuples_fetched / $index->tuples_read) * 100, 2)
                        : 0,
                ];
            }, $indexes);
        });
    }

    /**
     * Get table bloat statistics (PostgreSQL)
     *
     * @param string $table
     * @return array
     */
    public static function getTableBloat(string $table = 'appointments'): array
    {
        if (DB::getDriverName() !== 'pgsql') {
            return ['error' => 'PostgreSQL only'];
        }

        $cacheKey = "db:table_bloat:{$table}";

        return Cache::remember($cacheKey, 3600, function () use ($table) {
            $stats = DB::selectOne("
                SELECT
                    n_live_tup as live_tuples,
                    n_dead_tup as dead_tuples,
                    ROUND((n_dead_tup::float / NULLIF(n_live_tup + n_dead_tup, 0)) * 100, 2) as bloat_percentage,
                    last_vacuum,
                    last_autovacuum,
                    last_analyze,
                    last_autoanalyze
                FROM pg_stat_user_tables
                WHERE relname = ?
            ", [$table]);

            if (!$stats) {
                return ['error' => 'Table not found'];
            }

            return [
                'live_tuples' => $stats->live_tuples,
                'dead_tuples' => $stats->dead_tuples,
                'bloat_percentage' => $stats->bloat_percentage ?? 0,
                'last_vacuum' => $stats->last_vacuum,
                'last_autovacuum' => $stats->last_autovacuum,
                'last_analyze' => $stats->last_analyze,
                'last_autoanalyze' => $stats->last_autoanalyze,
                'needs_vacuum' => ($stats->bloat_percentage ?? 0) > 10,
            ];
        });
    }

    /**
     * Get explain plan for a query (PostgreSQL)
     *
     * @param string $sql
     * @param array $bindings
     * @return array
     */
    public static function explainQuery(string $sql, array $bindings = []): array
    {
        if (DB::getDriverName() !== 'pgsql') {
            return ['error' => 'PostgreSQL only'];
        }

        // Prepare bindings
        foreach ($bindings as $binding) {
            $sql = preg_replace('/\?/', "'{$binding}'", $sql, 1);
        }

        $explain = DB::select("EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON) {$sql}");

        return json_decode($explain[0]->{'QUERY PLAN'}, true);
    }

    /**
     * Reset monitoring data
     * Call between tests or at request end
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$queryPatterns = [];
        self::$slowQueries = [];
        self::$totalQueries = 0;
        self::$totalTime = 0;
    }

    /**
     * Get current connection pool status
     *
     * @return array
     */
    public static function getConnectionPoolStatus(): array
    {
        if (DB::getDriverName() !== 'pgsql') {
            return ['error' => 'PostgreSQL only'];
        }

        $stats = DB::selectOne("
            SELECT
                count(*) as total_connections,
                count(*) FILTER (WHERE state = 'active') as active,
                count(*) FILTER (WHERE state = 'idle') as idle,
                count(*) FILTER (WHERE state = 'idle in transaction') as idle_in_transaction,
                max(extract(epoch from (now() - query_start))) as longest_query_seconds
            FROM pg_stat_activity
            WHERE datname = current_database()
        ");

        return [
            'total' => $stats->total_connections,
            'active' => $stats->active,
            'idle' => $stats->idle,
            'idle_in_transaction' => $stats->idle_in_transaction,
            'longest_query_seconds' => round($stats->longest_query_seconds ?? 0, 2),
        ];
    }

    /**
     * Save performance baseline
     * Run this after optimization to establish new baseline
     *
     * @param string $label
     * @return void
     */
    public static function saveBaseline(string $label = 'default'): void
    {
        $baseline = [
            'label' => $label,
            'timestamp' => now()->toIso8601String(),
            'metrics' => self::getReport(),
            'index_usage' => self::getIndexUsage('appointments'),
            'table_bloat' => self::getTableBloat('appointments'),
        ];

        Cache::forever("db:baseline:{$label}", $baseline);

        Log::info('📊 Performance baseline saved', ['label' => $label]);
    }

    /**
     * Compare current performance with baseline
     *
     * @param string $label
     * @return array
     */
    public static function compareWithBaseline(string $label = 'default'): array
    {
        $baseline = Cache::get("db:baseline:{$label}");

        if (!$baseline) {
            return ['error' => 'Baseline not found'];
        }

        $current = self::getReport();

        return [
            'baseline' => $baseline,
            'current' => $current,
            'improvements' => [
                'avg_query_time' => self::calculateImprovement(
                    $baseline['metrics']['summary']['avg_time_ms'],
                    $current['summary']['avg_time_ms']
                ),
                'slow_queries' => self::calculateImprovement(
                    $baseline['metrics']['summary']['slow_queries'],
                    $current['summary']['slow_queries']
                ),
            ],
        ];
    }

    /**
     * Calculate performance improvement percentage
     *
     * @param float $before
     * @param float $after
     * @return array
     */
    private static function calculateImprovement(float $before, float $after): array
    {
        if ($before == 0) {
            return ['improvement' => 0, 'percentage' => 0];
        }

        $improvement = $before - $after;
        $percentage = ($improvement / $before) * 100;

        return [
            'before' => $before,
            'after' => $after,
            'improvement' => round($improvement, 2),
            'percentage' => round($percentage, 2),
        ];
    }
}
