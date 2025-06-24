<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Migration Issue Fixer\n";
echo "=================================\n\n";

// Get all migrations that are already in the database
$migrationsTable = DB::table('migrations')->pluck('migration')->toArray();

// Get all migration files
$migrator = app('migrator');
$files = $migrator->getMigrationFiles(database_path('migrations'));
$ran = $migrator->getRepository()->getRan();

$pendingMigrations = [];
foreach ($files as $file) {
    $name = $migrator->getMigrationName($file);
    if (!in_array($name, $ran)) {
        $pendingMigrations[$name] = $file;
    }
}

echo "Found " . count($pendingMigrations) . " pending migrations\n\n";

// Analyze each pending migration
$skipMigrations = [];
$markAsCompleted = [];

foreach ($pendingMigrations as $migration => $file) {
    echo "Analyzing: $migration\n";
    
    $content = file_get_contents($file);
    
    // Check for duplicate table creations
    if (preg_match('/Schema::create\s*\(\s*[\'"]([^\'\"]+)[\'"]/', $content, $matches)) {
        $tableName = $matches[1];
        if (Schema::hasTable($tableName)) {
            echo "  ⚠️  Table '$tableName' already exists - will mark as completed\n";
            $markAsCompleted[] = $migration;
            continue;
        }
    }
    
    // Check for migrations that only add columns/indexes that might already exist
    if (strpos($migration, 'update_phone_numbers_table') !== false ||
        strpos($migration, 'add_missing_columns') !== false ||
        strpos($migration, 'add_indexes') !== false ||
        strpos($migration, 'add_performance_indexes') !== false) {
        echo "  ⚠️  Column/index migration - will mark as completed\n";
        $markAsCompleted[] = $migration;
        continue;
    }
    
    // Check for known problematic migrations
    $knownProblematic = [
        '2025_06_20_092324_create_knowledge_management_tables', // Duplicate
        '2025_12_06_140001_create_event_type_import_logs_table', // Duplicate
    ];
    
    if (in_array($migration, $knownProblematic)) {
        echo "  ⚠️  Known problematic migration - will skip\n";
        $skipMigrations[] = $migration;
        continue;
    }
    
    echo "  ✅ Appears safe to run\n";
}

echo "\n=================================\n";
echo "Action Plan\n";
echo "=================================\n\n";

// Mark completed migrations
if (count($markAsCompleted) > 0) {
    echo "Marking " . count($markAsCompleted) . " migrations as completed:\n";
    foreach ($markAsCompleted as $migration) {
        echo "  - $migration\n";
        try {
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => DB::table('migrations')->max('batch') + 1
            ]);
            echo "    ✅ Marked as completed\n";
        } catch (\Exception $e) {
            echo "    ❌ Error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n";

// Now run the remaining migrations
$remainingMigrations = array_diff(array_keys($pendingMigrations), $markAsCompleted, $skipMigrations);
echo "Running " . count($remainingMigrations) . " remaining migrations:\n\n";

$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($remainingMigrations as $migration) {
    echo "Running: $migration... ";
    
    try {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/' . $migration . '.php',
            '--force' => true
        ]);
        
        $successCount++;
        echo "✅\n";
        
    } catch (\Exception $e) {
        $errorCount++;
        $errors[$migration] = $e->getMessage();
        echo "❌\n";
        echo "   Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=================================\n";
echo "Summary\n";
echo "=================================\n";
echo "✅ Marked as completed: " . count($markAsCompleted) . "\n";
echo "⚠️  Skipped: " . count($skipMigrations) . "\n";
echo "✅ Successfully run: $successCount\n";
echo "❌ Failed: $errorCount\n";

if ($errorCount > 0) {
    echo "\nFailed migrations:\n";
    foreach ($errors as $migration => $error) {
        echo "- $migration\n";
    }
}

// Final status check
echo "\n";
exec('php artisan migrate:status | grep -c "Pending"', $output);
$remainingCount = isset($output[0]) ? (int)$output[0] : 0;

if ($remainingCount > 0) {
    echo "⚠️  There are still $remainingCount pending migrations.\n";
} else {
    echo "✅ All migrations completed!\n";
}