<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait SecureQueryBuilder
{
    /**
     * Safely search for text using parameterized queries
     */
    public function scopeSecureSearch(Builder $query, string $column, ?string $value): Builder
    {
        if (empty($value)) {
            return $query;
        }

        // Sanitize value and use parameterized query
        $value = trim($value);
        return $query->where($column, 'LIKE', '%' . str_replace(['%', '_'], ['\%', '\_'], $value) . '%');
    }

    /**
     * Safely compare case-insensitive values
     */
    public function scopeWhereInsensitive(Builder $query, string $column, ?string $value): Builder
    {
        if (empty($value)) {
            return $query;
        }

        return $query->whereRaw('LOWER(' . DB::getTablePrefix() . $this->getTable() . '.' . $column . ') = ?', [strtolower($value)]);
    }

    /**
     * Safely search for phone numbers with normalization
     */
    public function scopeWherePhone(Builder $query, string $column, ?string $phone): Builder
    {
        if (empty($phone)) {
            return $query;
        }

        // Normalize phone number (remove spaces, dashes, etc.)
        $normalizedPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        return $query->where(function($q) use ($column, $phone, $normalizedPhone) {
            $q->where($column, $phone)
              ->orWhere($column, $normalizedPhone)
              ->orWhereRaw("REPLACE(REPLACE(REPLACE($column, ' ', ''), '-', ''), '(', '') = ?", [$normalizedPhone]);
        });
    }

    /**
     * Safely filter by date range
     */
    public function scopeWhereDateRange(Builder $query, string $column, $start, $end): Builder
    {
        if ($start) {
            $query->where($column, '>=', $start);
        }
        
        if ($end) {
            $query->where($column, '<=', $end);
        }
        
        return $query;
    }

    /**
     * Safely apply JSON contains query
     */
    public function scopeWhereJsonContainsSafe(Builder $query, string $column, $value): Builder
    {
        if (empty($value)) {
            return $query;
        }

        // Use proper JSON functions based on database driver
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            return $query->whereRaw("JSON_CONTAINS($column, ?)", [json_encode($value)]);
        } elseif ($driver === 'pgsql') {
            return $query->whereRaw("$column::jsonb @> ?::jsonb", [json_encode($value)]);
        } else {
            // SQLite fallback
            return $query->where($column, 'LIKE', '%' . str_replace(['%', '_'], ['\%', '\_'], json_encode($value)) . '%');
        }
    }

    /**
     * Safely aggregate with grouping
     */
    public function scopeSecureGroupBy(Builder $query, $columns): Builder
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        // Validate column names to prevent injection
        $validColumns = array_filter($columns, function($col) {
            return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $col);
        });

        return $query->groupBy($validColumns);
    }
}