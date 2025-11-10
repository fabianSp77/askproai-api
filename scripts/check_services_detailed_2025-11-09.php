<?php
/**
 * Check service configuration in detail
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';

echo "=== SERVICE CONFIGURATION CHECK ===\n\n";

echo "1. Checking services table...\n";

$services = DB::table('services')
    ->where('branch_id', $branchId)
    ->select('id', 'name', 'duration', 'price', 'is_active', 'calcom_event_type_id')
    ->get();

echo "   Total services found: " . $services->count() . "\n\n";

foreach ($services as $service) {
    echo "   Service: {$service->name}\n";
    echo "     ID: {$service->id}\n";
    echo "     Duration: {$service->duration} min\n";
    echo "     Price: €{$service->price}\n";
    echo "     Active: " . ($service->is_active ? 'YES' : 'NO') . "\n";
    echo "     Cal.com Event Type: " . ($service->calcom_event_type_id ?? 'NOT SET') . "\n";
    echo "\n";
}

// Check if duration and price columns exist and have correct type
echo "2. Checking table structure...\n";

$columns = DB::select("DESCRIBE services");

echo "   Relevant columns:\n";
foreach ($columns as $column) {
    if (in_array($column->Field, ['duration', 'price', 'name', 'is_active'])) {
        echo "     {$column->Field}: {$column->Type} (NULL: {$column->Null}, Default: " . ($column->Default ?? 'NULL') . ")\n";
    }
}

echo "\n=== END CHECK ===\n";
