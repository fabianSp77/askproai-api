<?php
/**
 * Data Recovery Script: Heal Orphaned Appointments
 *
 * PURPOSE: Retroactively assign staff_id to appointments missing this field
 * CONTEXT: 123 of 124 appointments (99.2%) created without staff_id
 * ROOT CAUSE: Missing staff assignment logic in booking flow (fixed 2025-11-08)
 * TIMELINE: System broken since 2025-09-26 (43+ days)
 *
 * HEALING STRATEGY:
 * 1. Find all appointments without staff_id (created since 2025-09-26)
 * 2. For each appointment:
 *    a) Try to find staff member assigned to the service in that branch
 *    b) Fallback: Any staff member in that branch
 *    c) Update appointment with staff_id
 *
 * USAGE:
 *   php database/scripts/heal_orphaned_appointments_2025-11-08.php
 *
 * SAFE TO RE-RUN: Yes - only updates appointments with NULL staff_id
 *
 * @author Claude Code
 * @date 2025-11-08
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  APPOINTMENT DATA RECOVERY - 2025-11-08\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

// Find orphaned appointments (missing staff_id)
$orphanedQuery = Appointment::whereNull('staff_id')
    ->where('created_at', '>=', '2025-09-26');  // Last working date

$totalOrphaned = $orphanedQuery->count();

echo "📊 ANALYSIS:\n";
echo "  Total appointments without staff_id: {$totalOrphaned}\n";
echo "  Date range: 2025-09-26 to now\n";
echo "\n";

if ($totalOrphaned === 0) {
    echo "✅ No orphaned appointments found. System is healthy!\n\n";
    exit(0);
}

// Statistics tracking
$stats = [
    'total' => $totalOrphaned,
    'fixed_service_match' => 0,
    'fixed_branch_fallback' => 0,
    'failed_no_staff' => 0,
    'failed_errors' => 0
];

$orphaned = $orphanedQuery->get();

echo "🔧 HEALING PROCESS:\n";
echo "───────────────────────────────────────────────────────────\n";

foreach ($orphaned as $index => $appointment) {
    $appointmentNum = $index + 1;
    $appointmentId = $appointment->id;

    echo "\n[{$appointmentNum}/{$totalOrphaned}] Processing appointment ID: {$appointmentId}\n";
    echo "  Service ID: {$appointment->service_id}\n";
    echo "  Branch ID: {$appointment->branch_id}\n";
    echo "  Company ID: {$appointment->company_id}\n";

    try {
        // Strategy 1: Find staff assigned to this specific service in this branch
        $staffMember = Staff::where('company_id', $appointment->company_id)
            ->where('branch_id', $appointment->branch_id)
            ->whereHas('services', function($q) use($appointment) {
                $q->where('service_id', $appointment->service_id);
            })
            ->first();

        if ($staffMember) {
            echo "  ✅ Found staff assigned to service\n";
            echo "     Staff ID: {$staffMember->id}\n";
            echo "     Staff Name: {$staffMember->name}\n";

            $appointment->staff_id = $staffMember->id;
            $appointment->save();

            $stats['fixed_service_match']++;

            Log::info('DATA RECOVERY: Assigned staff to orphaned appointment (service match)', [
                'appointment_id' => $appointmentId,
                'staff_id' => $staffMember->id,
                'service_id' => $appointment->service_id,
                'match_type' => 'service_assigned'
            ]);

            continue;
        }

        // Strategy 2: Fallback - any staff in this branch
        $staffMember = Staff::where('company_id', $appointment->company_id)
            ->where('branch_id', $appointment->branch_id)
            ->first();

        if ($staffMember) {
            echo "  ⚠️  Using branch fallback (no service-specific staff found)\n";
            echo "     Staff ID: {$staffMember->id}\n";
            echo "     Staff Name: {$staffMember->name}\n";

            $appointment->staff_id = $staffMember->id;
            $appointment->save();

            $stats['fixed_branch_fallback']++;

            Log::warning('DATA RECOVERY: Assigned staff to orphaned appointment (branch fallback)', [
                'appointment_id' => $appointmentId,
                'staff_id' => $staffMember->id,
                'service_id' => $appointment->service_id,
                'match_type' => 'branch_fallback'
            ]);

            continue;
        }

        // Strategy 3: No staff found - log as failed
        echo "  ❌ FAILED: No staff found in company {$appointment->company_id}, branch {$appointment->branch_id}\n";

        $stats['failed_no_staff']++;

        Log::error('DATA RECOVERY: Cannot heal orphaned appointment - no staff in branch', [
            'appointment_id' => $appointmentId,
            'company_id' => $appointment->company_id,
            'branch_id' => $appointment->branch_id,
            'service_id' => $appointment->service_id
        ]);

    } catch (\Exception $e) {
        echo "  ❌ ERROR: {$e->getMessage()}\n";

        $stats['failed_errors']++;

        Log::error('DATA RECOVERY: Exception while healing appointment', [
            'appointment_id' => $appointmentId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  RECOVERY SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";
echo "Total processed:           {$stats['total']}\n";
echo "✅ Fixed (service match):  {$stats['fixed_service_match']}\n";
echo "⚠️  Fixed (branch fallback): {$stats['fixed_branch_fallback']}\n";
echo "❌ Failed (no staff):      {$stats['failed_no_staff']}\n";
echo "❌ Failed (errors):        {$stats['failed_errors']}\n";
echo "\n";

$totalFixed = $stats['fixed_service_match'] + $stats['fixed_branch_fallback'];
$totalFailed = $stats['failed_no_staff'] + $stats['failed_errors'];

echo "TOTALS:\n";
echo "  Successfully healed: {$totalFixed}\n";
echo "  Failed to heal:      {$totalFailed}\n";

if ($totalFixed > 0) {
    $successRate = round(($totalFixed / $stats['total']) * 100, 1);
    echo "  Success rate:        {$successRate}%\n";
}

echo "\n";

// Verify final state
$remainingOrphaned = Appointment::whereNull('staff_id')
    ->where('created_at', '>=', '2025-09-26')
    ->count();

echo "VERIFICATION:\n";
echo "  Remaining orphaned appointments: {$remainingOrphaned}\n";

if ($remainingOrphaned === 0) {
    echo "\n✅ ALL APPOINTMENTS HEALED! System is now healthy.\n";
} elseif ($remainingOrphaned < $totalOrphaned) {
    echo "\n⚠️  Partial recovery: {$remainingOrphaned} appointments still need manual intervention.\n";
    echo "   Check logs for details on failed appointments.\n";
} else {
    echo "\n❌ No progress made. Please investigate staff/branch data integrity.\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

exit($totalFailed > 0 ? 1 : 0);
