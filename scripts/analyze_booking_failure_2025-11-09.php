<?php
/**
 * Analyze why booking failed
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\Appointment;

$callId = $argv[1] ?? null;

if (!$callId) {
    $call = Call::orderBy('created_at', 'desc')->first();
    $callId = $call->retell_call_id;
} else {
    $call = Call::where('retell_call_id', $callId)->first();
}

echo "=== BOOKING FAILURE ANALYSIS ===\n\n";
echo "Call ID: {$callId}\n";
echo "Created: {$call->created_at}\n\n";

// Check appointment
if ($call->appointment_id) {
    echo "✅ Appointment linked: {$call->appointment_id}\n";
    $appointment = Appointment::find($call->appointment_id);
    if ($appointment) {
        echo "   Status: {$appointment->status}\n";
        echo "   Customer: {$appointment->customer_name}\n";
        echo "   Service: {$appointment->service->name}\n";
        echo "   Time: {$appointment->start_time}\n\n";
    }
} else {
    echo "❌ No appointment linked\n\n";
}

// Get raw data
$raw = is_string($call->raw) ? json_decode($call->raw, true) : $call->raw;

// Find start_booking and confirm_booking tool calls
echo "=== TOOL CALLS ===\n\n";

if (isset($raw['transcript_with_tool_calls'])) {
    foreach ($raw['transcript_with_tool_calls'] as $event) {
        if ($event['role'] === 'tool_call_invocation' && $event['name'] === 'start_booking') {
            echo "start_booking:\n";
            $args = json_decode($event['arguments'], true);
            echo "  call_id: " . ($args['call_id'] ?? 'MISSING') . "\n";
            echo "  customer_name: " . ($args['customer_name'] ?? 'MISSING') . "\n";
            echo "  customer_phone: " . ($args['customer_phone'] ?? 'MISSING') . "\n";
            echo "  service: " . ($args['service'] ?? 'MISSING') . "\n";
            echo "  datetime: " . ($args['datetime'] ?? 'MISSING') . "\n\n";
        }

        if ($event['role'] === 'tool_call_result' && isset($event['tool_call_id'])) {
            // Check if this is for start_booking
            $content = json_decode($event['content'], true);
            if (isset($content['data']['next_action']) && $content['data']['next_action'] === 'confirm_booking') {
                echo "start_booking result:\n";
                echo "  success: " . ($content['success'] ? 'YES' : 'NO') . "\n";
                echo "  message: " . ($content['message'] ?? 'N/A') . "\n\n";
            }
        }

        if ($event['role'] === 'tool_call_invocation' && $event['name'] === 'confirm_booking') {
            echo "confirm_booking:\n";
            $args = json_decode($event['arguments'], true);
            echo "  call_id: " . ($args['call_id'] ?? 'MISSING') . "\n";
            echo "  function_name: " . ($args['function_name'] ?? 'MISSING') . "\n\n";
        }

        if ($event['role'] === 'tool_call_result' && isset($event['content'])) {
            $content = json_decode($event['content'], true);
            if (isset($content['error']) && $content['error'] === 'Fehler bei der Terminbuchung') {
                echo "confirm_booking result:\n";
                echo "  success: NO ❌\n";
                echo "  error: {$content['error']}\n";

                if (isset($content['context'])) {
                    echo "  context:\n";
                    foreach ($content['context'] as $key => $value) {
                        if (is_scalar($value)) {
                            echo "    {$key}: {$value}\n";
                        }
                    }
                }
                echo "\n";
            }
        }
    }
}

// Check Laravel logs for errors
echo "=== CHECKING LOGS ===\n\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);

    // Find logs related to this call
    $lines = explode("\n", $logs);
    $relevantLogs = [];

    foreach ($lines as $line) {
        if (strpos($line, $callId) !== false || strpos($line, 'confirm_booking') !== false) {
            $relevantLogs[] = $line;
        }
    }

    if (count($relevantLogs) > 0) {
        echo "Found " . count($relevantLogs) . " relevant log entries\n";
        echo "Last 10 entries:\n\n";

        $last10 = array_slice($relevantLogs, -10);
        foreach ($last10 as $log) {
            if (strlen($log) > 200) {
                $log = substr($log, 0, 200) . '...';
            }
            echo $log . "\n";
        }
    } else {
        echo "No relevant log entries found for this call\n";
    }
}

echo "\n=== PROBABLE CAUSE ===\n\n";

echo "The booking failed because:\n";
echo "1. call_id = \"1\" instead of real call ID\n";
echo "2. Backend cannot find booking data for call_id=\"1\"\n";
echo "3. confirm_booking fails with generic error\n\n";

echo "Root cause: parameter_mapping not working\n";
echo "Even though V103 is published, calls still send call_id=\"1\"\n\n";

echo "=== END ANALYSIS ===\n";
