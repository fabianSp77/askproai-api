<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Safe query helper to prevent SQL injection vulnerabilities
 * All dynamic SQL operations should use this class
 */
class SafeQueryHelper
{
    /**
     * Allowed tables for index operations
     */
    private const ALLOWED_TABLES = [
        'appointments',
        'customers',
        'staff',
        'branches',
        'services',
        'calls',
        'companies',
        'webhook_events',
        'calcom_event_types',
        'users',
        'phone_numbers',
        'retell_agents',
        'unified_event_types',
        'calcom_bookings'
    ];

    /**
     * Allowed indexes for optimization
     */
    private const ALLOWED_INDEXES = [
        'idx_appointments_dates',
        'idx_appointments_company_status',
        'idx_customers_phone',
        'idx_customers_company_name',
        'idx_calls_created_at',
        'idx_calls_company_date',
        'idx_calls_phone_number',
        'idx_webhook_events_event_id',
        'idx_companies_phone_number',
        'PRIMARY'
    ];

    /**
     * Safe table name validation
     */
    public static function validateTableName(string $table): bool
    {
        return in_array($table, self::ALLOWED_TABLES);
    }

    /**
     * Safe index name validation
     */
    public static function validateIndexName(string $index): bool
    {
        return in_array($index, self::ALLOWED_INDEXES) || 
               preg_match('/^idx_[a-zA-Z0-9_]+$/', $index);
    }

    /**
     * Safe column name validation
     */
    public static function validateColumnName(string $column): bool
    {
        // Allow table.column format
        if (strpos($column, '.') !== false) {
            [$table, $col] = explode('.', $column, 2);
            return self::validateTableName($table) && 
                   preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col);
        }
        
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column);
    }

    /**
     * Safe LIKE pattern escaping
     */
    public static function escapeLikePattern(string $pattern): string
    {
        // Escape special LIKE characters
        $pattern = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $pattern);
        return $pattern;
    }

    /**
     * Safe JSON path extraction
     */
    public static function safeJsonExtract(string $column, string $path): string
    {
        if (!self::validateColumnName($column)) {
            throw new \InvalidArgumentException('Invalid column name');
        }
        
        // Validate JSON path
        if (!preg_match('/^\$(\.[a-zA-Z0-9_\[\]]+)*$/', $path)) {
            throw new \InvalidArgumentException('Invalid JSON path');
        }
        
        return "JSON_EXTRACT(" . DB::getQueryGrammar()->wrap($column) . ", " . DB::getPdo()->quote($path) . ")";
    }

    /**
     * Safe date format function
     */
    public static function safeDateFormat(string $column, string $format): string
    {
        if (!self::validateColumnName($column)) {
            throw new \InvalidArgumentException('Invalid column name');
        }
        
        // Validate date format
        $allowedFormats = ['%Y-%m-%d', '%Y-%m', '%Y', '%H:%i:%s', '%Y-%m-%d %H:%i:%s'];
        if (!in_array($format, $allowedFormats)) {
            throw new \InvalidArgumentException('Invalid date format');
        }
        
        return "DATE_FORMAT(" . DB::getQueryGrammar()->wrap($column) . ", " . DB::getPdo()->quote($format) . ")";
    }

    /**
     * Safe aggregation function
     */
    public static function safeAggregate(string $function, string $column): string
    {
        $allowedFunctions = ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX'];
        $function = strtoupper($function);
        
        if (!in_array($function, $allowedFunctions)) {
            throw new \InvalidArgumentException('Invalid aggregation function');
        }
        
        if ($column !== '*' && !self::validateColumnName($column)) {
            throw new \InvalidArgumentException('Invalid column name');
        }
        
        $wrappedColumn = $column === '*' ? '*' : DB::getQueryGrammar()->wrap($column);
        return "{$function}({$wrappedColumn})";
    }

    /**
     * Safe case statement builder
     */
    public static function safeCaseWhen(array $conditions, $default = null): string
    {
        $sql = "CASE";
        
        foreach ($conditions as $condition) {
            if (!isset($condition['when']) || !isset($condition['then'])) {
                throw new \InvalidArgumentException('Invalid case condition structure');
            }
            
            // Validate column in condition
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*\?$/', $condition['when'], $matches)) {
                $column = $matches[1];
                if (!self::validateColumnName($column)) {
                    throw new \InvalidArgumentException('Invalid column in condition');
                }
            }
            
            $sql .= " WHEN {$condition['when']} THEN ?";
        }
        
        if ($default !== null) {
            $sql .= " ELSE ?";
        }
        
        $sql .= " END";
        
        return $sql;
    }

    /**
     * Log potentially unsafe query usage
     */
    public static function logUnsafeQuery(string $method, array $context): void
    {
        Log::warning('Potentially unsafe query detected', [
            'method' => $method,
            'context' => $context,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ]);
    }

    /**
     * Create safe index hint query
     */
    public static function withIndexHint(Builder $query, string $table, array $indexes): Builder
    {
        if (!self::validateTableName($table)) {
            self::logUnsafeQuery('withIndexHint', ['table' => $table]);
            return $query;
        }
        
        $validIndexes = array_filter($indexes, [self::class, 'validateIndexName']);
        
        if (empty($validIndexes)) {
            return $query;
        }
        
        // Since Laravel doesn't support index hints natively,
        // we'll add a comment to the query for manual optimization
        $indexList = implode(', ', $validIndexes);
        $query->whereRaw('1=1 /* USE INDEX (' . $indexList . ') */');
        
        return $query;
    }
}