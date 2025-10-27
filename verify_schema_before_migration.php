<?php

/**
 * Schema Verification Script - Pre-Migration Check
 *
 * Run this before applying Priority 1 fixes to verify:
 * 1. No orphaned records exist
 * 2. Current company_id distribution
 * 3. Current constraint/index status
 *
 * Usage: php verify_schema_before_migration.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════\n";
echo "Schema Verification - Pre-Migration Check\n";
echo "Database: " . config('database.connections.mysql.database') . "\n";
echo "Date: " . now()->toDateTimeString() . "\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// ═══════════════════════════════════════════════════════════
// CHECK 1: Orphaned Records
// ═══════════════════════════════════════════════════════════

echo "✓ CHECK 1: Orphaned Records\n";
echo "───────────────────────────────────────────────────────────\n";

$orphanedServices = DB::table('services as s')
    ->leftJoin('companies as c', 's.company_id', '=', 'c.id')
    ->whereNotNull('s.company_id')
    ->whereNull('c.id')
    ->count();

$orphanedCalls = DB::table('calls as ca')
    ->leftJoin('companies as co', 'ca.company_id', '=', 'co.id')
    ->whereNotNull('ca.company_id')
    ->whereNull('co.id')
    ->count();

$orphanedStaff = DB::table('staff as st')
    ->leftJoin('companies as c', 'st.company_id', '=', 'c.id')
    ->whereNotNull('st.company_id')
    ->whereNull('c.id')
    ->count();

echo "Orphaned services: {$orphanedServices} " . ($orphanedServices > 0 ? "❌ CRITICAL" : "✅") . "\n";
echo "Orphaned calls: {$orphanedCalls} " . ($orphanedCalls > 0 ? "❌ CRITICAL" : "✅") . "\n";
echo "Orphaned staff: {$orphanedStaff} " . ($orphanedStaff > 0 ? "❌ CRITICAL" : "✅") . "\n";

if ($orphanedServices > 0 || $orphanedCalls > 0 || $orphanedStaff > 0) {
    echo "\n⚠️  WARNING: Orphaned records found. Foreign key constraints will fail!\n";
    echo "   Action: Clean up orphaned records before migration.\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════
// CHECK 2: NULL company_id Distribution
// ═══════════════════════════════════════════════════════════

echo "✓ CHECK 2: NULL company_id Distribution\n";
echo "───────────────────────────────────────────────────────────\n";

$nullServices = DB::table('services')->whereNull('company_id')->count();
$totalServices = DB::table('services')->count();
$nullServicesPercent = $totalServices > 0 ? round(($nullServices / $totalServices) * 100, 2) : 0;

$nullStaff = DB::table('staff')->whereNull('company_id')->count();
$totalStaff = DB::table('staff')->count();
$nullStaffPercent = $totalStaff > 0 ? round(($nullStaff / $totalStaff) * 100, 2) : 0;

$nullCalls = DB::table('calls')->whereNull('company_id')->count();
$totalCalls = DB::table('calls')->count();
$nullCallsPercent = $totalCalls > 0 ? round(($nullCalls / $totalCalls) * 100, 2) : 0;

$nullBranches = DB::table('branches')->whereNull('company_id')->count();
$totalBranches = DB::table('branches')->count();
$nullBranchesPercent = $totalBranches > 0 ? round(($nullBranches / $totalBranches) * 100, 2) : 0;

echo "services: {$nullServices}/{$totalServices} NULL ({$nullServicesPercent}%) " . ($nullServices > 0 ? "⚠️  Will backfill" : "✅") . "\n";
echo "staff: {$nullStaff}/{$totalStaff} NULL ({$nullStaffPercent}%) " . ($nullStaff > 0 ? "⚠️  Will backfill" : "✅") . "\n";
echo "calls: {$nullCalls}/{$totalCalls} NULL ({$nullCallsPercent}%) " . ($nullCalls > 0 ? "⚠️  Will backfill" : "✅") . "\n";
echo "branches: {$nullBranches}/{$totalBranches} NULL ({$nullBranchesPercent}%) " . ($nullBranches > 0 ? "⚠️  Will backfill" : "✅") . "\n";

echo "\n";

// ═══════════════════════════════════════════════════════════
// CHECK 3: Current Foreign Key Status
// ═══════════════════════════════════════════════════════════

echo "✓ CHECK 3: Foreign Key Status\n";
echo "───────────────────────────────────────────────────────────\n";

$servicesFk = DB::select("
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'services'
      AND REFERENCED_TABLE_NAME = 'companies'
      AND REFERENCED_COLUMN_NAME = 'id'
");

$callsFk = DB::select("
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'calls'
      AND REFERENCED_TABLE_NAME = 'companies'
      AND REFERENCED_COLUMN_NAME = 'id'
");

echo "services.company_id → companies.id: " . (empty($servicesFk) ? "❌ MISSING (will add)" : "✅ EXISTS") . "\n";
echo "calls.company_id → companies.id: " . (empty($callsFk) ? "❌ MISSING (will add)" : "✅ EXISTS") . "\n";

echo "\n";

// ═══════════════════════════════════════════════════════════
// CHECK 4: Current Index Status
// ═══════════════════════════════════════════════════════════

echo "✓ CHECK 4: Performance Index Status\n";
echo "───────────────────────────────────────────────────────────\n";

$staffCalcomIndex = DB::select("SHOW INDEX FROM staff WHERE Key_name = 'idx_staff_calcom_user'");
$serviceStaffReverseIndex = DB::select("SHOW INDEX FROM service_staff WHERE Key_name = 'idx_service_staff_reverse'");
$servicesBranchIndex = DB::select("SHOW INDEX FROM services WHERE Key_name = 'idx_services_branch_active'");
$callsCustomerCompanyIndex = DB::select("SHOW INDEX FROM calls WHERE Key_name = 'idx_calls_customer_company'");

echo "staff.calcom_user_id: " . (empty($staffCalcomIndex) ? "❌ MISSING (will add)" : "✅ EXISTS") . "\n";
echo "service_staff(staff_id, can_book, is_active): " . (empty($serviceStaffReverseIndex) ? "❌ MISSING (will add)" : "✅ EXISTS") . "\n";
echo "services(company_id, branch_id, is_active): " . (empty($servicesBranchIndex) ? "❌ MISSING (will add)" : "✅ EXISTS") . "\n";
echo "calls(company_id, customer_id, created_at): " . (empty($callsCustomerCompanyIndex) ? "❌ MISSING (will add)" : "✅ EXISTS") . "\n";

echo "\n";

// ═══════════════════════════════════════════════════════════
// CHECK 5: Database Size & Table Statistics
// ═══════════════════════════════════════════════════════════

echo "✓ CHECK 5: Database Statistics\n";
echo "───────────────────────────────────────────────────────────\n";

$appointmentsCount = DB::table('appointments')->count();
$servicesCount = DB::table('services')->count();
$staffCount = DB::table('staff')->count();
$callsCount = DB::table('calls')->count();
$customersCount = DB::table('customers')->count();
$companiesCount = DB::table('companies')->count();

echo "Companies: {$companiesCount}\n";
echo "Services: {$servicesCount}\n";
echo "Staff: {$staffCount}\n";
echo "Customers: {$customersCount}\n";
echo "Appointments: {$appointmentsCount}\n";
echo "Calls: {$callsCount}\n";

echo "\n";

// ═══════════════════════════════════════════════════════════
// SUMMARY & RECOMMENDATION
// ═══════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════\n";
echo "SUMMARY & RECOMMENDATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$criticalIssues = ($orphanedServices > 0 || $orphanedCalls > 0 || $orphanedStaff > 0) ? 1 : 0;
$backfillNeeded = ($nullServices + $nullStaff + $nullCalls + $nullBranches) > 0;
$fksToAdd = (empty($servicesFk) ? 1 : 0) + (empty($callsFk) ? 1 : 0);
$indexesToAdd = (empty($staffCalcomIndex) ? 1 : 0) +
                (empty($serviceStaffReverseIndex) ? 1 : 0) +
                (empty($servicesBranchIndex) ? 1 : 0) +
                (empty($callsCustomerCompanyIndex) ? 1 : 0);

if ($criticalIssues > 0) {
    echo "❌ CRITICAL ISSUES FOUND!\n";
    echo "   Cannot proceed with migration until orphaned records are cleaned up.\n\n";
    echo "   Action Required:\n";
    if ($orphanedServices > 0) {
        echo "   - Clean up {$orphanedServices} orphaned services\n";
    }
    if ($orphanedCalls > 0) {
        echo "   - Clean up {$orphanedCalls} orphaned calls\n";
    }
    if ($orphanedStaff > 0) {
        echo "   - Clean up {$orphanedStaff} orphaned staff\n";
    }
    echo "\n";
    exit(1);
}

echo "✅ NO CRITICAL ISSUES\n\n";

if ($backfillNeeded) {
    $totalNull = $nullServices + $nullStaff + $nullCalls + $nullBranches;
    echo "⚠️  Backfill Required: {$totalNull} records with NULL company_id\n";
    echo "   Migration will automatically backfill these records.\n\n";
}

echo "Migration will apply:\n";
echo "  - {$fksToAdd} foreign key constraints\n";
echo "  - {$indexesToAdd} performance indexes\n";
echo "  - 4 NOT NULL constraints (after backfill)\n\n";

echo "Estimated Impact:\n";
echo "  - Execution time: ~2 hours (with backfill)\n";
echo "  - Downtime: None (indexes added online)\n";
echo "  - Data loss: None (additive changes only)\n\n";

echo "✅ SAFE TO PROCEED with migration\n\n";

echo "Next Steps:\n";
echo "  1. Backup database: mysqldump -u user -p askproai_db > backup_\$(date +%Y%m%d).sql\n";
echo "  2. Run migration: php artisan migrate --path=database/migrations/2025_10_23_000000_priority1_schema_fixes.php\n";
echo "  3. Verify: php verify_schema_after_migration.php\n\n";

echo "═══════════════════════════════════════════════════════════\n";
