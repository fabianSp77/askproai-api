<?php
/**
 * Check services table schema
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== SERVICES TABLE SCHEMA ===\n\n";

// Get all columns
$columns = DB::select("SHOW COLUMNS FROM services");

echo "Columns in services table:\n\n";

foreach ($columns as $column) {
    echo "{$column->Field}:\n";
    echo "  Type: {$column->Type}\n";
    echo "  Null: {$column->Null}\n";
    echo "  Default: " . ($column->Default ?? 'NULL') . "\n";
    echo "\n";
}

echo "=== CHECK IF DURATION_MINUTES EXISTS ===\n\n";

$hasDuration = Schema::hasColumn('services', 'duration');
$hasDurationMinutes = Schema::hasColumn('services', 'duration_minutes');

echo "has 'duration' column: " . ($hasDuration ? 'YES' : 'NO') . "\n";
echo "has 'duration_minutes' column: " . ($hasDurationMinutes ? 'YES' : 'NO') . "\n";

echo "\n=== END SCHEMA CHECK ===\n";
