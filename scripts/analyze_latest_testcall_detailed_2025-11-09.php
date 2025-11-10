<?php
/**
 * Detailed analysis of latest test call
 * Focus on booking failure at 09:45
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

echo "=== DETAILED TEST CALL ANALYSIS ===\n\n";

// 1. Get latest call
echo "1. Finding latest call...\n";
$latestCall = Call::orderBy('created_at', 'desc')->first();

if (!$latestCall) {
    die("❌ No calls found\n");
}

echo "✅ Found call: {$latestCall->retell_call_id}\n";
echo "   Created: {$latestCall->created_at}\n";
echo "   Duration: {$latestCall->call_duration}s\n";
echo "   Status: {$latestCall->call_status}\n\n";

// 2. Check appointment
echo "2. Checking appointment...\n";
if ($latestCall->appointment_id) {
    $appointment = Appointment::find($latestCall->appointment_id);
    if ($appointment) {
        echo "✅ Appointment exists:\n";
        echo "   ID: {$appointment->id}\n";
        echo "   Date: {$appointment->appointment_date}\n";
        echo "   Time: {$appointment->appointment_time}\n";
        echo "   Status: {$appointment->status}\n";
    } else {
        echo "⚠️  Appointment ID set but record not found\n";
    }
} else {
    echo "❌ No appointment linked to this call\n";
}
echo "\n";

// 3. Extract relevant logs
echo "3. Analyzing logs...\n";
$logFile = '/var/www/api-gateway/storage/logs/laravel.log';
$retellCallId = $latestCall->retell_call_id;

// Get all logs for this call
exec("grep '{$retellCallId}' {$logFile} | tail -200", $logs);

echo "   Found " . count($logs) . " log entries\n\n";

// 4. Check for function calls
echo "4. Checking function calls...\n\n";

// Check for book_appointment or confirm_booking
$bookingLogs = array_filter($logs, function($line) {
    return (strpos($line, 'book_appointment') !== false ||
            strpos($line, 'confirm_booking') !== false ||
            strpos($line, 'start_booking') !== false);
});

if (empty($bookingLogs)) {
    echo "❌ No booking function calls found in logs\n\n";
} else {
    echo "📋 Booking-related logs:\n";
    foreach ($bookingLogs as $log) {
        echo "   " . substr($log, 0, 200) . "...\n";
    }
    echo "\n";
}

// 5. Check for errors
echo "5. Checking for errors...\n\n";

$errorLogs = array_filter($logs, function($line) {
    return (stripos($line, 'error') !== false ||
            stripos($line, 'exception') !== false ||
            stripos($line, 'failed') !== false);
});

if (empty($errorLogs)) {
    echo "✅ No errors found in logs\n\n";
} else {
    echo "🚨 ERRORS FOUND:\n";
    foreach ($errorLogs as $log) {
        echo "\n" . $log . "\n";
    }
    echo "\n";
}

// 6. Check specific function call details
echo "6. Detailed function call analysis...\n\n";

// Extract JSON payloads from logs
exec("grep -A 20 '{$retellCallId}' {$logFile} | grep -E '(book_appointment|confirm_booking|start_booking)' | tail -50", $detailedLogs);

if (!empty($detailedLogs)) {
    foreach ($detailedLogs as $log) {
        echo $log . "\n";
    }
}

// 7. Check database for related records
echo "\n7. Database checks...\n\n";

// Check for any appointments created around this time
$recentAppointments = Appointment::where('created_at', '>=', $latestCall->created_at->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentAppointments->count() > 0) {
    echo "📋 Recent appointments (last 5 minutes):\n";
    foreach ($recentAppointments as $apt) {
        echo "   ID: {$apt->id} | Date: {$apt->appointment_date} {$apt->appointment_time} | Status: {$apt->status}\n";
    }
} else {
    echo "❌ No appointments created in the last 5 minutes\n";
}

echo "\n";

// 8. Check for call_id parameter in recent requests
echo "8. Checking call_id parameters...\n\n";

exec("grep '{$retellCallId}' {$logFile} | grep 'call_id' | tail -20", $callIdLogs);

if (!empty($callIdLogs)) {
    foreach ($callIdLogs as $log) {
        // Extract call_id value
        if (preg_match('/"call_id":"([^"]+)"/', $log, $matches)) {
            $callIdValue = $matches[1];
            $status = ($callIdValue === '1') ? '❌' : '✅';
            echo "   {$status} call_id: {$callIdValue}\n";
        }
    }
} else {
    echo "   No call_id parameters found in logs\n";
}

echo "\n";

// 9. Summary
echo "=== SUMMARY ===\n\n";
echo "Call ID: {$latestCall->retell_call_id}\n";
echo "Appointment linked: " . ($latestCall->appointment_id ? 'YES' : 'NO') . "\n";
echo "Errors found: " . (count($errorLogs) > 0 ? 'YES (' . count($errorLogs) . ')' : 'NO') . "\n";
echo "Booking calls found: " . (count($bookingLogs) > 0 ? 'YES (' . count($bookingLogs) . ')' : 'NO') . "\n";

echo "\n=== END ANALYSIS ===\n";
