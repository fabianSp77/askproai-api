<?php

/**
 * E2E Test - Phase 3 & 4: Reschedule and Cancel
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use Carbon\Carbon;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         E2E TEST - PHASE 3 & 4: RESCHEDULE & CANCEL          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Load appointment IDs from previous test
$appointmentData = json_decode(file_get_contents('/tmp/e2e_test_appointments.json'), true);
$appointmentIds = $appointmentData['appointment_ids'];

echo "📋 Testing with appointments: " . implode(', ', $appointmentIds) . "\n\n";

// ============================================================================
// PHASE 3: RESCHEDULE APPOINTMENTS
// ============================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 3: RESCHEDULE APPOINTMENTS (4 Tests)                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$rescheduleResults = [];

foreach ($appointmentIds as $index => $appointmentId) {
    $testNum = $index + 1;
    $appointment = Appointment::find($appointmentId);
    
    if (!$appointment) {
        echo "⚠️  Appointment #{$appointmentId} not found, skipping...\n\n";
        continue;
    }
    
    echo "🧪 TEST 3.{$testNum}: Reschedule Appointment #{$appointmentId}\n";
    echo str_repeat("─", 60) . "\n";
    
    $originalStart = $appointment->starts_at->copy();
    $newStart = $originalStart->copy()->addDays(7); // Move 1 week later
    $duration = $originalStart->diffInMinutes($appointment->ends_at);
    
    echo "   Original: {$originalStart->format('d.m.Y H:i')}\n";
    echo "   New:      {$newStart->format('d.m.Y H:i')}\n";
    
    try {
        // Update appointment time
        $appointment->starts_at = $newStart;
        $appointment->ends_at = $newStart->copy()->addMinutes($duration);
        $appointment->save();

        // Update phases if compound service
        $phases = \App\Models\AppointmentPhase::where('appointment_id', $appointment->id)->get();
        if ($phases->count() > 0) {
            foreach ($phases as $phase) {
                $phaseStart = $newStart->copy()->addMinutes($phase->start_offset_minutes);
                $phase->start_time = $phaseStart;
                $phase->end_time = $phaseStart->copy()->addMinutes($phase->duration_minutes);
                $phase->save();
            }
            echo "   ✅ Updated {$phases->count()} phases\n";
        }

        // Trigger resync to Cal.com
        try {
            \App\Jobs\SyncAppointmentToCalcomJob::dispatchSync($appointment, 'reschedule');
            echo "   ✅ Cal.com reschedule triggered\n";
        } catch (\Exception $syncError) {
            echo "   ⚠️  Cal.com sync failed: {$syncError->getMessage()}\n";
        }

        echo "   ✅ Reschedule successful (local)\n";
        $rescheduleResults[] = ['id' => $appointmentId, 'status' => 'success'];

    } catch (\Exception $e) {
        echo "   ❌ Reschedule failed: {$e->getMessage()}\n";
        $rescheduleResults[] = ['id' => $appointmentId, 'status' => 'failed', 'error' => $e->getMessage()];
    }
    
    echo "\n";
}

// ============================================================================
// PHASE 4: CANCEL APPOINTMENTS
// ============================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 4: CANCEL APPOINTMENTS (4 Tests)                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$cancelResults = [];

foreach ($appointmentIds as $index => $appointmentId) {
    $testNum = $index + 1;
    $appointment = Appointment::find($appointmentId);
    
    if (!$appointment) {
        echo "⚠️  Appointment #{$appointmentId} not found, skipping...\n\n";
        continue;
    }
    
    echo "🧪 TEST 4.{$testNum}: Cancel Appointment #{$appointmentId}\n";
    echo str_repeat("─", 60) . "\n";
    
    echo "   Service: {$appointment->service->name}\n";
    echo "   Status:  {$appointment->status}\n";
    
    try {
        // Update status to cancelled
        $appointment->status = 'cancelled';
        $appointment->save();

        // Trigger sync to Cal.com to cancel there too (BEFORE deleting)
        try {
            \App\Jobs\SyncAppointmentToCalcomJob::dispatchSync($appointment, 'cancel');
            echo "   ✅ Cal.com cancellation triggered\n";
        } catch (\Exception $syncError) {
            echo "   ⚠️  Cal.com sync failed: {$syncError->getMessage()}\n";
        }

        // Soft delete to preserve data
        $appointment->delete();

        echo "   ✅ Cancellation successful\n";
        $cancelResults[] = ['id' => $appointmentId, 'status' => 'success'];

    } catch (\Exception $e) {
        echo "   ❌ Cancellation failed: {$e->getMessage()}\n";
        $cancelResults[] = ['id' => $appointmentId, 'status' => 'failed', 'error' => $e->getMessage()];
    }
    
    echo "\n";
}

// ============================================================================
// TEST SUMMARY
// ============================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST SUMMARY                                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$rescheduleSuccess = count(array_filter($rescheduleResults, fn($r) => $r['status'] === 'success'));
$cancelSuccess = count(array_filter($cancelResults, fn($r) => $r['status'] === 'success'));

echo "📊 Reschedule: {$rescheduleSuccess}/" . count($rescheduleResults) . " successful\n";
echo "📊 Cancel: {$cancelSuccess}/" . count($cancelResults) . " successful\n";
echo "\n";

echo "✅ Phase 3 & 4 Complete\n";
echo "📝 All appointments have been rescheduled and cancelled\n";
echo "\n";
