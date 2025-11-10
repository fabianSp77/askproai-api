<?php

/**
 * Diagnostic Script: Find Orphaned Cal.com Bookings
 *
 * PURPOSE: Identify bookings that exist in Cal.com but not in local database
 * USAGE: php scripts/diagnose_orphaned_bookings.php
 *
 * This script helps diagnose the root cause of the 67% booking failure rate
 * by analyzing the state of Cal.com vs local database.
 *
 * CREATED: 2025-11-05 (Phase 1 - SAGA Pattern Implementation)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Orphaned Cal.com Bookings Diagnostic Tool\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// STEP 1: Analyze local database appointments
echo "📊 STEP 1: Analyzing local database appointments...\n";
echo "───────────────────────────────────────────────────────────────\n";

$totalAppointments = DB::table('appointments')->count();
$appointmentsWithCalcomId = DB::table('appointments')
    ->whereNotNull('calcom_v2_booking_id')
    ->count();

$appointmentsWithoutCalcomId = $totalAppointments - $appointmentsWithCalcomId;

$failedAppointments = DB::table('appointments')
    ->where('status', 'failed')
    ->whereNotNull('calcom_v2_booking_id')
    ->count();

echo "Total appointments: {$totalAppointments}\n";
echo "  - With Cal.com ID: {$appointmentsWithCalcomId}\n";
echo "  - Without Cal.com ID: {$appointmentsWithoutCalcomId}\n";
echo "  - Failed (with Cal.com ID): {$failedAppointments}\n";
echo "\n";

if ($failedAppointments > 0) {
    echo "⚠️  WARNING: {$failedAppointments} appointments marked as 'failed' but have Cal.com booking IDs!\n";
    echo "   These are likely orphaned bookings that need cleanup.\n";
    echo "\n";
}

// STEP 2: Analyze recent bookings (last 7 days)
echo "📊 STEP 2: Analyzing recent bookings (last 7 days)...\n";
echo "───────────────────────────────────────────────────────────────\n";

$recentStats = DB::table('appointments')
    ->selectRaw("
        DATE(created_at) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as successful,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN status = 'failed' AND calcom_v2_booking_id IS NOT NULL THEN 1 ELSE 0 END) as orphaned,
        ROUND(100.0 * SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) / COUNT(*), 2) as success_rate
    ")
    ->where('created_at', '>=', Carbon::now()->subDays(7))
    ->groupBy('date')
    ->orderBy('date', 'DESC')
    ->get();

echo "\nDate          Total  Success  Failed  Orphaned  Success Rate\n";
echo "───────────────────────────────────────────────────────────────\n";

foreach ($recentStats as $stat) {
    printf(
        "%s    %3d     %3d      %3d       %3d      %6.2f%%\n",
        $stat->date,
        $stat->total,
        $stat->successful,
        $stat->failed,
        $stat->orphaned,
        $stat->success_rate ?? 0
    );
}
echo "\n";

// STEP 3: Identify specific orphaned bookings
echo "📊 STEP 3: Identifying specific orphaned bookings...\n";
echo "───────────────────────────────────────────────────────────────\n";

$orphanedBookings = DB::table('appointments')
    ->select(
        'id',
        'calcom_v2_booking_id',
        'customer_id',
        'service_id',
        'starts_at',
        'created_at',
        'status'
    )
    ->where('status', 'failed')
    ->whereNotNull('calcom_v2_booking_id')
    ->where('created_at', '>=', Carbon::now()->subDays(7))
    ->orderBy('created_at', 'DESC')
    ->limit(20)
    ->get();

if ($orphanedBookings->isEmpty()) {
    echo "✅ No orphaned bookings found in the last 7 days!\n";
} else {
    echo "⚠️  Found {$orphanedBookings->count()} orphaned bookings (showing last 20):\n\n";
    echo "ID    Cal.com Booking ID              Starts At            Created At\n";
    echo "───────────────────────────────────────────────────────────────────────────\n";

    foreach ($orphanedBookings as $booking) {
        printf(
            "%-5d %-30s  %s  %s\n",
            $booking->id,
            $booking->calcom_v2_booking_id,
            $booking->starts_at,
            $booking->created_at
        );
    }
    echo "\n";
}

// STEP 4: Check for database errors in logs
echo "📊 STEP 4: Analyzing recent database errors...\n";
echo "───────────────────────────────────────────────────────────────\n";

$logFile = storage_path('logs/laravel.log');

if (!file_exists($logFile)) {
    echo "⚠️  Log file not found: {$logFile}\n";
} else {
    $logSize = filesize($logFile);
    $logSizeMB = round($logSize / 1024 / 1024, 2);
    echo "Log file size: {$logSizeMB} MB\n";

    // Search for SAGA compensation messages in last 1000 lines
    $lines = shell_exec("tail -n 1000 {$logFile} | grep -c 'SAGA Compensation'");
    $sagaCount = (int)trim($lines);

    $failedSaga = shell_exec("tail -n 1000 {$logFile} | grep -c 'SAGA Compensation FAILED'");
    $failedSagaCount = (int)trim($failedSaga);

    $successSaga = shell_exec("tail -n 1000 {$logFile} | grep -c 'SAGA Compensation successful'");
    $successSagaCount = (int)trim($successSaga);

    echo "\nLast 1000 log lines analysis:\n";
    echo "  - Total SAGA compensation attempts: {$sagaCount}\n";
    echo "  - Successful compensations: {$successSagaCount}\n";
    echo "  - Failed compensations: {$failedSagaCount}\n";

    if ($failedSagaCount > 0) {
        echo "\n⚠️  WARNING: {$failedSagaCount} SAGA compensations failed!\n";
        echo "   These bookings need manual cleanup in Cal.com dashboard.\n";
    }
    echo "\n";
}

// STEP 5: Recommendations
echo "═══════════════════════════════════════════════════════════════\n";
echo "  RECOMMENDATIONS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$totalOrphaned = $failedAppointments;

if ($totalOrphaned === 0) {
    echo "✅ No action needed - System is healthy!\n";
} elseif ($totalOrphaned < 10) {
    echo "⚠️  LOW PRIORITY: {$totalOrphaned} orphaned bookings found.\n";
    echo "\n";
    echo "Action: Manual cleanup recommended\n";
    echo "1. Login to Cal.com dashboard\n";
    echo "2. Search for each booking ID listed above\n";
    echo "3. Cancel each booking with reason: 'Database sync failed'\n";
} elseif ($totalOrphaned < 50) {
    echo "🔶 MEDIUM PRIORITY: {$totalOrphaned} orphaned bookings found.\n";
    echo "\n";
    echo "Action: Run automated cleanup\n";
    echo "1. Verify OrphanedBookingCleanupJob is registered\n";
    echo "2. Run: php artisan queue:work --queue=high\n";
    echo "3. Dispatch cleanup jobs for each orphaned booking\n";
    echo "4. Monitor logs for compensation results\n";
} else {
    echo "🚨 HIGH PRIORITY: {$totalOrphaned} orphaned bookings found!\n";
    echo "\n";
    echo "Action: IMMEDIATE intervention required\n";
    echo "1. Check database connection stability\n";
    echo "2. Review recent code changes to AppointmentCreationService\n";
    echo "3. Run automated cleanup for past bookings\n";
    echo "4. Enable enhanced error logging\n";
    echo "5. Monitor for new failures\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Diagnostic Complete\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// OPTIONAL: Output orphaned booking IDs to file for bulk cleanup
if ($totalOrphaned > 0) {
    $outputFile = storage_path('logs/orphaned_booking_ids.txt');
    $bookingIds = $orphanedBookings->pluck('calcom_v2_booking_id')->toArray();
    file_put_contents($outputFile, implode("\n", $bookingIds));
    echo "📄 Orphaned booking IDs saved to: {$outputFile}\n";
    echo "   Use this file for bulk cleanup if needed.\n\n";
}
