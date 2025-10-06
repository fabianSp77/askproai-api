#!/usr/bin/env php
<?php

/**
 * Index Analysis and Cleanup Script
 * Problem: calls table has 93 index entries, many redundant
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Index Analysis and Cleanup Tool\n";
echo "=====================================\n\n";

// Get all indexes
$indexes = DB::select('SHOW INDEX FROM calls');
$indexMap = [];
$columnUsage = [];

foreach ($indexes as $index) {
    $indexMap[$index->Key_name][] = $index->Column_name;
    $columnUsage[$index->Column_name][] = $index->Key_name;
}

echo "📊 Current Analysis:\n";
echo "  - Total index entries: " . count($indexes) . "\n";
echo "  - Unique index names: " . count($indexMap) . "\n\n";

// Find problematic columns
echo "⚠️  Columns with excessive indexes:\n";
foreach ($columnUsage as $column => $indexNames) {
    if (count($indexNames) > 3) {
        echo "  - $column: " . count($indexNames) . " indexes\n";
        foreach ($indexNames as $idx) {
            echo "      → $idx\n";
        }
    }
}

// Identify redundant indexes
$toKeep = [];
$toRemove = [];

// Always keep these
$essentialIndexes = [
    'PRIMARY',
    'calls_customer_id_foreign',
    'calls_company_id_foreign',
    'calls_appointment_id_foreign',
    'calls_staff_id_foreign',
    'calls_retell_call_id_unique'
];

foreach ($indexMap as $indexName => $columns) {
    // Keep essential indexes
    if (in_array($indexName, $essentialIndexes)) {
        $toKeep[$indexName] = $columns;
        continue;
    }

    // Keep UNIQUE constraints
    if (strpos($indexName, '_unique') !== false) {
        $toKeep[$indexName] = $columns;
        continue;
    }

    // For single column indexes on company_id, keep only one
    if (count($columns) == 1 && $columns[0] == 'company_id') {
        if (!isset($toKeep['idx_calls_company_id'])) {
            $toKeep['idx_calls_company_id'] = $columns;
        } else {
            $toRemove[] = $indexName;
        }
        continue;
    }

    // For single column indexes on created_at, keep only one
    if (count($columns) == 1 && $columns[0] == 'created_at') {
        if (!isset($toKeep['idx_calls_created_at'])) {
            $toKeep['idx_calls_created_at'] = $columns;
        } else {
            $toRemove[] = $indexName;
        }
        continue;
    }

    // For composite indexes, check if they're useful
    if (count($columns) == 2) {
        $composite = implode('_', $columns);
        // Keep useful composites
        if (in_array($composite, [
            'customer_id_created_at',
            'company_id_created_at',
            'status_created_at'
        ])) {
            $toKeep[$indexName] = $columns;
        } else {
            $toRemove[] = $indexName;
        }
        continue;
    }

    // Remove indexes with more than 2 columns (usually not efficient)
    if (count($columns) > 2) {
        $toRemove[] = $indexName;
        continue;
    }

    // Default: mark for review
    $toRemove[] = $indexName;
}

echo "\n✅ Indexes to KEEP (" . count($toKeep) . "):\n";
foreach ($toKeep as $name => $cols) {
    echo "  - $name (" . implode(', ', $cols) . ")\n";
}

echo "\n🗑️  Indexes to REMOVE (" . count($toRemove) . "):\n";
foreach ($toRemove as $name) {
    echo "  - $name (" . implode(', ', $indexMap[$name]) . ")\n";
}

echo "\n📈 Expected improvement:\n";
$currentCount = count($indexes);
$afterCount = count($toKeep);
$reduction = $currentCount - $afterCount;
echo "  - Current: $currentCount index entries\n";
echo "  - After cleanup: ~$afterCount index entries\n";
echo "  - Reduction: $reduction entries (" . round($reduction / $currentCount * 100) . "%)\n";

if (count($toRemove) > 0) {
    echo "\n⚠️  This will remove " . count($toRemove) . " indexes.\n";
    echo "❓ Proceed with cleanup? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);

    if (trim($line) === 'yes') {
        echo "\n🔧 Removing redundant indexes...\n\n";

        $success = 0;
        $failed = 0;

        foreach ($toRemove as $indexName) {
            try {
                echo "Removing $indexName... ";
                DB::statement("ALTER TABLE calls DROP INDEX `$indexName`");
                echo "✅\n";
                $success++;
            } catch (\Exception $e) {
                echo "❌ " . $e->getMessage() . "\n";
                $failed++;
            }
        }

        echo "\n✅ Cleanup complete!\n";
        echo "  - Removed: $success indexes\n";
        if ($failed > 0) {
            echo "  - Failed: $failed indexes\n";
        }

        // Verify final count
        $finalIndexes = DB::select('SHOW INDEX FROM calls');
        echo "\n📊 Final status:\n";
        echo "  - Total indexes: " . count($finalIndexes) . "\n";

        if (count($finalIndexes) <= 64) {
            echo "  ✅ Within MySQL limit!\n";
        } else {
            echo "  ⚠️  Still over limit. Manual review needed.\n";
        }
    } else {
        echo "\n❌ Cleanup cancelled.\n";
    }
} else {
    echo "\n✅ No redundant indexes found!\n";
}

echo "\n💡 Performance test command:\n";
echo "php artisan tinker --execute=\"\\\$start = microtime(true); App\\Models\\Call::with('customer')->take(1000)->get(); echo 'Query time: ' . round((microtime(true) - \\\$start) * 1000, 2) . 'ms';\";\n";