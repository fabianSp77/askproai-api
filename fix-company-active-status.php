<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\PortalUser;

echo "\n🔧 FIXING COMPANY ACTIVE STATUS\n";
echo "================================\n\n";

// Get Demo GmbH company
$company = Company::find(16);

if (!$company) {
    echo "❌ Company ID 16 not found!\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Current status: " . ($company->is_active ? 'ACTIVE' : 'INACTIVE') . "\n";

if (!$company->is_active) {
    echo "\n⚠️  Company is INACTIVE - This prevents login!\n";
    echo "Activating company...\n";
    
    $company->is_active = true;
    $company->save();
    
    echo "✅ Company activated!\n";
} else {
    echo "✅ Company is already active\n";
}

// Also check if there are any other issues
echo "\nChecking for other potential issues:\n";

// Check if company has branches
try {
    // Set company context for TenantScope
    app()->instance('current_company_id', 16);
    $branches = $company->branches()->count();
    echo "- Branches: $branches\n";
} catch (\Exception $e) {
    echo "- Branches: Unable to count (TenantScope issue)\n";
}

// Check if company has valid subscription/credits
if (isset($company->prepaid_balance)) {
    echo "- Prepaid balance: €" . number_format($company->prepaid_balance, 2) . "\n";
}

// Check all portal users for this company
$users = PortalUser::where('company_id', 16)->get();
echo "\nPortal users for this company:\n";
foreach ($users as $user) {
    echo "- {$user->email} (Active: " . ($user->is_active ? 'YES' : 'NO') . ")\n";
}

echo "\n✅ Company status fixed!\n";