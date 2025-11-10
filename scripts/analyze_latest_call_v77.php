<?php
/**
 * Analyze Latest Call - V77 Verification
 * Check if V77 features are working (phone/email optional)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Call;
use App\Models\Appointment;
use Carbon\Carbon;

echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  🔍 LATEST CALL ANALYSIS - V77 VERIFICATION                 ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

// Get latest call
$latestCall = Call::orderBy('created_at', 'desc')->first();

if (!$latestCall) {
    echo "❌ No calls found in database" . PHP_EOL;
    exit(1);
}

echo "═══ CALL DETAILS ═══" . PHP_EOL;
echo "Call ID: {$latestCall->call_id}" . PHP_EOL;
echo "From: {$latestCall->from_number}" . PHP_EOL;
echo "To: {$latestCall->to_number}" . PHP_EOL;
echo "Direction: {$latestCall->direction}" . PHP_EOL;
echo "Status: {$latestCall->call_status}" . PHP_EOL;
echo "Started: {$latestCall->start_timestamp}" . PHP_EOL;
echo "Duration: {$latestCall->call_length}s" . PHP_EOL;
echo "Created: {$latestCall->created_at}" . PHP_EOL;
echo PHP_EOL;

// Check if there's an appointment linked
$appointment = Appointment::where('call_id', $latestCall->call_id)->first();

if ($appointment) {
    echo "═══ APPOINTMENT DETAILS ═══" . PHP_EOL;
    echo "✅ Appointment created from this call" . PHP_EOL;
    echo PHP_EOL;
    echo "Customer Name: {$appointment->customer_name}" . PHP_EOL;
    echo "Customer Phone: {$appointment->customer_phone}" . PHP_EOL;
    echo "Customer Email: {$appointment->customer_email}" . PHP_EOL;
    echo "Service: " . ($appointment->service->name ?? 'N/A') . PHP_EOL;
    echo "Date/Time: {$appointment->start_time}" . PHP_EOL;
    echo "Duration: {$appointment->duration}min" . PHP_EOL;
    echo "Status: {$appointment->status}" . PHP_EOL;
    echo PHP_EOL;

    // V77 Verification Checks
    echo "═══ V77 VERIFICATION ═══" . PHP_EOL;

    $checks = [];

    // Check 1: Name provided (mandatory)
    $checks['Name provided (mandatory)'] = !empty($appointment->customer_name) &&
                                           $appointment->customer_name !== 'unknown';

    // Check 2: Phone is fallback if not provided
    $isFallbackPhone = $appointment->customer_phone === '+49000000000' ||
                       $appointment->customer_phone === '+490000000000';
    $isRealPhone = !$isFallbackPhone && !empty($appointment->customer_phone);

    if ($isFallbackPhone) {
        $checks['Phone: Fallback used (V77)'] = true;
        echo "✅ Phone: Fallback used (+49000000000)" . PHP_EOL;
        echo "   This is CORRECT for V77 when user didn't provide phone" . PHP_EOL;
    } elseif ($isRealPhone) {
        $checks['Phone: Real phone used'] = true;
        echo "✅ Phone: Real phone used ({$appointment->customer_phone})" . PHP_EOL;
        echo "   User provided phone or caller ID transmitted" . PHP_EOL;
    } else {
        $checks['Phone: Issue'] = false;
        echo "⚠️ Phone: {$appointment->customer_phone}" . PHP_EOL;
    }

    // Check 3: Email is fallback if not provided
    $isFallbackEmail = $appointment->customer_email === 'booking@temp.de';
    $isRealEmail = !$isFallbackEmail && !empty($appointment->customer_email) &&
                   filter_var($appointment->customer_email, FILTER_VALIDATE_EMAIL);

    if ($isFallbackEmail) {
        $checks['Email: Fallback used (V77)'] = true;
        echo "✅ Email: Fallback used (booking@temp.de)" . PHP_EOL;
        echo "   This is CORRECT for V77 when user didn't provide email" . PHP_EOL;
    } elseif ($isRealEmail) {
        $checks['Email: Real email used'] = true;
        echo "✅ Email: Real email used ({$appointment->customer_email})" . PHP_EOL;
    } else {
        echo "⚠️ Email: {$appointment->customer_email}" . PHP_EOL;
    }

    // Check 4: Appointment successfully created
    $checks['Appointment created'] = $appointment->id !== null;

    // Check 5: Status is confirmed or pending
    $checks['Valid status'] = in_array($appointment->status, ['confirmed', 'pending']);

    echo PHP_EOL;

    // Summary
    $passed = array_filter($checks, fn($v) => $v === true);
    $total = count($checks);

    echo "═══ V77 CHECK SUMMARY ═══" . PHP_EOL;
    foreach ($checks as $label => $result) {
        $status = $result ? '✅' : '❌';
        echo "{$status} {$label}" . PHP_EOL;
    }

    echo PHP_EOL;
    echo "Result: " . count($passed) . "/{$total} checks passed" . PHP_EOL;
    echo PHP_EOL;

    if (count($passed) === $total) {
        echo "✅ ✅ ✅ V77 WORKING CORRECTLY! ✅ ✅ ✅" . PHP_EOL;
        echo PHP_EOL;
        if ($isFallbackPhone || $isFallbackEmail) {
            echo "🎯 V77 Feature Confirmed:" . PHP_EOL;
            echo "   - Booking succeeded without real phone/email" . PHP_EOL;
            echo "   - Fallback values used correctly" . PHP_EOL;
            echo "   - Agent did NOT block booking" . PHP_EOL;
        }
    } else {
        echo "⚠️ Some checks failed - review above" . PHP_EOL;
    }

} else {
    echo "⚠️ No appointment linked to this call" . PHP_EOL;
    echo PHP_EOL;

    // Check if call was successful but no appointment
    if ($latestCall->call_status === 'ended') {
        echo "Call ended but no appointment created." . PHP_EOL;
        echo "Possible reasons:" . PHP_EOL;
        echo "- User hung up before completing booking" . PHP_EOL;
        echo "- Technical error during booking" . PHP_EOL;
        echo "- User was just inquiring, not booking" . PHP_EOL;
    }
}

echo PHP_EOL;

// Check Retell call logs if available
if ($latestCall->call_id) {
    echo "═══ RETELL CALL LOG CHECK ═══" . PHP_EOL;
    echo "Call ID: {$latestCall->call_id}" . PHP_EOL;
    echo "View in Retell Dashboard:" . PHP_EOL;
    echo "https://app.retellai.com/dashboard/calls/{$latestCall->call_id}" . PHP_EOL;
    echo PHP_EOL;
    echo "Check for:" . PHP_EOL;
    echo "- Agent prompts (should NOT ask for phone/email)" . PHP_EOL;
    echo "- Function calls (start_booking with fallback phone)" . PHP_EOL;
    echo "- Conversation flow (error handler behavior)" . PHP_EOL;
}

echo PHP_EOL;
echo "═══ LARAVEL LOGS (Last 50 lines for this call) ═══" . PHP_EOL;

$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $logContent = file_get_contents($logPath);
    $lines = explode("\n", $logContent);
    $relevantLines = [];

    // Find lines related to this call
    foreach ($lines as $line) {
        if (strpos($line, $latestCall->call_id) !== false ||
            strpos($line, 'start_booking') !== false ||
            strpos($line, 'fallback_phone') !== false ||
            strpos($line, 'MISSING_CUSTOMER') !== false) {
            $relevantLines[] = $line;
        }
    }

    if (!empty($relevantLines)) {
        $lastLines = array_slice($relevantLines, -50);
        foreach ($lastLines as $line) {
            // Highlight important patterns
            if (strpos($line, 'fallback_phone') !== false) {
                echo "🔵 " . trim($line) . PHP_EOL;
            } elseif (strpos($line, 'MISSING_CUSTOMER_PHONE') !== false) {
                echo "🔴 " . trim($line) . PHP_EOL;
            } elseif (strpos($line, 'start_booking') !== false) {
                echo "🟢 " . trim($line) . PHP_EOL;
            } else {
                echo "   " . trim($line) . PHP_EOL;
            }
        }
    } else {
        echo "No relevant logs found for this call" . PHP_EOL;
    }
} else {
    echo "Log file not found" . PHP_EOL;
}
