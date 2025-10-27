<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 DEBUG LATEST RETELL CALLS\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Get latest RetellCallSessions
$sessions = \App\Models\RetellCallSession::orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "📊 Latest 5 Call Sessions:\n\n";

if ($sessions->isEmpty()) {
    echo "❌ NO CALL SESSIONS FOUND!\n\n";
} else {
    foreach ($sessions as $session) {
        echo "─────────────────────────────────────────\n";
        echo "ID: " . $session->id . "\n";
        echo "Call ID: " . ($session->call_id ?? 'N/A') . "\n";
        echo "Agent ID: " . ($session->agent_id ?? 'N/A') . "\n";
        echo "From: " . ($session->from_number ?? 'N/A') . "\n";
        echo "To: " . ($session->to_number ?? 'N/A') . "\n";
        echo "Status: " . ($session->call_status ?? 'N/A') . "\n";
        echo "Direction: " . ($session->direction ?? 'N/A') . "\n";
        echo "Created: " . $session->created_at->format('Y-m-d H:i:s') . "\n";
        echo "Updated: " . $session->updated_at->format('Y-m-d H:i:s') . "\n\n";

        // Check for events
        $events = \App\Models\RetellCallEvent::where('call_session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->get();

        echo "Events: " . $events->count() . "\n";
        foreach ($events as $event) {
            echo "  - " . $event->event_type . " (" . $event->created_at->format('H:i:s') . ")\n";
        }
        echo "\n";

        // Check for function traces
        $traces = \App\Models\RetellFunctionTrace::where('call_session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->get();

        echo "Function Traces: " . $traces->count() . "\n";
        foreach ($traces as $trace) {
            echo "  - " . $trace->function_name . " → " . $trace->status . "\n";
        }
        echo "\n";
    }
}

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 CHECKING DATABASE TABLES\n";
echo "═══════════════════════════════════════════════════════\n\n";

$sessionCount = \App\Models\RetellCallSession::count();
$eventCount = \App\Models\RetellCallEvent::count();
$traceCount = \App\Models\RetellFunctionTrace::count();
$transcriptCount = \App\Models\RetellTranscriptSegment::count();

echo "Total Call Sessions: $sessionCount\n";
echo "Total Call Events: $eventCount\n";
echo "Total Function Traces: $traceCount\n";
echo "Total Transcript Segments: $transcriptCount\n\n";

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 CHECKING WEBHOOK LOGS\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Check latest webhook calls in logs
exec('tail -100 storage/logs/laravel.log | grep -i retell', $output);

if (empty($output)) {
    echo "❌ NO RETELL WEBHOOK LOGS FOUND!\n";
    echo "This means webhooks are NOT reaching the backend!\n\n";
} else {
    echo "✅ Found webhook activity:\n";
    foreach (array_slice($output, -10) as $line) {
        echo "  " . $line . "\n";
    }
}

echo "\n";
