<?php

/**
 * Fix Database Schema Issues
 *
 * Problems:
 * 1. calls table: company_id has no default value
 * 2. retell_call_sessions table: branch_id column doesn't exist
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "🔧 DATABASE SCHEMA FIX\n";
echo str_repeat('=', 80) . "\n\n";

try {
    // FIX 1: calls table - add default value for company_id
    echo "1. Checking calls table...\n";

    $callsColumns = Schema::getColumnListing('calls');

    if (in_array('company_id', $callsColumns)) {
        // Check if it's nullable or has default
        $companyIdInfo = DB::select("SHOW COLUMNS FROM calls WHERE Field = 'company_id'")[0];

        echo "   Current company_id: {$companyIdInfo->Type}, Null={$companyIdInfo->Null}, Default={$companyIdInfo->Default}\n";

        if ($companyIdInfo->Null === 'NO' && $companyIdInfo->Default === null) {
            echo "   ❌ company_id is NOT NULL with no default\n";
            echo "   ✅ Making company_id nullable...\n";

            DB::statement('ALTER TABLE calls MODIFY company_id BIGINT UNSIGNED NULL');

            echo "   ✅ Fixed: company_id is now nullable\n\n";
        } else {
            echo "   ✅ company_id is already nullable or has default\n\n";
        }
    } else {
        echo "   ⚠️ company_id column doesn't exist in calls table\n\n";
    }

    // FIX 2: retell_call_sessions table - check branch_id column
    echo "2. Checking retell_call_sessions table...\n";

    if (Schema::hasTable('retell_call_sessions')) {
        $sessionColumns = Schema::getColumnListing('retell_call_sessions');

        if (!in_array('branch_id', $sessionColumns)) {
            echo "   ❌ branch_id column doesn't exist\n";
            echo "   ✅ Adding branch_id column...\n";

            Schema::table('retell_call_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            });

            echo "   ✅ Fixed: branch_id column added\n\n";
        } else {
            echo "   ✅ branch_id column already exists\n\n";
        }
    } else {
        echo "   ⚠️ retell_call_sessions table doesn't exist\n\n";
    }

    // FIX 3: customers table - add default value for company_id
    echo "3. Checking customers table...\n";

    if (Schema::hasTable('customers')) {
        $customersColumns = Schema::getColumnListing('customers');

        if (in_array('company_id', $customersColumns)) {
            $companyIdInfo = DB::select("SHOW COLUMNS FROM customers WHERE Field = 'company_id'")[0];

            echo "   Current company_id: {$companyIdInfo->Type}, Null={$companyIdInfo->Null}, Default={$companyIdInfo->Default}\n";

            if ($companyIdInfo->Null === 'NO' && $companyIdInfo->Default === null) {
                echo "   ❌ company_id is NOT NULL with no default\n";
                echo "   ✅ Making company_id nullable...\n";

                DB::statement('ALTER TABLE customers MODIFY company_id BIGINT UNSIGNED NULL');

                echo "   ✅ Fixed: company_id is now nullable\n\n";
            } else {
                echo "   ✅ company_id is already nullable or has default\n\n";
            }
        } else {
            echo "   ⚠️ company_id column doesn't exist in customers table\n\n";
        }
    } else {
        echo "   ⚠️ customers table doesn't exist\n\n";
    }

    echo str_repeat('=', 80) . "\n";
    echo "✅ DATABASE SCHEMA FIX COMPLETE\n";
    echo str_repeat('=', 80) . "\n\n";

    echo "SUMMARY:\n";
    echo "- calls.company_id: Made nullable\n";
    echo "- retell_call_sessions.branch_id: Added column (if missing)\n";
    echo "- customers.company_id: Made nullable\n\n";

    echo "Next step: Make a new test call to verify the fix!\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
