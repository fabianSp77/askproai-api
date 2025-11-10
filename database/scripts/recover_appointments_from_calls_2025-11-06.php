<?php
/**
 * Recover Missing Appointments from Call Hidden Data (2025-11-06)
 *
 * PROBLEM: 15 calls have appointment data in "hidden" columns but NO appointments exist:
 *          - datum_termin (appointment date)
 *          - uhrzeit_termin (appointment time)
 *          - dienstleistung (service name)
 *
 * ROOT CAUSE: October 1, 2025 regression - appointment creation failed but call data was saved
 *
 * SOLUTION: This script creates missing appointments from call hidden data by:
 *           1. Finding calls with datum_termin but NO appointment_id
 *           2. Fuzzy matching dienstleistung to existing services
 *           3. Parsing date/time into proper appointment datetime
 *           4. Creating appointment with auto-assigned staff
 *           5. Establishing bidirectional call ↔ appointment links
 *
 * SAFETY: Dry-run mode, test data separation, transaction-safe, rollback on error
 *
 * Usage: php database/scripts/recover_appointments_from_calls_2025-11-06.php [--dry-run] [--include-test-data]
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv ?? []);
$includeTestData = in_array('--include-test-data', $argv ?? []);

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Appointment Recovery from Call Hidden Data\n";
echo "  Regression Fix: October 1, 2025\n";
echo "  Date: " . now()->format('Y-m-d H:i:s') . "\n";
if ($dryRun) {
    echo "  Mode: DRY RUN (no changes will be made)\n";
}
if ($includeTestData) {
    echo "  Test Data: INCLUDED\n";
} else {
    echo "  Test Data: EXCLUDED (use --include-test-data to include)\n";
}
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Step 1: Find calls with hidden appointment data
echo "📊 Step 1: Finding calls with hidden appointment data...\n";

$callsWithHiddenData = Call::whereNull('appointment_id')
    ->whereNotNull('datum_termin')
    ->with(['customer', 'branch', 'company'])
    ->get();

echo "   Found: {$callsWithHiddenData->count()} calls with hidden appointment data\n";

if ($callsWithHiddenData->count() === 0) {
    echo "\n✅ No calls with hidden appointment data found! No recovery needed.\n\n";
    exit(0);
}

// Step 2: Filter test data
$testDataKeywords = ['hansi', 'hinterseher', 'test', 'demo'];

$realCalls = $callsWithHiddenData->filter(function ($call) use ($testDataKeywords, $includeTestData) {
    if ($includeTestData) {
        return true;
    }

    $customerName = strtolower($call->customer?->name ?? '');

    foreach ($testDataKeywords as $keyword) {
        if (str_contains($customerName, $keyword)) {
            return false;
        }
    }

    return true;
});

$testCalls = $callsWithHiddenData->diff($realCalls);

echo "\n📋 Data Classification:\n";
echo "   Real data calls: {$realCalls->count()}\n";
echo "   Test data calls: {$testCalls->count()}\n";

if ($realCalls->count() === 0 && !$includeTestData) {
    echo "\n⚠️  All calls are test data. Use --include-test-data to process them.\n\n";
    exit(0);
}

$callsToProcess = $includeTestData ? $callsWithHiddenData : $realCalls;

// Step 3: Analyze recoverability
echo "\n📋 Step 2: Analyzing recoverability...\n";
echo "─────────────────────────────────────────────────────────────────\n";

$recoverable = [];
$notRecoverable = [];

foreach ($callsToProcess as $call) {
    $issues = [];

    // Check required data
    if (!$call->datum_termin) {
        $issues[] = 'Missing datum_termin';
    }

    if (!$call->branch_id) {
        $issues[] = 'Missing branch_id';
    }

    if (!$call->company_id) {
        $issues[] = 'Missing company_id';
    }

    if (!$call->dienstleistung) {
        $issues[] = 'Missing dienstleistung (service name)';
    }

    if (empty($issues)) {
        $recoverable[] = [
            'call' => $call,
            'issues' => [],
        ];
    } else {
        $notRecoverable[] = [
            'call' => $call,
            'issues' => $issues,
        ];
    }
}

$recoverableCount = count($recoverable);
$notRecoverableCount = count($notRecoverable);

echo "   ✅ Recoverable: {$recoverableCount}\n";
echo "   ❌ Not recoverable: {$notRecoverableCount}\n";

if ($recoverableCount === 0) {
    echo "\n⚠️  No calls can be recovered (missing required data).\n\n";
    exit(0);
}

// Show sample
echo "\n📝 Sample of recoverable calls:\n";
echo "─────────────────────────────────────────────────────────────────\n";

$sampleCount = min(5, $recoverableCount);
for ($i = 0; $i < $sampleCount; $i++) {
    $item = $recoverable[$i];
    $call = $item['call'];
    echo sprintf(
        "  • Call %d | Customer: %s | Date: %s | Time: %s | Service: %s\n",
        $call->id,
        $call->customer?->name ?? 'Unknown',
        $call->datum_termin,
        $call->uhrzeit_termin ?? 'Not specified',
        $call->dienstleistung
    );
}

if ($recoverableCount > $sampleCount) {
    echo "  ... and " . ($recoverableCount - $sampleCount) . " more\n";
}

echo "─────────────────────────────────────────────────────────────────\n";

// Step 4: Service matching
echo "\n🔍 Step 3: Matching services...\n";

$matchedServices = [];
$unmatchedServices = [];

foreach ($recoverable as $key => $item) {
    $call = $item['call'];
    $serviceName = $call->dienstleistung;

    // Try exact match first
    $service = Service::where('name', $serviceName)
        ->where('branch_id', $call->branch_id)
        ->first();

    // Try fuzzy match if exact fails
    if (!$service) {
        $allServices = Service::where('branch_id', $call->branch_id)->get();

        foreach ($allServices as $candidateService) {
            $similarity = 0;
            similar_text(
                strtolower($serviceName),
                strtolower($candidateService->name),
                $similarity
            );

            if ($similarity > 80) {
                $service = $candidateService;
                break;
            }
        }
    }

    if ($service) {
        $recoverable[$key]['service'] = $service;
        $matchedServices[] = $serviceName;
    } else {
        $unmatchedServices[] = $serviceName;
        // Remove from recoverable
        unset($recoverable[$key]);
    }
}

$recoverable = array_values($recoverable); // Re-index array
$recoverableCount = count($recoverable);

echo "   ✅ Matched services: " . count($matchedServices) . "\n";
echo "   ❌ Unmatched services: " . count($unmatchedServices) . "\n";

if (count($unmatchedServices) > 0) {
    echo "\n⚠️  Services that could not be matched:\n";
    foreach (array_unique($unmatchedServices) as $serviceName) {
        echo "   • {$serviceName}\n";
    }
}

if ($recoverableCount === 0) {
    echo "\n❌ No appointments can be recovered (no matching services found).\n\n";
    exit(0);
}

echo "\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE: No changes will be made.\n";
    echo "   Remove --dry-run to apply recovery.\n\n";
    echo "📊 Would recover {$recoverableCount} appointments.\n\n";
    exit(0);
}

// Step 5: Confirm before proceeding
echo "⚠️  This will create {$recoverableCount} new appointment records.\n";
echo "   Continue? [y/N]: ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "\n❌ Aborted by user.\n\n";
    exit(0);
}

// Step 6: Recover appointments
echo "\n🔧 Step 4: Creating appointments...\n";

$recovered = 0;
$errors = 0;
$errorDetails = [];

foreach ($recoverable as $item) {
    $call = $item['call'];
    $service = $item['service'];

    try {
        DB::transaction(function () use ($call, $service, &$recovered) {
            // Parse date and time
            $date = $call->datum_termin;
            $time = $call->uhrzeit_termin ?? '09:00';

            // Combine into datetime
            try {
                $startsAt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "{$date} {$time}");
            } catch (\Exception $e) {
                // Fallback to just date if time parsing fails
                $startsAt = \Carbon\Carbon::createFromFormat('Y-m-d', $date)->setTime(9, 0);
            }

            $endsAt = $startsAt->copy()->addMinutes($service->duration ?? 60);

            // Auto-select staff from service
            $staff = $service->staff()
                ->wherePivot('can_book', true)
                ->first();

            // Create appointment
            $appointment = new Appointment();
            $appointment->forceFill([
                'customer_id' => $call->customer_id,
                'branch_id' => $call->branch_id,
                'company_id' => $call->company_id,
                'service_id' => $service->id,
                'staff_id' => $staff?->id,
                'call_id' => $call->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'confirmed', // Recovered data assumed confirmed
                'price' => $service->price,
                'recovery_source' => 'call_hidden_data',
                'recovery_date' => now(),
                'notes' => "Recovered from call hidden data (datum_termin, uhrzeit_termin, dienstleistung) on " . now()->format('Y-m-d'),
            ]);

            $appointment->save();

            // Update call with bidirectional link
            $call->update([
                'appointment_id' => $appointment->id,
                'staff_id' => $staff?->id ?? $call->staff_id,
                'has_appointment' => true,
                'appointment_link_status' => 'recovered',
                'appointment_linked_at' => now(),
            ]);

            $recovered++;

            Log::info('✅ Recovery: Created appointment from call hidden data', [
                'call_id' => $call->id,
                'appointment_id' => $appointment->id,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'staff_id' => $staff?->id,
                'customer_name' => $call->customer?->name,
                'starts_at' => $startsAt->format('Y-m-d H:i'),
                'recovery_date' => now()->format('Y-m-d H:i:s'),
            ]);
        });

        echo "   ✓ Recovered: Call ID {$call->id} → Appointment created\n";

    } catch (\Exception $e) {
        $errors++;
        $errorDetails[] = [
            'call_id' => $call->id,
            'error' => $e->getMessage(),
        ];

        echo "   ✗ Error: Call ID {$call->id} - {$e->getMessage()}\n";

        Log::error('❌ Recovery: Failed to create appointment from call', [
            'call_id' => $call->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

// Step 7: Summary
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Appointment Recovery Summary\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

echo "📊 Results:\n";
echo "   Total calls analyzed: {$callsWithHiddenData->count()}\n";
echo "   Recoverable: {$recoverableCount}\n";
echo "   ✅ Successfully recovered: {$recovered}\n";
echo "   ❌ Errors: {$errors}\n";

if ($recoverableCount > 0) {
    $successRate = round(($recovered / $recoverableCount) * 100, 2);
    echo "   📈 Success rate: {$successRate}%\n";
}

if ($errors > 0) {
    echo "\n⚠️  Error Details:\n";
    foreach ($errorDetails as $error) {
        echo "   • Call {$error['call_id']}: {$error['error']}\n";
    }
}

if ($testCalls->count() > 0 && !$includeTestData) {
    echo "\n💡 Test Data Notice:\n";
    echo "   {$testCalls->count()} test data calls were excluded.\n";
    echo "   Use --include-test-data to process them if needed.\n";
}

echo "\n";

// Step 8: Verification
if ($recovered > 0) {
    echo "🔍 Step 5: Verifying recovery...\n";

    $stillWithHiddenData = Call::whereNull('appointment_id')
        ->whereNotNull('datum_termin')
        ->count();

    echo "   Calls still with hidden data: {$stillWithHiddenData}\n";

    // Calculate recovery rate
    $totalCallsWithAppointment = Call::whereNotNull('appointment_id')->count();
    $totalCalls = Call::count();
    $linkingRate = $totalCalls > 0 ? round(($totalCallsWithAppointment / $totalCalls) * 100, 2) : 0;

    echo "\n📈 Current System Health:\n";
    echo "   Total calls: {$totalCalls}\n";
    echo "   Calls with appointment_id: {$totalCallsWithAppointment}\n";
    echo "   Linking rate: {$linkingRate}%\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  🎉 Appointment Recovery Complete\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

Log::info('Appointment recovery script completed', [
    'total_analyzed' => $callsWithHiddenData->count(),
    'recovered' => $recovered,
    'errors' => $errors,
    'success_rate' => $recoverableCount > 0 ? round(($recovered / $recoverableCount) * 100, 2) : 0,
    'run_date' => now()->toIso8601String(),
]);

exit($errors > 0 ? 1 : 0);
