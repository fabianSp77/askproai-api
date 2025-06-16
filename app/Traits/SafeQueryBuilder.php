<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SafeQueryBuilder
{
    /**
     * Safe LIKE query with proper escaping
     */
    public function scopeSafeLike(Builder $query, string $column, ?string $value, string $mode = 'both'): Builder
    {
        if (empty($value)) {
            return $query;
        }
        
        // Escape special LIKE characters
        $value = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
        
        // Apply wildcard based on mode
        $searchValue = match($mode) {
            'start' => $value . '%',
            'end' => '%' . $value,
            'both' => '%' . $value . '%',
            'exact' => $value,
            default => '%' . $value . '%',
        };
        
        return $query->where($column, 'LIKE', $searchValue);
    }
    
    /**
     * Safe search across multiple columns
     */
    public function scopeSafeSearch(Builder $query, array $columns, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }
        
        return $query->where(function ($q) use ($columns, $search) {
            foreach ($columns as $column) {
                $q->orWhere(function ($subQuery) use ($column, $search) {
                    $subQuery->safeLike($column, $search);
                });
            }
        });
    }
    
    /**
     * Safe whereIn with type validation
     */
    public function scopeSafeWhereIn(Builder $query, string $column, $values): Builder
    {
        if (empty($values)) {
            return $query->whereRaw('0 = 1'); // Always false
        }
        
        // Ensure array
        if (!is_array($values)) {
            $values = [$values];
        }
        
        // Filter and validate values
        $validValues = array_filter($values, function ($value) {
            return !is_null($value) && !is_array($value) && !is_object($value);
        });
        
        if (empty($validValues)) {
            return $query->whereRaw('0 = 1');
        }
        
        return $query->whereIn($column, $validValues);
    }
    
    /**
     * Safe date range query
     */
    public function scopeSafeDateRange(Builder $query, string $column, $start, $end): Builder
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
     * Safe ordering with whitelist
     */
    public function scopeSafeOrderBy(Builder $query, ?string $column, string $direction = 'asc'): Builder
    {
        if (empty($column)) {
            return $query;
        }
        
        // Get allowed columns from model
        $allowedColumns = array_merge(
            $this->getFillable(),
            ['id', 'created_at', 'updated_at'],
            $this->getSortableColumns ?? []
        );
        
        if (!in_array($column, $allowedColumns)) {
            return $query;
        }
        
        // Validate direction
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        
        return $query->orderBy($column, $direction);
    }
}