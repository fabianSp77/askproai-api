<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\DB;

class SqlProtectionService
{
    /**
     * Safely quote a table name
     */
    public static function quoteTable(string $table): string
    {
        // Remove any non-alphanumeric characters except underscore
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        // Wrap in backticks for MySQL
        return "`{$safe}`";
    }
    
    /**
     * Safely quote a column name
     */
    public static function quoteColumn(string $column): string
    {
        // Remove any non-alphanumeric characters except underscore
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        
        // Wrap in backticks for MySQL
        return "`{$safe}`";
    }
    
    /**
     * Validate and sanitize order by direction
     */
    public static function sanitizeOrderDirection(string $direction): string
    {
        $direction = strtoupper($direction);
        return in_array($direction, ['ASC', 'DESC']) ? $direction : 'ASC';
    }
    
    /**
     * Check if a table exists and is allowed
     */
    public static function isTableAllowed(string $table, array $allowedTables): bool
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        return in_array($safe, $allowedTables);
    }
}