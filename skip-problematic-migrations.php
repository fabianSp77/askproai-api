#!/usr/bin/php
<?php

/**
 * Skip problematic migrations that have foreign key issues
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Skipping Problematic Migrations ===\n\n";

$problematicMigrations = [
    '2025_06_18_create_dashboard_metrics_tables',
    '2025_06_18_create_dashboard_metrics_tables_simple',
    '2025_06_18_create_dashboard_performance_indexes',
    '2025_06_18_create_validation_results_table',
    '2025_06_19_add_critical_performance_indexes_v2',
    '2025_06_19_add_critical_performance_indexes',
];

foreach ($problematicMigrations as $migration) {
    $exists = DB::table('migrations')->where('migration', $migration)->exists();
    if (!$exists) {
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => 999
        ]);
        echo "✓ Skipped: $migration\n";
    }
}

echo "\nDone. Run 'php artisan migrate --force' again.\n";