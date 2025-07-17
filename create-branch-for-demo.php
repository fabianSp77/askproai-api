<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "🏢 Creating Branch for Demo GmbH\n";
echo "================================\n\n";

// Check if branch already exists for company 16
$existingBranch = DB::table('branches')->where('company_id', 16)->first();

if ($existingBranch) {
    echo "✅ Branch already exists:\n";
    echo "   - ID: {$existingBranch->id}\n";
    echo "   - Name: {$existingBranch->name}\n";
    echo "   - Active: " . ($existingBranch->is_active ? 'YES' : 'NO') . "\n";
    
    if (!$existingBranch->is_active) {
        DB::table('branches')->where('id', $existingBranch->id)->update(['is_active' => 1]);
        echo "   ✅ Activated branch\n";
    }
} else {
    echo "Creating new branch...\n";
    
    $branchId = Str::uuid();
    $branchData = [
        'id' => $branchId,
        'uuid' => $branchId, // Same as ID
        'company_id' => 16,
        'name' => 'Demo GmbH Hauptfiliale',
        'slug' => 'demo-hauptfiliale',
        'city' => 'Berlin',
        'phone_number' => '+49 30 12345678',
        'notification_email' => 'demo@askproai.de',
        'active' => 1,
        'is_active' => 1,
        'address' => 'Musterstraße 123',
        'postal_code' => '10115',
        'country' => 'Deutschland',
        'calendar_mode' => 'inherit',
        'invoice_recipient' => 0,
        'accepts_walkins' => 0,
        'parking_available' => 1,
        'service_radius_km' => 0,
        'created_at' => now(),
        'updated_at' => now()
    ];
    
    try {
        DB::table('branches')->insert($branchData);
        echo "✅ Created branch successfully!\n";
        echo "   - ID: $branchId\n";
        echo "   - Name: Demo GmbH Hauptfiliale\n";
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Verify
$branch = DB::table('branches')->where('company_id', 16)->first();
if ($branch) {
    echo "\n✅ Branch verified:\n";
    echo "   - ID: {$branch->id}\n";
    echo "   - Name: {$branch->name}\n";
    echo "   - City: {$branch->city}\n";
    echo "   - Phone: {$branch->phone_number}\n";
}