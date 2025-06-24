<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Safe Migration Runner\n";
echo "=================================\n\n";

// Step 1: Create database backup
echo "Step 1: Creating database backup...\n";
$backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$dbName = config('database.connections.mysql.database');
$dbUser = config('database.connections.mysql.username');
$dbPass = config('database.connections.mysql.password');
$dbHost = config('database.connections.mysql.host');

$command = sprintf(
    'mysqldump -h%s -u%s -p%s %s > storage/backups/%s 2>/dev/null',
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    escapeshellarg($dbPass),
    escapeshellarg($dbName),
    $backupFile
);

// Create backup directory if it doesn't exist
if (!is_dir(storage_path('backups'))) {
    mkdir(storage_path('backups'), 0755, true);
}

exec($command, $output, $returnCode);
if ($returnCode === 0) {
    echo "✅ Backup created: storage/backups/$backupFile\n\n";
} else {
    echo "❌ Failed to create backup. Aborting.\n";
    exit(1);
}

// Step 2: Get pending migrations
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

echo "Step 2: Found " . count($pendingMigrations) . " pending migrations\n\n";

// Step 3: Identify problematic migrations
$skipMigrations = [];
$existingTables = array_map(function($table) {
    return $table->{'Tables_in_' . DB::getDatabaseName()};
}, DB::select('SHOW TABLES'));

// Check for duplicate table creations
$duplicates = [
    '2025_06_20_092324_create_knowledge_management_tables', // Duplicate of 084131
    '2025_12_06_140001_create_event_type_import_logs_table', // Duplicate
];

// Check for already existing tables
$tableChecks = [
    '2025_06_19_create_tax_compliance_tables_safe' => ['tax_rates'],
    '2025_06_20_create_mcp_metrics_table' => ['mcp_metrics'],
    '2025_06_20_create_notifications_table' => ['notifications'],
    '2025_06_21_create_event_type_import_logs_table' => ['event_type_import_logs'],
    '2025_06_22_140000_update_staff_event_types_for_uuid' => ['staff_event_types'],
];

foreach ($tableChecks as $migration => $tables) {
    foreach ($tables as $table) {
        if (in_array($table, $existingTables)) {
            $skipMigrations[] = $migration;
            echo "⚠️  Skipping $migration - table '$table' already exists\n";
        }
    }
}

// Add duplicates to skip list
foreach ($duplicates as $duplicate) {
    $skipMigrations[] = $duplicate;
    echo "⚠️  Skipping $duplicate - duplicate migration\n";
}

echo "\n";

// Step 4: Run safe migrations one by one
$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($pendingMigrations as $migration => $file) {
    if (in_array($migration, $skipMigrations)) {
        continue;
    }
    
    echo "Running: $migration... ";
    
    try {
        DB::beginTransaction();
        
        // Run the migration
        $migrator->run([database_path('migrations')], [
            'pretend' => false,
            'step' => true,
            'path' => database_path('migrations'),
            'migration' => $migration
        ]);
        
        // Mark as ran manually since we're doing it one by one
        $migrator->getRepository()->log($migration, 1);
        
        DB::commit();
        $successCount++;
        echo "✅\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        $errorCount++;
        $errors[$migration] = $e->getMessage();
        echo "❌\n";
        echo "   Error: " . $e->getMessage() . "\n";
        
        // Check if it's a "table already exists" error
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "   Marking as completed anyway...\n";
            $migrator->getRepository()->log($migration, 1);
        }
    }
}

// Step 5: Summary
echo "\n=================================\n";
echo "Migration Summary\n";
echo "=================================\n";
echo "✅ Successful: $successCount\n";
echo "❌ Failed: $errorCount\n";
echo "⚠️  Skipped: " . count($skipMigrations) . "\n";

if ($errorCount > 0) {
    echo "\nFailed migrations:\n";
    foreach ($errors as $migration => $error) {
        echo "- $migration: $error\n";
    }
}

echo "\n✅ Database backup saved to: storage/backups/$backupFile\n";

// Final check
$remaining = Artisan::call('migrate:status');
exec('php artisan migrate:status | grep -c Pending', $output);
$remainingCount = isset($output[0]) ? (int)$output[0] : 0;

if ($remainingCount > 0) {
    echo "\n⚠️  There are still $remainingCount pending migrations.\n";
    echo "Run 'php artisan migrate:status' to see them.\n";
} else {
    echo "\n✅ All migrations completed!\n";
}