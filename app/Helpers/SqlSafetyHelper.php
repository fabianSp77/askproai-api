<?php

namespace App\Helpers;

class SqlSafetyHelper
{
    /**
     * Validate day of week for SQL queries
     */
    public static function validateDayOfWeek(string $day): string
    {
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $day = strtolower($day);
        
        if (!in_array($day, $validDays)) {
            throw new \InvalidArgumentException("Invalid day of week: " . $day);
        }
        
        return $day;
    }
    
    /**
     * Safely build JSON path for SQL queries
     */
    public static function safeJsonPath(string $field, string $path): string
    {
        // Remove any SQL special characters
        $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
        $path = preg_replace('/[^a-zA-Z0-9_.]/', '', $path);
        
        return "$." . $path;
    }
    
    /**
     * Validate table name
     */
    public static function validateTableName(string $table): string
    {
        // Only allow alphanumeric and underscore
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException("Invalid table name: " . $table);
        }
        
        return $table;
    }
}
