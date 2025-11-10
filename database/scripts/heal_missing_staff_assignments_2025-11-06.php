<?php
/**
 * Heal Missing Staff Assignments (2025-11-06)
 *
 * PROBLEM: 99.1% of appointments have staff_id: NULL because:
 *          - AppointmentCreationService didn't auto-select staff
 *          - Cal.com host mapping sometimes fails
 *          - No fallback logic existed
 *
 * SOLUTION: This script assigns staff to appointments without staff_id by:
 *           1. Finding appointments with NULL staff_id
 *           2. Looking up service's assigned staff from service_staff pivot table
 *           3. Auto-selecting first available staff (can_book = true)
 *           4. Updating appointment + linked call
 *
 * SAFETY: Read-only queries, targeted updates, transaction-safe, dry-run mode
 *
 * Usage: php database/scripts/heal_missing_staff_assignments_2025-11-06.php [--dry-run]
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Check for dry-run mode
$dryRun = in_array('--dry-run', $argv ?? []);

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Staff Assignment Healing Script\n";
echo "  Fix: Missing staff_id in appointments\n";
echo "  Date: " . now()->format('Y-m-d H:i:s') . "\n";
if ($dryRun) {
    echo "  Mode: DRY RUN (no changes will be made)\n";
}
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Step 1: Find appointments without staff
echo "📊 Step 1: Analyzing appointments without staff...\n";

$appointmentsWithoutStaff = Appointment::whereNull('staff_id')
    ->where('created_at', '>', now()->subMonths(3))  // Last 3 months only
    ->where('status', '!=', 'cancelled')
    ->with(['service', 'call'])
    ->get();

echo "   Found: {$appointmentsWithoutStaff->count()} appointments without staff_id (last 3 months)\n";

if ($appointmentsWithoutStaff->count() === 0) {
    echo "\n✅ All appointments have staff assigned! No fixing needed.\n\n";
    exit(0);
}

// Step 2: Analyze which can be auto-assigned
echo "\n📋 Step 2: Checking which appointments can have staff auto-assigned...\n";

$canBeAssigned = [];
$cannotBeAssigned = [];

foreach ($appointmentsWithoutStaff as $appointment) {
    $service = $appointment->service;

    if (!$service) {
        $cannotBeAssigned[] = [
            'appointment_id' => $appointment->id,
            'reason' => 'Service not found',
        ];
        continue;
    }

    // Check if service has assigned staff
    $availableStaff = $service->staff()
        ->wherePivot('can_book', true)
        ->first();

    if ($availableStaff) {
        $canBeAssigned[] = [
            'appointment' => $appointment,
            'service' => $service,
            'staff' => $availableStaff,
        ];
    } else {
        $cannotBeAssigned[] = [
            'appointment_id' => $appointment->id,
            'service_name' => $service->name,
            'reason' => 'No staff assigned to service in service_staff table',
        ];
    }
}

$assignableCount = count($canBeAssigned);
$nonAssignableCount = count($cannotBeAssigned);

echo "   ✅ Can be auto-assigned: {$assignableCount}\n";
echo "   ⚠️  Cannot be auto-assigned: {$nonAssignableCount}\n";

if ($assignableCount === 0) {
    echo "\n⚠️  No appointments can be auto-assigned.\n";
    echo "   Reason: Services don't have staff assigned in service_staff table.\n";
    echo "   Action: Admin should assign staff to services via Filament admin panel.\n\n";
    exit(0);
}

// Show sample of assignable appointments
echo "\n📝 Sample of appointments that will be assigned staff:\n";
echo "─────────────────────────────────────────────────────────────────\n";

$sampleCount = min(5, $assignableCount);
for ($i = 0; $i < $sampleCount; $i++) {
    $item = $canBeAssigned[$i];
    echo sprintf(
        "  • Appointment %d → %s (Staff: %s)\n",
        $item['appointment']->id,
        $item['service']->name,
        $item['staff']->name
    );
}

if ($assignableCount > $sampleCount) {
    echo "  ... and " . ($assignableCount - $sampleCount) . " more\n";
}

echo "─────────────────────────────────────────────────────────────────\n";

// Show non-assignable (if any)
if ($nonAssignableCount > 0) {
    echo "\n⚠️  Appointments that CANNOT be auto-assigned:\n";
    echo "─────────────────────────────────────────────────────────────────\n";

    $sampleNonAssignable = min(5, $nonAssignableCount);
    for ($i = 0; $i < $sampleNonAssignable; $i++) {
        $item = $cannotBeAssigned[$i];
        echo sprintf(
            "  • Appointment %d: %s\n",
            $item['appointment_id'],
            $item['reason']
        );
    }

    if ($nonAssignableCount > $sampleNonAssignable) {
        echo "  ... and " . ($nonAssignableCount - $sampleNonAssignable) . " more\n";
    }

    echo "─────────────────────────────────────────────────────────────────\n";
}

echo "\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE: No changes will be made.\n";
    echo "   Remove --dry-run to apply changes.\n\n";
    exit(0);
}

// Step 3: Confirm before proceeding
echo "⚠️  This will update {$assignableCount} appointment records (and their linked calls).\n";
echo "   Continue? [y/N]: ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "\n❌ Aborted by user.\n\n";
    exit(0);
}

// Step 4: Assign staff
echo "\n🔧 Step 3: Assigning staff to appointments...\n";

$assigned = 0;
$errors = 0;
$errorDetails = [];

foreach ($canBeAssigned as $item) {
    $appointment = $item['appointment'];
    $staff = $item['staff'];
    $service = $item['service'];

    try {
        DB::transaction(function () use ($appointment, $staff, &$assigned) {
            // Update appointment
            $appointment->update([
                'staff_id' => $staff->id,
            ]);

            // Also update linked call if exists
            if ($appointment->call) {
                $appointment->call->update([
                    'staff_id' => $staff->id,
                ]);
            }

            $assigned++;

            Log::info('✅ Staff healing: Auto-assigned staff to appointment', [
                'appointment_id' => $appointment->id,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'service_name' => $appointment->service->name,
                'call_id' => $appointment->call?->id,
                'healing_date' => now()->format('Y-m-d H:i:s'),
            ]);
        });

        echo "   ✓ Assigned: Appointment {$appointment->id} → Staff {$staff->name}\n";

    } catch (\Exception $e) {
        $errors++;
        $errorDetails[] = [
            'appointment_id' => $appointment->id,
            'error' => $e->getMessage(),
        ];

        echo "   ✗ Error: Appointment {$appointment->id} - {$e->getMessage()}\n";

        Log::error('❌ Staff healing: Failed to assign staff', [
            'appointment_id' => $appointment->id,
            'staff_id' => $staff->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

// Step 5: Summary
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Staff Assignment Healing Summary\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

echo "📊 Results:\n";
echo "   Total appointments analyzed: {$appointmentsWithoutStaff->count()}\n";
echo "   ✅ Successfully assigned staff: {$assigned}\n";
echo "   ⚠️  Could not auto-assign: {$nonAssignableCount}\n";
echo "   ❌ Errors: {$errors}\n";

if ($assignableCount > 0) {
    $successRate = round(($assigned / $assignableCount) * 100, 2);
    echo "   📈 Success rate: {$successRate}%\n";
}

if ($errors > 0) {
    echo "\n⚠️  Error Details:\n";
    foreach ($errorDetails as $error) {
        echo "   • Appointment {$error['appointment_id']}: {$error['error']}\n";
    }
}

echo "\n";

// Step 6: Verification
if ($assigned > 0) {
    echo "🔍 Step 4: Verifying assignments...\n";

    $stillWithoutStaff = Appointment::whereNull('staff_id')
        ->where('created_at', '>', now()->subMonths(3))
        ->where('status', '!=', 'cancelled')
        ->count();

    echo "   Appointments still without staff (last 3 months): {$stillWithoutStaff}\n";

    // Calculate staff assignment rate
    $totalAppointments = Appointment::where('created_at', '>', now()->subMonths(3))
        ->where('status', '!=', 'cancelled')
        ->count();

    $withStaff = $totalAppointments - $stillWithoutStaff;
    $staffRate = $totalAppointments > 0 ? round(($withStaff / $totalAppointments) * 100, 2) : 0;

    echo "\n📈 Current System Health (last 3 months):\n";
    echo "   Total appointments: {$totalAppointments}\n";
    echo "   With staff_id: {$withStaff}\n";
    echo "   Staff assignment rate: {$staffRate}%\n";
}

if ($nonAssignableCount > 0) {
    echo "\n💡 Recommendations:\n";
    echo "   {$nonAssignableCount} appointments could not be auto-assigned.\n";
    echo "   Action: Assign staff to these services in the admin panel:\n";
    echo "           https://api.askproai.de/admin/services\n";
    echo "   Then re-run this script to assign remaining appointments.\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  🎉 Staff Assignment Healing Complete\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

Log::info('Staff healing script completed', [
    'total_analyzed' => $appointmentsWithoutStaff->count(),
    'assigned' => $assigned,
    'cannot_assign' => $nonAssignableCount,
    'errors' => $errors,
    'success_rate' => $assignableCount > 0 ? round(($assigned / $assignableCount) * 100, 2) : 0,
    'run_date' => now()->toIso8601String(),
]);

exit($errors > 0 ? 1 : 0);
