<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DATABASE TABLE LIST ===\n\n";

try {
    $tables = DB::select('SHOW TABLES');
    $tableList = [];
    
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        $tableList[] = $tableName;
    }
    
    sort($tableList);
    
    echo "Total tables: " . count($tableList) . "\n\n";
    
    // Save to file
    file_put_contents(__DIR__ . '/../backups/2025-06-17/all_tables.txt', implode("\n", $tableList));
    
    // Group tables by prefix
    $groups = [];
    foreach ($tableList as $table) {
        $prefix = explode('_', $table)[0];
        if (!isset($groups[$prefix])) {
            $groups[$prefix] = [];
        }
        $groups[$prefix][] = $table;
    }
    
    foreach ($groups as $prefix => $tables) {
        echo "\n{$prefix}_* tables (" . count($tables) . "):\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}