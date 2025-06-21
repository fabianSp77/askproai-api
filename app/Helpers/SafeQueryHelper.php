<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class SafeQueryHelper
{
    /**
     * Escape special characters for LIKE queries to prevent SQL injection
     * 
     * @param string $value The value to escape
     * @param string $escape The escape character (default: \)
     * @return string The escaped value safe for use in LIKE queries
     */
    public static function escapeLike(string $value, string $escape = '\\'): string
    {
        // Escape the escape character first
        $value = str_replace($escape, $escape . $escape, $value);
        
        // Escape LIKE wildcards
        $value = str_replace('%', $escape . '%', $value);
        $value = str_replace('_', $escape . '_', $value);
        
        return $value;
    }
    
    /**
     * Build a safe LIKE query with proper escaping
     * 
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param string $column The column to search
     * @param string $value The value to search for
     * @param string $type The type of LIKE query: 'both', 'left', 'right', 'none'
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function whereLike($query, string $column, string $value, string $type = 'both')
    {
        $escaped = self::escapeLike($value);
        
        switch ($type) {
            case 'left':
                $pattern = '%' . $escaped;
                break;
            case 'right':
                $pattern = $escaped . '%';
                break;
            case 'none':
                $pattern = $escaped;
                break;
            case 'both':
            default:
                $pattern = '%' . $escaped . '%';
                break;
        }
        
        return $query->where($column, 'LIKE', $pattern);
    }
    
    /**
     * Build a safe case-insensitive comparison using LOWER()
     * 
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param string $column The column to compare
     * @param string $value The value to compare against
     * @param string $operator The comparison operator (default: =)
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function whereLower($query, string $column, string $value, string $operator = '=')
    {
        // Validate operator to prevent injection
        $allowedOperators = ['=', '!=', '<>', 'LIKE', 'NOT LIKE'];
        if (!in_array($operator, $allowedOperators)) {
            throw new \InvalidArgumentException('Invalid operator for whereLower');
        }
        
        if ($operator === 'LIKE' || $operator === 'NOT LIKE') {
            $value = self::escapeLike($value);
        }
        
        return $query->whereRaw('LOWER(' . $column . ') ' . $operator . ' LOWER(?)', [$value]);
    }
    
    /**
     * Sanitize a column name to prevent SQL injection
     * 
     * @param string $column The column name to sanitize
     * @param array $allowedColumns Optional whitelist of allowed column names
     * @return string The sanitized column name
     */
    public static function sanitizeColumn(string $column, array $allowedColumns = []): string
    {
        // If whitelist is provided, validate against it
        if (!empty($allowedColumns) && !in_array($column, $allowedColumns)) {
            throw new \InvalidArgumentException('Invalid column name: ' . $column);
        }
        
        // Remove any non-alphanumeric characters except underscore and dot (for table.column)
        $sanitized = preg_replace('/[^a-zA-Z0-9_.]/', '', $column);
        
        // Ensure it doesn't start with a number
        if (preg_match('/^[0-9]/', $sanitized)) {
            throw new \InvalidArgumentException('Column name cannot start with a number');
        }
        
        return $sanitized;
    }
    
    /**
     * Build a safe ORDER BY clause
     * 
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param string $column The column to order by
     * @param string $direction The sort direction (asc/desc)
     * @param array $allowedColumns Optional whitelist of allowed column names
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function orderBySafe($query, string $column, string $direction = 'asc', array $allowedColumns = [])
    {
        $column = self::sanitizeColumn($column, $allowedColumns);
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        
        return $query->orderBy($column, $direction);
    }
    
    /**
     * Build a safe JSON contains query
     * 
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param string $column The JSON column to search
     * @param mixed $value The value to search for
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function whereJsonContains($query, string $column, $value)
    {
        $column = self::sanitizeColumn($column);
        
        if (DB::connection()->getDriverName() === 'mysql') {
            return $query->whereRaw("JSON_CONTAINS($column, ?)", [json_encode($value)]);
        } elseif (DB::connection()->getDriverName() === 'pgsql') {
            return $query->whereRaw("$column::jsonb @> ?::jsonb", [json_encode($value)]);
        } else {
            // Fallback for SQLite and others
            return $query->where($column, 'LIKE', '%' . self::escapeLike(json_encode($value)) . '%');
        }
    }
    
    /**
     * Create a safe full-text search query
     * 
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param array $columns The columns to search
     * @param string $search The search term
     * @param string $mode The search mode (NATURAL LANGUAGE MODE or BOOLEAN MODE)
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function whereFullText($query, array $columns, string $search, string $mode = 'NATURAL LANGUAGE MODE')
    {
        // Sanitize columns
        $columns = array_map(function ($col) {
            return self::sanitizeColumn($col);
        }, $columns);
        
        $columnList = implode(', ', $columns);
        
        // Validate mode
        $allowedModes = ['NATURAL LANGUAGE MODE', 'BOOLEAN MODE', 'NATURAL LANGUAGE MODE WITH QUERY EXPANSION'];
        if (!in_array($mode, $allowedModes)) {
            $mode = 'NATURAL LANGUAGE MODE';
        }
        
        if (DB::connection()->getDriverName() === 'mysql') {
            return $query->whereRaw("MATCH($columnList) AGAINST(? IN $mode)", [$search]);
        } else {
            // Fallback to LIKE for non-MySQL databases
            return $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . self::escapeLike($search) . '%');
                }
            });
        }
    }
}