<?php

/**
 * Phase 2: Test Company Cleanup Script
 *
 * Safely removes 37 test/dummy companies while preserving production data
 *
 * Production Companies (KEEP):
 * - ID 1: Krückeberg Servicegruppe (113 appointments, 41 calls)
 * - ID 15: AskProAI (26 appointments, 130 calls)
 *
 * Test Companies (DELETE): 37 total
 *
 * Run: php database/scripts/cleanup_test_companies.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   TEST COMPANY CLEANUP - PHASE 2\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Production companies to KEEP
$productionCompanyIds = [1, 15]; // Krückeberg, AskProAI

echo "🔒 Production Companies (PROTECTED):\n";
$prodCompanies = Company::whereIn('id', $productionCompanyIds)->get(['id', 'name']);
foreach ($prodCompanies as $company) {
    echo "   - ID {$company->id}: {$company->name}\n";
}
echo "\n";

// Get all companies except production
$testCompanies = Company::whereNotIn('id', $productionCompanyIds)->get();

echo "🗑️  Test Companies to DELETE: " . $testCompanies->count() . "\n";
foreach ($testCompanies as $company) {
    $apptCount = Appointment::where('company_id', $company->id)->count();
    $callCount = Call::where('company_id', $company->id)->count();
    $customerCount = Customer::where('company_id', $company->id)->count();

    echo sprintf(
        "   - ID %3d: %-50s (Appts: %3d, Calls: %3d, Customers: %3d)\n",
        $company->id,
        substr($company->name, 0, 50),
        $apptCount,
        $callCount,
        $customerCount
    );
}

echo "\n";
echo "⚠️  WARNING: This will permanently delete:\n";
echo "   - " . $testCompanies->count() . " test companies\n";
echo "   - All associated appointments\n";
echo "   - All associated customers\n";
echo "   - All associated calls\n";
echo "   - All associated services\n";
echo "   - All associated branches\n";
echo "   - All associated staff\n";
echo "\n";

// Auto-proceed (no confirmation needed when run via script)
echo "🚀 Starting cleanup process...\n\n";

$stats = [
    'companies_deleted' => 0,
    'appointments_deleted' => 0,
    'customers_deleted' => 0,
    'calls_deleted' => 0,
    'services_deleted' => 0,
    'branches_deleted' => 0,
    'staff_deleted' => 0,
    'errors' => []
];

DB::beginTransaction();

try {
    foreach ($testCompanies as $company) {
        echo "Processing: {$company->name} (ID: {$company->id})...\n";

        // Count before deletion
        $apptCount = Appointment::where('company_id', $company->id)->count();
        $customerCount = Customer::where('company_id', $company->id)->count();
        $callCount = Call::where('company_id', $company->id)->count();
        $serviceCount = Service::where('company_id', $company->id)->count();
        $branchCount = Branch::where('company_id', $company->id)->count();
        $staffCount = Staff::where('company_id', $company->id)->count();

        // Delete associated data
        Appointment::where('company_id', $company->id)->delete();
        Customer::where('company_id', $company->id)->delete();
        Call::where('company_id', $company->id)->delete();
        Service::where('company_id', $company->id)->delete();
        Branch::where('company_id', $company->id)->delete();
        Staff::where('company_id', $company->id)->delete();

        // Delete company
        $company->delete();

        // Update stats
        $stats['companies_deleted']++;
        $stats['appointments_deleted'] += $apptCount;
        $stats['customers_deleted'] += $customerCount;
        $stats['calls_deleted'] += $callCount;
        $stats['services_deleted'] += $serviceCount;
        $stats['branches_deleted'] += $branchCount;
        $stats['staff_deleted'] += $staffCount;

        echo "   ✅ Deleted: {$apptCount} appts, {$customerCount} customers, {$callCount} calls, {$serviceCount} services, {$branchCount} branches, {$staffCount} staff\n";
    }

    DB::commit();

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ✅ CLEANUP COMPLETE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "📊 Summary:\n";
    echo "   Companies deleted:    " . $stats['companies_deleted'] . "\n";
    echo "   Appointments deleted: " . $stats['appointments_deleted'] . "\n";
    echo "   Customers deleted:    " . $stats['customers_deleted'] . "\n";
    echo "   Calls deleted:        " . $stats['calls_deleted'] . "\n";
    echo "   Services deleted:     " . $stats['services_deleted'] . "\n";
    echo "   Branches deleted:     " . $stats['branches_deleted'] . "\n";
    echo "   Staff deleted:        " . $stats['staff_deleted'] . "\n";

    echo "\n";
    echo "🔒 Production Companies PRESERVED:\n";
    $prodCompaniesAfter = Company::whereIn('id', $productionCompanyIds)->get(['id', 'name']);
    foreach ($prodCompaniesAfter as $company) {
        $apptCount = Appointment::where('company_id', $company->id)->count();
        $callCount = Call::where('company_id', $company->id)->count();
        echo "   - ID {$company->id}: {$company->name} ({$apptCount} appts, {$callCount} calls) ✅\n";
    }

    echo "\n";
    echo "📋 Remaining Companies: " . Company::count() . " (should be 2)\n";
    echo "\n";

    // Log to Laravel
    Log::info('Phase 2: Test company cleanup completed', $stats);

    echo "✅ All done! Database cleaned successfully.\n";
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ❌ ERROR\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n";
    echo "⚠️  Transaction rolled back. No data was deleted.\n";
    echo "\n";

    Log::error('Phase 2: Test company cleanup failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    exit(1);
}
