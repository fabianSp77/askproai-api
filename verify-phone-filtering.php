<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "=== Phone Number Filtering Verification ===\n\n";

// Get all companies with phone numbers
$companies = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereHas('phoneNumbers', function($q) {
        $q->withoutGlobalScope(\App\Scopes\TenantScope::class);
    })
    ->get();

foreach ($companies as $company) {
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo str_repeat('-', 50) . "\n";
    
    // Get all phone numbers for this company
    $phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->pluck('number')
        ->toArray();
    
    echo "Phone Numbers:\n";
    foreach ($phoneNumbers as $num) {
        echo "  - $num\n";
    }
    
    // Check calls for each phone number
    foreach ($phoneNumbers as $phoneNumber) {
        $callCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('to_number', $phoneNumber)
            ->count();
        
        $correctCompanyCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('to_number', $phoneNumber)
            ->where('company_id', $company->id)
            ->count();
        
        $wrongCompanyCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('to_number', $phoneNumber)
            ->where('company_id', '!=', $company->id)
            ->count();
        
        echo "\n  Phone: $phoneNumber\n";
        echo "    Total calls: $callCount\n";
        echo "    Correct company_id: $correctCompanyCount\n";
        echo "    Wrong company_id: $wrongCompanyCount";
        
        if ($wrongCompanyCount > 0) {
            echo " ⚠️ DATA ISSUE!";
        }
        echo "\n";
    }
    
    // Check for calls to this company without matching phone numbers
    $callsToWrongNumbers = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereNotIn('to_number', $phoneNumbers)
        ->whereNotNull('to_number')
        ->count();
    
    if ($callsToWrongNumbers > 0) {
        echo "\n  ⚠️ WARNING: {$callsToWrongNumbers} calls assigned to this company with non-company phone numbers!\n";
        
        // Show sample
        $samples = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $company->id)
            ->whereNotIn('to_number', $phoneNumbers)
            ->whereNotNull('to_number')
            ->limit(3)
            ->get(['id', 'to_number', 'created_at']);
        
        foreach ($samples as $sample) {
            echo "    - Call ID {$sample->id} to {$sample->to_number} ({$sample->created_at})\n";
        }
    }
    
    echo "\n";
}

// Summary statistics
echo "\n=== SUMMARY ===\n";

$totalCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->count();
$callsWithCorrectCompany = DB::select("
    SELECT COUNT(*) as count
    FROM calls c
    INNER JOIN phone_numbers p ON c.to_number = p.number AND p.is_active = 1
    WHERE c.company_id = p.company_id
")[0]->count;

$callsWithWrongCompany = DB::select("
    SELECT COUNT(*) as count
    FROM calls c
    INNER JOIN phone_numbers p ON c.to_number = p.number AND p.is_active = 1
    WHERE c.company_id != p.company_id
")[0]->count;

$callsToUnregisteredNumbers = DB::select("
    SELECT COUNT(*) as count
    FROM calls c
    LEFT JOIN phone_numbers p ON c.to_number = p.number AND p.is_active = 1
    WHERE p.id IS NULL AND c.to_number IS NOT NULL
")[0]->count;

echo "Total calls in system: $totalCalls\n";
echo "Calls with correct company_id: $callsWithCorrectCompany\n";
echo "Calls with WRONG company_id: $callsWithWrongCompany\n";
echo "Calls to unregistered numbers: $callsToUnregisteredNumbers\n";

if ($callsWithWrongCompany > 0 || $callsToUnregisteredNumbers > 0) {
    echo "\n⚠️ DATA INTEGRITY ISSUES DETECTED!\n";
    echo "Run fix-call-company-assignment.php to correct these issues.\n";
} else {
    echo "\n✅ All calls have correct company assignments!\n";
}