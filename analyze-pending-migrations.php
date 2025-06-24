<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Migration Analysis Report\n";
echo "=================================\n\n";

// Get pending migrations directly from Laravel's migrator
$migrator = app('migrator');
$files = $migrator->getMigrationFiles(database_path('migrations'));
$ran = $migrator->getRepository()->getRan();

$migrations = [];
foreach ($files as $file) {
    $name = $migrator->getMigrationName($file);
    if (!in_array($name, $ran)) {
        $migrations[] = $name;
    }
}

echo "Total pending migrations: " . count($migrations) . "\n\n";

// Categorize migrations
$categories = [
    'table_creation' => [],
    'column_addition' => [],
    'index_creation' => [],
    'data_migration' => [],
    'dangerous' => [],
    'unknown' => []
];

foreach ($migrations as $migration) {
    $path = database_path('migrations/' . $migration . '.php');
    if (!file_exists($path)) {
        $categories['unknown'][] = $migration;
        continue;
    }
    
    $content = file_get_contents($path);
    
    // Categorize based on content
    if (preg_match('/Schema::create\s*\(/', $content)) {
        $categories['table_creation'][] = $migration;
    } elseif (preg_match('/\->addColumn|table\->.*\(.*\)/', $content)) {
        $categories['column_addition'][] = $migration;
    } elseif (preg_match('/\->index\(|addIndex/', $content)) {
        $categories['index_creation'][] = $migration;
    } elseif (preg_match('/DB::|Model::/', $content)) {
        $categories['data_migration'][] = $migration;
    }
    
    // Check for dangerous operations
    if (preg_match('/dropColumn|dropTable|dropIndex|DB::raw|whereRaw/', $content)) {
        $categories['dangerous'][] = $migration;
    }
}

// Display categorized results
echo "Migration Categories:\n";
echo "====================\n\n";

echo "✅ Safe - Table Creation (" . count($categories['table_creation']) . "):\n";
foreach ($categories['table_creation'] as $m) echo "   - $m\n";

echo "\n✅ Safe - Column Addition (" . count($categories['column_addition']) . "):\n";
foreach ($categories['column_addition'] as $m) echo "   - $m\n";

echo "\n✅ Safe - Index Creation (" . count($categories['index_creation']) . "):\n";
foreach ($categories['index_creation'] as $m) echo "   - $m\n";

echo "\n⚠️  Careful - Data Migration (" . count($categories['data_migration']) . "):\n";
foreach ($categories['data_migration'] as $m) echo "   - $m\n";

echo "\n❌ Dangerous - Review Required (" . count($categories['dangerous']) . "):\n";
foreach ($categories['dangerous'] as $m) echo "   - $m\n";

echo "\n❓ Unknown (" . count($categories['unknown']) . "):\n";
foreach ($categories['unknown'] as $m) echo "   - $m\n";

// Check for duplicate table creations
echo "\n\nChecking for potential conflicts:\n";
echo "=================================\n";

$tableCreations = [];
foreach ($categories['table_creation'] as $migration) {
    $content = file_get_contents(database_path('migrations/' . $migration . '.php'));
    preg_match_all('/Schema::create\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
    foreach ($matches[1] as $table) {
        if (!isset($tableCreations[$table])) {
            $tableCreations[$table] = [];
        }
        $tableCreations[$table][] = $migration;
    }
}

$conflicts = array_filter($tableCreations, fn($migrations) => count($migrations) > 1);
if (count($conflicts) > 0) {
    echo "⚠️  Found potential table creation conflicts:\n";
    foreach ($conflicts as $table => $migrations) {
        echo "   Table '$table' created in:\n";
        foreach ($migrations as $m) echo "      - $m\n";
    }
} else {
    echo "✅ No table creation conflicts found.\n";
}

// Check existing tables
echo "\n\nChecking against existing tables:\n";
echo "=================================\n";

$existingTables = array_map(function($table) {
    return $table->{'Tables_in_' . DB::getDatabaseName()};
}, DB::select('SHOW TABLES'));
$newTables = array_keys($tableCreations);
$alreadyExist = array_intersect($newTables, $existingTables);

if (count($alreadyExist) > 0) {
    echo "⚠️  Tables that already exist:\n";
    foreach ($alreadyExist as $table) {
        echo "   - $table (in " . implode(', ', $tableCreations[$table]) . ")\n";
    }
} else {
    echo "✅ No conflicts with existing tables.\n";
}

echo "\n\nRecommendation:\n";
echo "===============\n";

$dangerCount = count($categories['dangerous']);
$conflictCount = count($conflicts) + count($alreadyExist);

if ($dangerCount > 0 || $conflictCount > 0) {
    echo "⚠️  CAREFUL: Found $dangerCount dangerous migrations and $conflictCount conflicts.\n";
    echo "   1. Create a database backup first: php artisan backup:run --only-db\n";
    echo "   2. Review dangerous migrations manually\n";
    echo "   3. Run migrations in batches: php artisan migrate --step\n";
} else {
    echo "✅ Migrations appear safe to run.\n";
    echo "   1. Create a database backup: php artisan backup:run --only-db\n";
    echo "   2. Run migrations: php artisan migrate --force\n";
}

echo "\n";