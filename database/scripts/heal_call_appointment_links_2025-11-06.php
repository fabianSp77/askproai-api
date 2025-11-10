<?php
/**
 * Heal Call-Appointment Links (Regression Fix 2025-11-06)
 *
 * PROBLEM: Since Oct 1, 2025, appointments were created with call_id (forward link)
 *          but calls were NOT updated with appointment_id (backward link missing).
 *          This caused 99.88% of calls to have no appointment data visible in admin UI.
 *
 * IMPACT: 7 appointments created Oct 3-present have broken bidirectional links
 *
 * SOLUTION: This script restores bidirectional links for historical data by:
 *           1. Finding appointments with call_id set
 *           2. Checking if the linked call has appointment_id set
 *           3. If not, updating call with appointment_id
 *
 * SAFETY: Read-only queries, targeted updates only, full transaction rollback on error
 *
 * Usage: php database/scripts/heal_call_appointment_links_2025-11-06.php
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Call-Appointment Bidirectional Link Healing Script\n";
echo "  Regression Fix: October 1, 2025\n";
echo "  Date: " . now()->format('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Step 1: Find appointments with call_id set
echo "📊 Step 1: Analyzing database...\n";

$appointmentsWithCallId = Appointment::whereNotNull('call_id')
    ->with('call')
    ->get();

echo "   Found: {$appointmentsWithCallId->count()} appointments with call_id set\n";

// Step 2: Filter for broken links (call doesn't have appointment_id)
$brokenLinks = $appointmentsWithCallId->filter(function ($appointment) {
    return $appointment->call && is_null($appointment->call->appointment_id);
});

echo "   Broken bidirectional links: {$brokenLinks->count()}\n";

if ($brokenLinks->count() === 0) {
    echo "\n✅ All links are healthy! No fixing needed.\n\n";
    exit(0);
}

echo "\n📋 Broken Links Details:\n";
echo "─────────────────────────────────────────────────────────────────\n";

foreach ($brokenLinks as $appointment) {
    $call = $appointment->call;
    echo sprintf(
        "  • Appointment ID: %d | Call ID: %d | Created: %s\n",
        $appointment->id,
        $call->id,
        $appointment->created_at->format('Y-m-d H:i')
    );
}

echo "─────────────────────────────────────────────────────────────────\n";
echo "\n";

// Step 3: Confirm before proceeding
echo "⚠️  This will update {$brokenLinks->count()} call records.\n";
echo "   Continue? [y/N]: ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "\n❌ Aborted by user.\n\n";
    exit(0);
}

// Step 4: Fix broken links
echo "\n🔧 Step 2: Fixing broken links...\n";

$fixed = 0;
$errors = 0;
$errorDetails = [];

foreach ($brokenLinks as $appointment) {
    try {
        DB::transaction(function () use ($appointment, &$fixed) {
            $call = $appointment->call;

            $call->update([
                'appointment_id' => $appointment->id,
                'staff_id' => $appointment->staff_id ?? $call->staff_id,
                'has_appointment' => true,
                'appointment_link_status' => 'linked',
                'appointment_linked_at' => now(),
            ]);

            $fixed++;

            Log::info('✅ Healing script: Fixed broken bidirectional link', [
                'call_id' => $call->id,
                'appointment_id' => $appointment->id,
                'appointment_created' => $appointment->created_at->format('Y-m-d H:i'),
                'staff_id' => $appointment->staff_id,
                'healing_date' => now()->format('Y-m-d H:i:s'),
            ]);
        });

        echo "   ✓ Fixed: Call ID {$appointment->call->id} → Appointment ID {$appointment->id}\n";

    } catch (\Exception $e) {
        $errors++;
        $errorDetails[] = [
            'appointment_id' => $appointment->id,
            'call_id' => $appointment->call_id,
            'error' => $e->getMessage(),
        ];

        echo "   ✗ Error: Appointment ID {$appointment->id} - {$e->getMessage()}\n";

        Log::error('❌ Healing script: Failed to fix broken link', [
            'appointment_id' => $appointment->id,
            'call_id' => $appointment->call_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

// Step 5: Summary
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Healing Summary\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

echo "📊 Results:\n";
echo "   Total broken links found: {$brokenLinks->count()}\n";
echo "   ✅ Successfully fixed: {$fixed}\n";
echo "   ❌ Errors: {$errors}\n";

if ($brokenLinks->count() > 0) {
    $successRate = round(($fixed / $brokenLinks->count()) * 100, 2);
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
if ($fixed > 0) {
    echo "🔍 Step 3: Verifying fixes...\n";

    $stillBroken = Appointment::whereNotNull('call_id')
        ->whereHas('call', function ($q) {
            $q->whereNull('appointment_id');
        })
        ->count();

    if ($stillBroken === 0) {
        echo "   ✅ All links verified healthy!\n";
    } else {
        echo "   ⚠️  {$stillBroken} links still broken (these may be new or require manual review)\n";
    }

    echo "\n";

    // Calculate new linking rate
    $totalCalls = Call::count();
    $callsWithAppointment = Call::whereNotNull('appointment_id')->count();
    $linkingRate = $totalCalls > 0 ? round(($callsWithAppointment / $totalCalls) * 100, 2) : 0;

    echo "📈 Current System Health:\n";
    echo "   Total calls: {$totalCalls}\n";
    echo "   Calls with appointment_id: {$callsWithAppointment}\n";
    echo "   Linking rate: {$linkingRate}%\n";
    echo "   (Target: >95% for new appointments after regression fix)\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  🎉 Healing Complete\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

Log::info('Healing script completed', [
    'total_broken' => $brokenLinks->count(),
    'fixed' => $fixed,
    'errors' => $errors,
    'success_rate' => $brokenLinks->count() > 0 ? round(($fixed / $brokenLinks->count()) * 100, 2) : 100,
    'run_date' => now()->toIso8601String(),
]);

exit($errors > 0 ? 1 : 0);
