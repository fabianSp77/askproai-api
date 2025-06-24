#!/usr/bin/php
<?php
/**
 * SQL Injection Fix Script
 * Fixes the most critical SQL injection vulnerabilities
 */

echo "=== SQL Injection Vulnerability Fixer ===\n\n";

$fixes = [
    [
        'file' => 'app/Services/RealTime/IntelligentCallRouter.php',
        'description' => 'Fix whereRaw with dayOfWeek interpolation',
        'search' => '->whereRaw("JSON_EXTRACT(working_hours, \'$.{$dayOfWeek}.start\') <= ?", [$currentTime])
              ->whereRaw("JSON_EXTRACT(working_hours, \'$.{$dayOfWeek}.end\') >= ?", [$currentTime]);',
        'replace' => '->where(function($sq) use ($dayOfWeek, $currentTime) {
                  // Validate dayOfWeek against whitelist
                  $validDays = [\'monday\', \'tuesday\', \'wednesday\', \'thursday\', \'friday\', \'saturday\', \'sunday\'];
                  if (!in_array($dayOfWeek, $validDays)) {
                      throw new \InvalidArgumentException("Invalid day of week: " . $dayOfWeek);
                  }
                  
                  $sq->whereRaw("JSON_EXTRACT(working_hours, ?) <= ?", ["$." . $dayOfWeek . ".start", $currentTime])
                     ->whereRaw("JSON_EXTRACT(working_hours, ?) >= ?", ["$." . $dayOfWeek . ".end", $currentTime]);
              });'
    ],
    [
        'file' => 'app/Services/RealTime/ConcurrentCallManager.php',
        'description' => 'Fix whereRaw with dayOfWeek interpolation',
        'search' => '->whereRaw("JSON_EXTRACT(working_hours, \'$.{$dayOfWeek}.start\') <= ?", [$currentTime])
                        ->whereRaw("JSON_EXTRACT(working_hours, \'$.{$dayOfWeek}.end\') >= ?", [$currentTime]);',
        'replace' => '->where(function($sq) use ($dayOfWeek, $currentTime) {
                          // Validate dayOfWeek against whitelist
                          $validDays = [\'monday\', \'tuesday\', \'wednesday\', \'thursday\', \'friday\', \'saturday\', \'sunday\'];
                          if (!in_array($dayOfWeek, $validDays)) {
                              throw new \InvalidArgumentException("Invalid day of week: " . $dayOfWeek);
                          }
                          
                          $sq->whereRaw("JSON_EXTRACT(working_hours, ?) <= ?", ["$." . $dayOfWeek . ".start", $currentTime])
                             ->whereRaw("JSON_EXTRACT(working_hours, ?) >= ?", ["$." . $dayOfWeek . ".end", $currentTime]);
                      });'
    ]
];

$fixed = 0;
$failed = 0;

foreach ($fixes as $fix) {
    $filePath = __DIR__ . '/' . $fix['file'];
    
    if (!file_exists($filePath)) {
        echo "❌ File not found: {$fix['file']}\n";
        $failed++;
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Create backup
    $backupPath = $filePath . '.backup.' . date('YmdHis');
    file_put_contents($backupPath, $content);
    
    // Apply fix
    $newContent = str_replace($fix['search'], $fix['replace'], $content);
    
    if ($newContent !== $content) {
        file_put_contents($filePath, $newContent);
        echo "✅ Fixed: {$fix['file']}\n";
        echo "   {$fix['description']}\n";
        echo "   Backup: $backupPath\n";
        $fixed++;
    } else {
        echo "⚠️  No match found in: {$fix['file']}\n";
        echo "   Pattern might have already been fixed or changed\n";
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: $fixed files\n";
echo "Failed: $failed files\n";

// Additional validation helper
$validationHelper = '<?php

namespace App\Helpers;

class SqlSafetyHelper
{
    /**
     * Validate day of week for SQL queries
     */
    public static function validateDayOfWeek(string $day): string
    {
        $validDays = [\'monday\', \'tuesday\', \'wednesday\', \'thursday\', \'friday\', \'saturday\', \'sunday\'];
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
        $field = preg_replace(\'/[^a-zA-Z0-9_]/\', \'\', $field);
        $path = preg_replace(\'/[^a-zA-Z0-9_.]/\', \'\', $path);
        
        return "$." . $path;
    }
    
    /**
     * Validate table name
     */
    public static function validateTableName(string $table): string
    {
        // Only allow alphanumeric and underscore
        if (!preg_match(\'/^[a-zA-Z0-9_]+$/\', $table)) {
            throw new \InvalidArgumentException("Invalid table name: " . $table);
        }
        
        return $table;
    }
}
';

file_put_contents(__DIR__ . '/app/Helpers/SqlSafetyHelper.php', $validationHelper);
echo "\n✅ Created SqlSafetyHelper class for SQL injection prevention\n";