<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QueryOptimizer
{
    /**
     * Eagerly load relationships to prevent N+1 queries
     */
    public function optimizeEagerLoading(Builder $query, array $relationships): Builder
    {
        // Only load relationships that are actually needed
        $filteredRelationships = array_filter($relationships, function ($relation) use ($query) {
            return $this->isRelationshipUsed($query, $relation);
        });

        if (!empty($filteredRelationships)) {
            $query->with($filteredRelationships);
        }

        return $query;
    }

    /**
     * Apply index hints for better query performance
     */
    public function applyIndexHints(Builder $query, string $table, array $indexes): Builder
    {
        if (DB::getDriverName() === 'mysql') {
            // Sanitize table name to prevent SQL injection
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            
            // Sanitize index names
            $indexes = array_map(function($index) {
                return preg_replace('/[^a-zA-Z0-9_]/', '', $index);
            }, $indexes);
            
            $indexList = implode(', ', $indexes);
            // Use proper table wrapping for security
            $wrappedTable = DB::getQueryGrammar()->wrap($table);
            $query->fromRaw("`{$table}` USE INDEX (" . $this->sanitizeIndexList($indexList) . ")");
        }
        
        return $query;
    }

    /**
     * Optimize appointment queries
     */
    public function optimizeAppointmentQuery(Builder $query): Builder
    {
        // Common eager loading for appointments
        $query->with([
            'customer:id,name,email,phone',
            'staff:id,name,email',
            'branch:id,name',
            'service:id,name,duration,price'
        ]);

        // Use index for date range queries
        if ($this->hasDateRangeCondition($query)) {
            $this->applyIndexHints($query, 'appointments', ['idx_appointments_dates']);
        }

        return $query;
    }

    /**
     * Optimize customer queries
     */
    public function optimizeCustomerQuery(Builder $query): Builder
    {
        // Add appointment count without loading all appointments
        $query->withCount(['appointments' => function ($q) {
            $q->where('status', '!=', 'cancelled');
        }]);

        // Use phone index for phone searches
        if ($this->hasPhoneCondition($query)) {
            $this->applyIndexHints($query, 'customers', ['idx_customers_phone']);
        }

        return $query;
    }

    /**
     * Optimize call queries
     */
    public function optimizeCallQuery(Builder $query): Builder
    {
        // Eager load commonly used relationships
        $query->with([
            'customer:id,name,phone',
            'agent:id,name'
        ]);

        // Apply date index for recent calls
        if ($this->hasDateCondition($query, 'created_at')) {
            $this->applyIndexHints($query, 'calls', ['idx_calls_created_at']);
        }

        return $query;
    }

    /**
     * Optimize staff queries
     */
    public function optimizeStaffQuery(Builder $query): Builder
    {
        // Load relationships efficiently
        $query->with([
            'branches:id,name',
            'services:id,name',
            'homeBranch:id,name'
        ]);

        // Count appointments without loading them all
        $query->withCount(['appointments' => function ($q) {
            $q->whereDate('starts_at', '>=', now()->startOfDay());
        }]);

        return $query;
    }

    /**
     * Cache complex aggregation queries
     */
    public function cacheAggregation(string $key, \Closure $callback, int $ttl = 300)
    {
        return Cache::remember($key, $ttl, function () use ($callback) {
            // Enable query log to monitor the aggregation
            DB::enableQueryLog();
            
            $result = $callback();
            
            // Log slow aggregations
            $queries = DB::getQueryLog();
            foreach ($queries as $query) {
                if ($query['time'] > 1000) { // More than 1 second
                    Log::warning('Slow aggregation query', [
                        'sql' => $query['query'],
                        'bindings' => $query['bindings'],
                        'time' => $query['time']
                    ]);
                }
            }
            
            DB::disableQueryLog();
            
            return $result;
        });
    }

    /**
     * Analyze query performance
     */
    public function analyzeQuery(Builder $query): array
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        // Get query execution plan with proper parameter binding
        $explain = DB::select("EXPLAIN " . $sql, $bindings);
        
        // Analyze the explain results
        $analysis = [
            'sql' => $sql,
            'explain' => $explain,
            'warnings' => [],
            'suggestions' => []
        ];
        
        foreach ($explain as $row) {
            // Check for full table scans
            if ($row->type === 'ALL') {
                $analysis['warnings'][] = "Full table scan detected on table {$row->table}";
                $analysis['suggestions'][] = "Consider adding an index for table {$row->table}";
            }
            
            // Check for filesort
            if (strpos($row->Extra ?? '', 'Using filesort') !== false) {
                $analysis['warnings'][] = "Filesort detected on table {$row->table}";
                $analysis['suggestions'][] = "Consider adding an index to avoid filesort";
            }
            
            // Check for temporary tables
            if (strpos($row->Extra ?? '', 'Using temporary') !== false) {
                $analysis['warnings'][] = "Temporary table usage detected";
                $analysis['suggestions'][] = "Optimize query to avoid temporary tables";
            }
        }
        
        return $analysis;
    }

    /**
     * Get query statistics
     */
    public function getQueryStats(): array
    {
        $stats = [];
        
        if (DB::getDriverName() === 'mysql') {
            // Get slow query count
            $slowQueries = DB::select("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
            $stats['slow_queries'] = $slowQueries[0]->Value ?? 0;
            
            // Get query cache stats
            $cacheHits = DB::select("SHOW GLOBAL STATUS LIKE 'Qcache_hits'");
            $cacheMisses = DB::select("SHOW GLOBAL STATUS LIKE 'Qcache_not_cached'");
            
            $stats['cache_hits'] = $cacheHits[0]->Value ?? 0;
            $stats['cache_misses'] = $cacheMisses[0]->Value ?? 0;
            
            // Get table lock waits
            $lockWaits = DB::select("SHOW GLOBAL STATUS LIKE 'Table_locks_waited'");
            $stats['lock_waits'] = $lockWaits[0]->Value ?? 0;
        }
        
        return $stats;
    }

    /**
     * Optimize pagination queries
     */
    public function optimizePagination(Builder $query, int $perPage = 15): Builder
    {
        // Use cursor pagination for large datasets
        if ($this->isLargeDataset($query)) {
            return $query->cursorPaginate($perPage);
        }
        
        // For smaller datasets, use regular pagination with optimized count
        return $query->paginate($perPage);
    }

    /**
     * Check if a relationship is used in the query
     */
    private function isRelationshipUsed(Builder $query, string $relation): bool
    {
        $sql = $query->toSql();
        $relationTable = $this->getRelationTable($relation);
        
        return strpos($sql, $relationTable) !== false;
    }

    /**
     * Check if query has date range condition
     */
    private function hasDateRangeCondition(Builder $query): bool
    {
        $sql = $query->toSql();
        return strpos($sql, 'starts_at') !== false || strpos($sql, 'ends_at') !== false;
    }

    /**
     * Check if query has phone condition
     */
    private function hasPhoneCondition(Builder $query): bool
    {
        $sql = $query->toSql();
        return strpos($sql, 'phone') !== false;
    }

    /**
     * Check if query has date condition
     */
    private function hasDateCondition(Builder $query, string $column): bool
    {
        $sql = $query->toSql();
        return strpos($sql, $column) !== false;
    }

    /**
     * Check if dataset is large
     */
    private function isLargeDataset(Builder $query): bool
    {
        // Clone query to avoid affecting the original
        $countQuery = clone $query;
        $countQuery->getQuery()->orders = [];
        $countQuery->getQuery()->limit = null;
        $countQuery->getQuery()->offset = null;
        
        // Quick count check
        $count = $countQuery->count();
        
        return $count > 10000;
    }

    /**
     * Get relation table name
     */
    private function getRelationTable(string $relation): string
    {
        $relationMap = [
            'customer' => 'customers',
            'staff' => 'staff',
            'branch' => 'branches',
            'service' => 'services',
            'appointments' => 'appointments',
            'calls' => 'calls'
        ];
        
        return $relationMap[$relation] ?? $relation;
    }

    /**
     * Force use of specific index
     */
    public function forceIndex(Builder $query, string $table, string $index): Builder
    {
        if (DB::getDriverName() === 'mysql') {
            // Sanitize table and index names to prevent SQL injection
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $index = preg_replace('/[^a-zA-Z0-9_]/', '', $index);
            
            // Use proper table wrapping for security
            $wrappedTable = DB::getQueryGrammar()->wrap($table);
            $wrappedIndex = DB::getQueryGrammar()->wrap($index);
            $query->fromRaw("`{$table}` FORCE INDEX (" . $this->sanitizeIndexName($index) . ")");
        }
        
        return $query;
    }

    /**
     * Add query hints
     */
    public function addQueryHint(Builder $query, string $hint): Builder
    {
        if (DB::getDriverName() === 'mysql') {
            // MySQL specific hints
            switch ($hint) {
                case 'no_cache':
                    $query->selectRaw('SQL_NO_CACHE *');
                    break;
                case 'cache':
                    $query->selectRaw('SQL_CACHE *');
                    break;
                case 'big_result':
                    $query->selectRaw('SQL_BIG_RESULT *');
                    break;
            }
        }
        
        return $query;
    }
}