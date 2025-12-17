<?php

/**
 * E2E Booking Flow Test
 *
 * Tests:
 * 1. 2x Compound Service Bookings (Dauerwelle, Ansatzfärbung)
 * 2. 3x Simple Service Bookings (Herrenhaarschnitt, Damenhaarschnitt, Föhnen)
 * 3. Reschedule all appointments
 * 4. Cancel all appointments
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\AppointmentPhase;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Company;
use App\Jobs\SyncAppointmentToCalcomJob;
use Carbon\Carbon;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         E2E BOOKING FLOW TEST - COMPREHENSIVE SUITE           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Setup
$company = Company::find(1);
$branch = \App\Models\Branch::where('company_id', $company->id)->first();
$customer = Customer::where('company_id', $company->id)->first();
$staff = Staff::where('company_id', $company->id)->where('is_active', true)->first();
$branchId = $branch->id;

if (!$customer) {
    echo "❌ No customer found. Creating test customer...\n";
    $customer = Customer::unguarded(function() use ($company, $branchId) {
        return Customer::create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'name' => 'E2E Test Customer',
            'email' => 'e2e-test@example.com',
            'phone' => '+491234567890',
        ]);
    });
}

echo "📊 Test Configuration:\n";
echo "   Company: {$company->name} (ID: {$company->id})\n";
echo "   Customer: {$customer->name} (ID: {$customer->id})\n";
echo "   Staff: {$staff->name} (ID: {$staff->id})\n";
echo "   Parallel Booking: " . (config('features.parallel_calcom_booking') ? 'ENABLED ✅' : 'DISABLED ❌') . "\n";
echo "\n";

$testResults = [
    'compound_bookings' => [],
    'simple_bookings' => [],
    'reschedules' => [],
    'cancellations' => [],
    'performance' => [],
];

// ============================================================================
// PHASE 1: COMPOUND SERVICE BOOKINGS
// ============================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 1: COMPOUND SERVICE BOOKINGS (2 Tests)                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test 1.1: Dauerwelle (4 segments)
echo "🧪 TEST 1.1: Dauerwelle Booking\n";
echo str_repeat("─", 60) . "\n";

$dauerwelleService = Service::where('company_id', $company->id)
    ->where('name', 'Dauerwelle')
    ->first();

if (!$dauerwelleService) {
    echo "⚠️  Dauerwelle service not found, skipping...\n\n";
} else {
    $startTime = Carbon::parse('2025-12-10 14:00:00');

    $appointment1 = Appointment::unguarded(function() use ($company, $branchId, $customer, $dauerwelleService, $staff, $startTime) {
        return Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'customer_id' => $customer->id,
            'service_id' => $dauerwelleService->id,
            'staff_id' => $staff->id,
            'starts_at' => $startTime,
            'ends_at' => $startTime->copy()->addMinutes(90),
            'status' => 'confirmed',
            'source' => 'test_e2e',
        ]);
    });

    // Create phases for compound service
    $phases = [
        ['segment_key' => 'A', 'segment_name' => 'Haare wickeln', 'duration' => 25, 'offset' => 0],
        ['segment_key' => 'B', 'segment_name' => 'Fixierung auftragen', 'duration' => 15, 'offset' => 25],
        ['segment_key' => 'C', 'segment_name' => 'Auswaschen & Pflege', 'duration' => 15, 'offset' => 40],
        ['segment_key' => 'D', 'segment_name' => 'Schneiden & Styling', 'duration' => 15, 'offset' => 55],
    ];

    foreach ($phases as $phaseData) {
        AppointmentPhase::create([
            'appointment_id' => $appointment1->id,
            'phase_type' => 'initial',
            'segment_key' => $phaseData['segment_key'],
            'segment_name' => $phaseData['segment_name'],
            'sequence_order' => ord($phaseData['segment_key']) - ord('A') + 1,
            'start_offset_minutes' => $phaseData['offset'],
            'duration_minutes' => $phaseData['duration'],
            'staff_required' => true,
            'start_time' => $startTime->copy()->addMinutes($phaseData['offset']),
            'end_time' => $startTime->copy()->addMinutes($phaseData['offset'] + $phaseData['duration']),
            'calcom_sync_status' => 'pending',
        ]);
    }

    echo "   Created appointment #{$appointment1->id}\n";
    echo "   Service: {$dauerwelleService->name}\n";
    echo "   Start: {$startTime->format('d.m.Y H:i')}\n";
    echo "   Phases: 4 segments (A, B, C, D)\n";
    echo "\n";

    // Sync to Cal.com
    echo "   🔄 Syncing to Cal.com...\n";
    $syncStart = microtime(true);

    try {
        SyncAppointmentToCalcomJob::dispatchSync($appointment1, 'create');
        $syncDuration = round((microtime(true) - $syncStart) * 1000, 2);

        $appointment1->refresh();
        $syncedPhases = $appointment1->phases()->where('calcom_sync_status', 'synced')->count();
        $failedPhases = $appointment1->phases()->where('calcom_sync_status', 'failed')->count();

        echo "   ✅ Sync completed in {$syncDuration}ms\n";
        echo "   Result: {$syncedPhases}/4 synced, {$failedPhases}/4 failed\n";
        echo "   Status: {$appointment1->calcom_sync_status}\n";

        $testResults['compound_bookings'][] = [
            'id' => $appointment1->id,
            'service' => 'Dauerwelle',
            'success' => $syncedPhases > 0,
            'duration_ms' => $syncDuration,
            'synced_phases' => $syncedPhases,
            'failed_phases' => $failedPhases,
        ];

        $testResults['performance'][] = [
            'operation' => 'Compound Booking (4 segments)',
            'duration_ms' => $syncDuration,
        ];

    } catch (\Exception $e) {
        echo "   ❌ Sync failed: {$e->getMessage()}\n";
        $testResults['compound_bookings'][] = [
            'id' => $appointment1->id,
            'service' => 'Dauerwelle',
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }

    echo "\n";
}

// Test 1.2: Ansatzfärbung (4 segments) - ONLY IF SERVICE EXISTS
echo "🧪 TEST 1.2: Ansatzfärbung Booking\n";
echo str_repeat("─", 60) . "\n";

$ansatzService = Service::where('company_id', $company->id)
    ->where('name', 'Ansatzfärbung')
    ->first();

if (!$ansatzService) {
    echo "⚠️  Ansatzfärbung service not found, skipping...\n\n";
} else {
    $startTime = Carbon::parse('2025-12-11 10:00:00');

    $appointment2 = Appointment::unguarded(function() use ($company, $branchId, $customer, $ansatzService, $staff, $startTime) {
        return Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'customer_id' => $customer->id,
            'service_id' => $ansatzService->id,
            'staff_id' => $staff->id,
            'starts_at' => $startTime,
            'ends_at' => $startTime->copy()->addMinutes(80),
            'status' => 'confirmed',
            'source' => 'test_e2e',
        ]);
    });

    $phases = [
        ['segment_key' => 'A', 'segment_name' => 'Ansatzfärbung auftragen', 'duration' => 20, 'offset' => 0],
        ['segment_key' => 'B', 'segment_name' => 'Auswaschen', 'duration' => 15, 'offset' => 20],
        ['segment_key' => 'C', 'segment_name' => 'Haarschnitt', 'duration' => 25, 'offset' => 35],
        ['segment_key' => 'D', 'segment_name' => 'Föhnen & Styling', 'duration' => 20, 'offset' => 60],
    ];

    foreach ($phases as $phaseData) {
        AppointmentPhase::create([
            'appointment_id' => $appointment2->id,
            'phase_type' => 'initial',
            'segment_key' => $phaseData['segment_key'],
            'segment_name' => $phaseData['segment_name'],
            'sequence_order' => ord($phaseData['segment_key']) - ord('A') + 1,
            'start_offset_minutes' => $phaseData['offset'],
            'duration_minutes' => $phaseData['duration'],
            'staff_required' => true,
            'start_time' => $startTime->copy()->addMinutes($phaseData['offset']),
            'end_time' => $startTime->copy()->addMinutes($phaseData['offset'] + $phaseData['duration']),
            'calcom_sync_status' => 'pending',
        ]);
    }

    echo "   Created appointment #{$appointment2->id}\n";
    echo "   Service: {$ansatzService->name}\n";
    echo "   Start: {$startTime->format('d.m.Y H:i')}\n";
    echo "   Phases: 4 segments\n";
    echo "\n";

    echo "   🔄 Syncing to Cal.com...\n";
    $syncStart = microtime(true);

    try {
        SyncAppointmentToCalcomJob::dispatchSync($appointment2, 'create');
        $syncDuration = round((microtime(true) - $syncStart) * 1000, 2);

        $appointment2->refresh();
        $syncedPhases = $appointment2->phases()->where('calcom_sync_status', 'synced')->count();
        $failedPhases = $appointment2->phases()->where('calcom_sync_status', 'failed')->count();

        echo "   ✅ Sync completed in {$syncDuration}ms\n";
        echo "   Result: {$syncedPhases}/4 synced, {$failedPhases}/4 failed\n";

        $testResults['compound_bookings'][] = [
            'id' => $appointment2->id,
            'service' => 'Ansatzfärbung',
            'success' => $syncedPhases > 0,
            'duration_ms' => $syncDuration,
            'synced_phases' => $syncedPhases,
        ];

        $testResults['performance'][] = [
            'operation' => 'Compound Booking (4 segments)',
            'duration_ms' => $syncDuration,
        ];

    } catch (\Exception $e) {
        echo "   ❌ Sync failed: {$e->getMessage()}\n";
        $testResults['compound_bookings'][] = [
            'id' => $appointment2->id,
            'service' => 'Ansatzfärbung',
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }

    echo "\n";
}

// ============================================================================
// PHASE 2: SIMPLE SERVICE BOOKINGS
// ============================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 2: SIMPLE SERVICE BOOKINGS (3 Tests)                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$simpleServices = [
    ['name' => 'Herrenhaarschnitt', 'duration' => 30, 'date' => '2025-12-12 15:00:00'],
    ['name' => 'Damenhaarschnitt', 'duration' => 45, 'date' => '2025-12-13 11:00:00'],
    ['name' => 'Föhnen & Styling', 'duration' => 20, 'date' => '2025-12-14 16:30:00'],
];

foreach ($simpleServices as $index => $serviceData) {
    $testNum = $index + 1;
    echo "🧪 TEST 2.{$testNum}: {$serviceData['name']} Booking\n";
    echo str_repeat("─", 60) . "\n";

    $service = Service::where('company_id', $company->id)
        ->where('name', $serviceData['name'])
        ->first();

    if (!$service) {
        echo "   ⚠️  Service '{$serviceData['name']}' not found, skipping...\n\n";
        continue;
    }

    $startTime = Carbon::parse($serviceData['date']);

    $appointment = Appointment::unguarded(function() use ($company, $branchId, $customer, $service, $staff, $startTime, $serviceData) {
        return Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'starts_at' => $startTime,
            'ends_at' => $startTime->copy()->addMinutes($serviceData['duration']),
            'status' => 'confirmed',
            'source' => 'test_e2e',
        ]);
    });

    echo "   Created appointment #{$appointment->id}\n";
    echo "   Service: {$service->name}\n";
    echo "   Start: {$startTime->format('d.m.Y H:i')}\n";
    echo "   Duration: {$serviceData['duration']}min\n";
    echo "\n";

    echo "   🔄 Syncing to Cal.com...\n";
    $syncStart = microtime(true);

    try {
        SyncAppointmentToCalcomJob::dispatchSync($appointment, 'create');
        $syncDuration = round((microtime(true) - $syncStart) * 1000, 2);

        $appointment->refresh();

        echo "   ✅ Sync completed in {$syncDuration}ms\n";
        echo "   Status: {$appointment->calcom_sync_status}\n";

        $testResults['simple_bookings'][] = [
            'id' => $appointment->id,
            'service' => $service->name,
            'success' => $appointment->calcom_sync_status === 'synced',
            'duration_ms' => $syncDuration,
        ];

        $testResults['performance'][] = [
            'operation' => 'Simple Booking',
            'duration_ms' => $syncDuration,
        ];

    } catch (\Exception $e) {
        echo "   ❌ Sync failed: {$e->getMessage()}\n";
        $testResults['simple_bookings'][] = [
            'id' => $appointment->id,
            'service' => $service->name,
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }

    echo "\n";
}

// ============================================================================
// SUMMARY
// ============================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST SUMMARY                                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$compoundSuccess = count(array_filter($testResults['compound_bookings'], fn($r) => $r['success'] ?? false));
$compoundTotal = count($testResults['compound_bookings']);

$simpleSuccess = count(array_filter($testResults['simple_bookings'], fn($r) => $r['success']));
$simpleTotal = count($testResults['simple_bookings']);

echo "📊 Compound Bookings: {$compoundSuccess}/{$compoundTotal} successful\n";
echo "📊 Simple Bookings: {$simpleSuccess}/{$simpleTotal} successful\n";
echo "\n";

if (!empty($testResults['performance'])) {
    $avgDuration = round(array_sum(array_column($testResults['performance'], 'duration_ms')) / count($testResults['performance']), 2);
    echo "⚡ Average Sync Duration: {$avgDuration}ms\n";

    $compoundPerf = array_filter($testResults['performance'], fn($p) => str_contains($p['operation'], 'Compound'));
    if (!empty($compoundPerf)) {
        $avgCompound = round(array_sum(array_column($compoundPerf, 'duration_ms')) / count($compoundPerf), 2);
        echo "⚡ Average Compound Sync: {$avgCompound}ms\n";
    }

    $simplePerf = array_filter($testResults['performance'], fn($p) => str_contains($p['operation'], 'Simple'));
    if (!empty($simplePerf)) {
        $avgSimple = round(array_sum(array_column($simplePerf, 'duration_ms')) / count($simplePerf), 2);
        echo "⚡ Average Simple Sync: {$avgSimple}ms\n";
    }
}

echo "\n";
echo "✅ Phase 1 & 2 Complete - Appointments created and synced to Cal.com\n";
echo "📅 Check Cal.com calendar to verify bookings appear correctly\n";
echo "\n";

// Save appointment IDs for next phases
$appointmentIds = array_merge(
    array_column($testResults['compound_bookings'], 'id'),
    array_column($testResults['simple_bookings'], 'id')
);

file_put_contents('/tmp/e2e_test_appointments.json', json_encode([
    'appointment_ids' => array_filter($appointmentIds),
    'customer_id' => $customer->id,
    'created_at' => now()->toIso8601String(),
]));

echo "💾 Appointment IDs saved to /tmp/e2e_test_appointments.json\n";
echo "\n";
