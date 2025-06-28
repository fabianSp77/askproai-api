<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking database structure...\n\n";

// Check branches table
echo "BRANCHES TABLE:\n";
$branch = DB::table('branches')->first();
if ($branch) {
    echo "Sample branch:\n";
    echo "- ID: " . $branch->id . " (Type: " . gettype($branch->id) . ")\n";
    echo "- Name: " . ($branch->name ?? 'N/A') . "\n\n";
}

// Check appointments table structure
echo "APPOINTMENTS TABLE STRUCTURE:\n";
$columns = DB::select("SHOW COLUMNS FROM appointments WHERE Field IN ('id', 'branch_id', 'company_id', 'customer_id', 'staff_id', 'service_id')");
foreach ($columns as $column) {
    echo "- {$column->Field}: {$column->Type}\n";
}

echo "\n";