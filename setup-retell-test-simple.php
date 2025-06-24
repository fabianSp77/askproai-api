#!/usr/bin/env php
<?php
/**
 * Simple Setup for Retell Test - Works with existing company
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "SIMPLE RETELL TEST SETUP\n";
echo "========================================\n";

// Find existing company
$company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('is_active', true)
    ->first();

if (!$company) {
    echo "❌ No active company found! Please create a company first.\n";
    exit(1);
}

echo "✅ Using company: {$company->name} (ID: {$company->id})\n";

// Set tenant context
app()->instance('current_company', $company);
app()->bind('tenant.company_id', function() use ($company) {
    return $company->id;
});

// Find branch with phone number
$branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->where('phone_number', '+49 30 837 93 369')
    ->first();

if (!$branch) {
    echo "❌ No branch found with phone number +49 30 837 93 369\n";
    
    // Find any active branch
    $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->first();
        
    if ($branch) {
        echo "✅ Using branch: {$branch->name}\n";
        
        // Update phone number
        $branch->update(['phone_number' => '+49 30 837 93 369']);
        echo "✅ Updated branch phone number to: +49 30 837 93 369\n";
    }
} else {
    echo "✅ Using branch: {$branch->name}\n";
}

// Check/create phone number record
$phoneNumber = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('number', '+49 30 837 93 369')
    ->first();

if (!$phoneNumber && $branch) {
    $phoneNumber = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)->create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'number' => '+49 30 837 93 369',
        'type' => 'main',
        'is_active' => true,
        'retell_agent_id' => 'agent_test123'
    ]);
    echo "✅ Created phone number record\n";
} elseif ($phoneNumber) {
    echo "✅ Phone number record exists\n";
}

echo "\n========================================\n";
echo "SETUP COMPLETE\n";
echo "========================================\n";
echo "\nTest Configuration:\n";
echo "- Company: {$company->name} (ID: {$company->id})\n";
if ($branch) {
    echo "- Branch: {$branch->name} (ID: {$branch->id})\n";
}
echo "- Phone: +49 30 837 93 369\n";
echo "\n⚠️  Note: Make sure this phone number is configured in Retell!\n";
echo "\n✅ Ready for testing\n\n";