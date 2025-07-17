#!/usr/bin/env php
<?php
/**
 * Fix Phone Number to Branch Mapping
 * 
 * This script fixes missing branch_id in calls by:
 * 1. Checking phone_numbers table
 * 2. Fixing format mismatches
 * 3. Updating orphaned calls
 * 
 * Error Code: RETELL_003
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Branch;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\DB;

echo "🔧 Phone Number Mapping Fix Script\n";
echo "==================================\n\n";

try {
    // Step 1: Check calls without branch_id
    echo "1. Checking calls without branch_id...\n";
    $orphanedCalls = Call::whereNull('branch_id')->count();
    
    if ($orphanedCalls === 0) {
        echo "   ✅ No orphaned calls found!\n";
    } else {
        echo "   ⚠️  Found {$orphanedCalls} calls without branch_id\n";
    }

    // Step 2: Check phone_numbers table
    echo "\n2. Checking phone_numbers table...\n";
    $phoneNumbers = PhoneNumber::all();
    
    if ($phoneNumbers->isEmpty()) {
        echo "   ❌ No phone numbers configured!\n";
        
        // Try to create from branches
        $branches = Branch::whereNotNull('phone_number')->get();
        foreach ($branches as $branch) {
            if ($branch->phone_number) {
                PhoneNumber::create([
                    'number' => $branch->phone_number,
                    'branch_id' => $branch->id,
                    'company_id' => $branch->company_id,
                    'is_active' => true,
                ]);
                echo "   ✅ Created phone number mapping for {$branch->phone_number} → {$branch->name}\n";
            }
        }
        $phoneNumbers = PhoneNumber::all();
    } else {
        echo "   ✅ Found {$phoneNumbers->count()} phone number mappings\n";
    }

    // Step 3: Check for phone numbers without branch_id
    echo "\n3. Checking for incomplete mappings...\n";
    $incompleteMappings = PhoneNumber::whereNull('branch_id')->get();
    
    foreach ($incompleteMappings as $phoneNumber) {
        echo "   ⚠️  Phone {$phoneNumber->number} has no branch_id\n";
        
        // Try to find matching branch
        $branch = Branch::where('phone_number', $phoneNumber->number)
                       ->orWhere('phone_number', 'LIKE', '%' . substr($phoneNumber->number, -10) . '%')
                       ->first();
                       
        if ($branch) {
            $phoneNumber->branch_id = $branch->id;
            $phoneNumber->save();
            echo "   ✅ Fixed: Mapped to branch {$branch->name}\n";
        }
    }

    // Step 4: Fix phone number format issues
    echo "\n4. Checking phone number formats...\n";
    $formats = [
        'with_plus' => 0,
        'without_plus' => 0,
        'with_country' => 0,
        'local_only' => 0,
    ];
    
    foreach ($phoneNumbers as $phoneNumber) {
        if (strpos($phoneNumber->number, '+') === 0) {
            $formats['with_plus']++;
        } else {
            $formats['without_plus']++;
        }
        
        if (strpos($phoneNumber->number, '+49') === 0 || strpos($phoneNumber->number, '49') === 0) {
            $formats['with_country']++;
        } else {
            $formats['local_only']++;
        }
    }
    
    echo "   Phone number formats:\n";
    echo "   - With + prefix: {$formats['with_plus']}\n";
    echo "   - Without + prefix: {$formats['without_plus']}\n";
    echo "   - With country code: {$formats['with_country']}\n";
    echo "   - Local format only: {$formats['local_only']}\n";

    // Step 5: Fix orphaned calls
    echo "\n5. Fixing orphaned calls...\n";
    $fixedCount = 0;
    
    $orphanedCallsQuery = Call::whereNull('branch_id')->limit(100);
    foreach ($orphanedCallsQuery->get() as $call) {
        $phoneToFind = $call->from_number ?? $call->to_number;
        
        if (!$phoneToFind) {
            continue;
        }
        
        // Try multiple format variations
        $variations = [
            $phoneToFind,
            '+' . $phoneToFind,
            ltrim($phoneToFind, '+'),
            substr($phoneToFind, -10),  // Last 10 digits
            substr($phoneToFind, -11),  // Last 11 digits
        ];
        
        foreach ($variations as $variation) {
            $phoneNumber = PhoneNumber::where('number', $variation)
                                     ->orWhere('number', 'LIKE', '%' . $variation . '%')
                                     ->first();
            
            if ($phoneNumber && $phoneNumber->branch_id) {
                $call->branch_id = $phoneNumber->branch_id;
                $call->company_id = $phoneNumber->company_id ?? 
                                   Branch::find($phoneNumber->branch_id)->company_id;
                $call->save();
                $fixedCount++;
                echo "   ✅ Fixed call {$call->call_id} → Branch ID {$phoneNumber->branch_id}\n";
                break;
            }
        }
    }

    // Step 6: Create missing phone number records
    echo "\n6. Creating missing phone number records...\n";
    
    // Get unique phone numbers from calls
    $uniquePhones = DB::table('calls')
        ->select(DB::raw('DISTINCT COALESCE(to_number, from_number) as phone'))
        ->whereNotNull(DB::raw('COALESCE(to_number, from_number)'))
        ->pluck('phone');
    
    $createdCount = 0;
    foreach ($uniquePhones as $phone) {
        if (!PhoneNumber::where('number', $phone)->exists()) {
            // Try to find a branch for this phone
            $branch = Branch::where('phone_number', $phone)
                           ->orWhere('phone_number', 'LIKE', '%' . substr($phone, -10) . '%')
                           ->first();
            
            if ($branch) {
                PhoneNumber::create([
                    'number' => $phone,
                    'branch_id' => $branch->id,
                    'company_id' => $branch->company_id,
                    'is_active' => true,
                ]);
                $createdCount++;
                echo "   ✅ Created mapping: {$phone} → {$branch->name}\n";
            }
        }
    }

    // Summary
    echo "\n✅ Phone mapping fix completed!\n";
    echo "   - Total phone mappings: " . PhoneNumber::count() . "\n";
    echo "   - Fixed orphaned calls: {$fixedCount}\n";
    echo "   - Created new mappings: {$createdCount}\n";
    echo "   - Remaining orphaned calls: " . Call::whereNull('branch_id')->count() . "\n";
    
    if (Call::whereNull('branch_id')->count() > 0) {
        echo "\n⚠️  Some calls still have no branch_id. Manual intervention may be required.\n";
        echo "   Run the following SQL to investigate:\n";
        echo "   SELECT DISTINCT from_number, to_number FROM calls WHERE branch_id IS NULL LIMIT 10;\n";
    }
    
    exit(0);

} catch (\Exception $e) {
    echo "\n❌ Error during fix process: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}