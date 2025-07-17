<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔍 Checking branches table structure\n";
echo "===================================\n\n";

// Check if table exists
if (!Schema::hasTable('branches')) {
    echo "❌ Table 'branches' does not exist!\n";
    exit(1);
}

echo "✅ Table 'branches' exists\n\n";

// Get columns
$columns = DB::select("SHOW COLUMNS FROM branches");
echo "Columns in branches table:\n";
foreach ($columns as $column) {
    echo "  - {$column->Field} ({$column->Type})" . ($column->Null == 'NO' ? ' NOT NULL' : '') . "\n";
}

// Check existing branches
echo "\nExisting branches:\n";
$branches = DB::table('branches')->get();
if ($branches->isEmpty()) {
    echo "  No branches found\n";
} else {
    foreach ($branches as $branch) {
        echo "  - ID: {$branch->id}, Name: {$branch->name}, Company: {$branch->company_id}\n";
    }
}

// Create a proper branch
echo "\n🔧 Creating branch with correct fields...\n";

try {
    // First, let's see what fields are available
    $sampleBranch = DB::table('branches')->first();
    if ($sampleBranch) {
        echo "\nSample branch structure:\n";
        foreach ((array)$sampleBranch as $key => $value) {
            echo "  - $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }
    
    // Create minimal branch with only required fields
    $branchData = [
        'company_id' => 16,
        'name' => 'Hauptfiliale Berlin',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ];
    
    // Check which optional fields exist
    $optionalFields = ['address', 'city', 'postal_code', 'country', 'timezone'];
    foreach ($optionalFields as $field) {
        if (Schema::hasColumn('branches', $field)) {
            switch($field) {
                case 'address':
                    $branchData[$field] = 'Musterstraße 1';
                    break;
                case 'city':
                    $branchData[$field] = 'Berlin';
                    break;
                case 'postal_code':
                    $branchData[$field] = '10115';
                    break;
                case 'country':
                    $branchData[$field] = 'DE';
                    break;
                case 'timezone':
                    $branchData[$field] = 'Europe/Berlin';
                    break;
            }
        }
    }
    
    $branchId = DB::table('branches')->insertGetId($branchData);
    echo "✅ Created branch with ID: $branchId\n";
    
} catch (\Exception $e) {
    echo "❌ Error creating branch: " . $e->getMessage() . "\n";
}