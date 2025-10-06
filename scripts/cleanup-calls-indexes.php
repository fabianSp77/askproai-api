#!/usr/bin/env php
<?php

/**
 * /sc:cleanup - Index Cleanup Script
 * Problem: calls table has 93 indexes (max 64 allowed!)
 * Solution: Remove duplicate and unnecessary indexes
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 SuperClaude Index Cleanup Script\n";
echo "=====================================\n\n";

// Analyze current indexes
$indexes = DB::select('SHOW INDEX FROM calls');
$indexGroups = [];

foreach ($indexes as $index) {
    $indexGroups[$index->Key_name][] = $index->Column_name;
}

echo "📊 Current Status:\n";
echo "  - Total indexes: " . count($indexes) . " (Max: 64)\n";
echo "  - Unique index names: " . count($indexGroups) . "\n\n";

// Find duplicate indexes
$duplicates = [];
$keep = [];

foreach ($indexGroups as $name => $columns) {
    // Skip PRIMARY and UNIQUE constraints
    if ($name === 'PRIMARY' || strpos($name, 'unique') !== false) {
        $keep[$name] = $columns;
        continue;
    }

    // Look for duplicates (indexes with similar names)
    $baseName = preg_replace('/_index$|_foreign$/', '', $name);

    if (isset($duplicates[$baseName])) {
        echo "⚠️ Duplicate found: $name (duplicate of {$duplicates[$baseName]})\n";
    } else {
        $duplicates[$baseName] = $name;
        $keep[$name] = $columns;
    }
}

echo "\n🗑️ Indexes to remove:\n";
$toRemove = array_diff(array_keys($indexGroups), array_keys($keep));

if (empty($toRemove)) {
    echo "  No obvious duplicates found.\n";
    echo "  Manual review needed for optimization.\n\n";
} else {
    foreach ($toRemove as $indexName) {
        echo "  - $indexName\n";
    }

    echo "\n❓ Remove these indexes? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);

    if (trim($line) === 'y') {
        echo "\n🔧 Removing duplicate indexes...\n";

        foreach ($toRemove as $indexName) {
            try {
                DB::statement("ALTER TABLE calls DROP INDEX `$indexName`");
                echo "  ✅ Removed: $indexName\n";
            } catch (\Exception $e) {
                echo "  ❌ Failed to remove $indexName: " . $e->getMessage() . "\n";
            }
        }

        echo "\n✅ Cleanup complete!\n";
    } else {
        echo "\n❌ Cleanup cancelled.\n";
    }
}

// Show recommendations
echo "\n💡 Recommendations:\n";
echo "  1. Review all indexes for actual usage\n";
echo "  2. Consider composite indexes instead of multiple single-column indexes\n";
echo "  3. Remove indexes on rarely-queried columns\n";
echo "  4. Use EXPLAIN on slow queries to identify needed indexes\n\n";

// Final count
$newIndexes = DB::select('SHOW INDEX FROM calls');
echo "📊 Final Status:\n";
echo "  - Total indexes: " . count($newIndexes) . " (Max: 64)\n";

if (count($newIndexes) > 64) {
    echo "  ⚠️ Still over limit! Manual intervention required.\n";
} else {
    echo "  ✅ Within limits!\n";
}

echo "\n🎯 Next Steps:\n";
echo "  1. Run performance tests to identify truly needed indexes\n";
echo "  2. Create migration to add only critical indexes\n";
echo "  3. Monitor query performance with Laravel Telescope\n";