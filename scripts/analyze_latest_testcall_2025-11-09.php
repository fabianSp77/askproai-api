<?php
/**
 * Comprehensive Analysis of Latest Test Call
 * Analyzes Agent, Backend, and Cal.com behavior
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "=== LATEST TEST CALL ANALYSIS ===\n\n";

// 1. Find latest call
echo "1. Finding latest call...\n";
$latestCall = Call::orderBy('created_at', 'desc')->first();

if (!$latestCall) {
    die("❌ No calls found in database!\n");
}

echo "✅ Found Call ID: {$latestCall->id}\n";
echo "   Retell Call ID: {$latestCall->retell_call_id}\n";
echo "   Created: {$latestCall->created_at}\n";
echo "   Status: {$latestCall->status}\n";
echo "   Customer: {$latestCall->name}\n";
echo "   Service: {$latestCall->dienstleistung}\n";
echo "   Requested Date: {$latestCall->datum_termin}\n";
echo "   Requested Time: {$latestCall->uhrzeit_termin}\n";
echo "   Appointment ID: " . ($latestCall->appointment_id ?? 'NULL') . "\n\n";

// 2. Check if appointment was created
echo "2. Checking Appointments table...\n";
$appointment = null;
if ($latestCall->appointment_id) {
    $appointment = Appointment::find($latestCall->appointment_id);
    if ($appointment) {
        echo "✅ Appointment found:\n";
        echo "   ID: {$appointment->id}\n";
        echo "   Cal.com Booking ID: {$appointment->calcom_booking_id}\n";
        echo "   Status: {$appointment->status}\n";
        echo "   Start Time: {$appointment->start_time}\n";
        echo "   Service ID: {$appointment->service_id}\n";
    } else {
        echo "❌ Appointment ID exists but record not found!\n";
    }
} else {
    echo "❌ No appointment_id in call record\n";

    // Search by call_id
    $appointment = Appointment::where('call_id', $latestCall->id)->first();
    if ($appointment) {
        echo "⚠️ Found appointment via call_id but not linked!\n";
        echo "   Appointment ID: {$appointment->id}\n";
    } else {
        echo "❌ No appointment found for this call\n";
    }
}
echo "\n";

// 3. Extract logs from Laravel log file
echo "3. Extracting logs for this call...\n";
$logFile = '/var/www/api-gateway/storage/logs/laravel.log';
$retellCallId = $latestCall->retell_call_id;

$command = "grep -A 5 -B 5 '{$retellCallId}' {$logFile} | tail -200";
$logs = shell_exec($command);

if (empty($logs)) {
    echo "❌ No logs found for call_id: {$retellCallId}\n";
} else {
    echo "✅ Found logs (last 200 lines):\n";
    echo "---BEGIN LOGS---\n";
    echo $logs;
    echo "\n---END LOGS---\n\n";
}

// 4. Check for function calls in logs
echo "4. Analyzing function calls...\n";

// Check for check_availability
$availabilityLogs = shell_exec("grep -A 10 'check_availability' {$logFile} | grep '{$retellCallId}' -A 10 -B 2 | tail -50");
if ($availabilityLogs) {
    echo "✅ check_availability was called:\n";
    echo $availabilityLogs . "\n";
} else {
    echo "⚠️ No check_availability logs found\n";
}

// Check for start_booking
$startBookingLogs = shell_exec("grep -A 10 'start_booking' {$logFile} | grep '{$retellCallId}' -A 10 -B 2 | tail -50");
if ($startBookingLogs) {
    echo "✅ start_booking was called:\n";
    echo $startBookingLogs . "\n";
} else {
    echo "⚠️ No start_booking logs found\n";
}

// Check for confirm_booking
$confirmBookingLogs = shell_exec("grep -A 10 'confirm_booking' {$logFile} | grep '{$retellCallId}' -A 10 -B 2 | tail -50");
if ($confirmBookingLogs) {
    echo "✅ confirm_booking was called:\n";
    echo $confirmBookingLogs . "\n";
} else {
    echo "⚠️ No confirm_booking logs found\n";
}

echo "\n";

// 5. Check for errors
echo "5. Checking for errors...\n";
$errorLogs = shell_exec("grep -i 'error\|failed\|exception' {$logFile} | grep '{$retellCallId}' -B 2 -A 5 | tail -50");
if ($errorLogs) {
    echo "❌ ERRORS FOUND:\n";
    echo $errorLogs . "\n\n";
} else {
    echo "✅ No errors found for this call\n\n";
}

// 6. Check Cal.com calls
echo "6. Checking Cal.com API calls...\n";
$calcomLogs = shell_exec("grep -i 'cal\.com\|calcom\|slots\|booking' {$logFile} | grep '{$retellCallId}' -B 2 -A 5 | tail -100");
if ($calcomLogs) {
    echo "✅ Cal.com interactions found:\n";
    echo $calcomLogs . "\n\n";
} else {
    echo "⚠️ No Cal.com logs found\n\n";
}

// 7. Summary
echo "=== SUMMARY ===\n\n";

$issues = [];

if (!$latestCall->appointment_id) {
    $issues[] = "No appointment_id in call record";
}

if (!$appointment) {
    $issues[] = "No appointment created";
}

if ($latestCall->status !== 'completed') {
    $issues[] = "Call status is '{$latestCall->status}', not 'completed'";
}

if (empty($issues)) {
    echo "✅ No obvious issues detected - call appears successful\n";
} else {
    echo "❌ ISSUES DETECTED:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
    echo "\n";
}

echo "=== NEXT STEPS ===\n\n";
echo "1. Review logs above for error messages\n";
echo "2. Check if exact time '08:50' was available in Cal.com\n";
echo "3. Verify backend returned correct 'available' field\n";
echo "4. Check if Agent followed correct edge (should go to func_start_booking if available)\n";
echo "\n=== END ANALYSIS ===\n";
