<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\DB;

echo "=== Fix Call Company Assignment ===\n\n";

// First, let's analyze the problem
echo "ANALYSIS:\n";

// Get all phone numbers with their company assignments
$phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('is_active', true)
    ->get(['number', 'company_id', 'branch_id']);

$phoneToCompanyMap = [];
foreach ($phoneNumbers as $phone) {
    $phoneToCompanyMap[$phone->number] = [
        'company_id' => $phone->company_id,
        'branch_id' => $phone->branch_id
    ];
}

// Find calls with mismatched company_id
$mismatchedCalls = 0;
$callsToFix = [];

$allCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('to_number')
    ->get(['id', 'to_number', 'company_id', 'branch_id']);

foreach ($allCalls as $call) {
    if (isset($phoneToCompanyMap[$call->to_number])) {
        $correctCompanyId = $phoneToCompanyMap[$call->to_number]['company_id'];
        if ($call->company_id != $correctCompanyId) {
            $mismatchedCalls++;
            $callsToFix[] = [
                'call_id' => $call->id,
                'to_number' => $call->to_number,
                'current_company_id' => $call->company_id,
                'correct_company_id' => $correctCompanyId,
                'correct_branch_id' => $phoneToCompanyMap[$call->to_number]['branch_id']
            ];
        }
    }
}

echo "Total calls analyzed: " . count($allCalls) . "\n";
echo "Calls with mismatched company_id: $mismatchedCalls\n\n";

if ($mismatchedCalls > 0) {
    echo "Sample of mismatched calls (first 10):\n";
    $sample = array_slice($callsToFix, 0, 10);
    foreach ($sample as $fix) {
        echo "  Call ID {$fix['call_id']}: to_number={$fix['to_number']}, ";
        echo "current_company={$fix['current_company_id']}, ";
        echo "should_be={$fix['correct_company_id']}\n";
    }
    
    echo "\n";
    echo "Would you like to fix these mismatched calls? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) == 'yes') {
        echo "\nFixing mismatched calls...\n";
        
        DB::beginTransaction();
        try {
            $fixed = 0;
            foreach ($callsToFix as $fix) {
                Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('id', $fix['call_id'])
                    ->update([
                        'company_id' => $fix['correct_company_id'],
                        'branch_id' => $fix['correct_branch_id']
                    ]);
                $fixed++;
                
                if ($fixed % 100 == 0) {
                    echo "  Fixed $fixed calls...\n";
                }
            }
            
            DB::commit();
            echo "\nSuccessfully fixed $fixed calls!\n";
            
            // Verify the fix
            echo "\nVerifying fix...\n";
            $remainingMismatched = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->whereRaw('company_id != (SELECT company_id FROM phone_numbers WHERE phone_numbers.number = calls.to_number AND phone_numbers.is_active = 1 LIMIT 1)')
                ->count();
            
            echo "Remaining mismatched calls: $remainingMismatched\n";
            
        } catch (\Exception $e) {
            DB::rollback();
            echo "Error: " . $e->getMessage() . "\n";
            echo "Transaction rolled back.\n";
        }
    } else {
        echo "Fix cancelled.\n";
    }
} else {
    echo "No mismatched calls found. All calls have correct company_id assignments.\n";
}

// Check specific case
echo "\n=== Checking Specific Case ===\n";
$krueckebergCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', 1)
    ->whereBetween('created_at', ['2025-07-01', '2025-07-03'])
    ->count();

echo "Calls for Krückeberg (Company ID 1) in date range: $krueckebergCalls\n";