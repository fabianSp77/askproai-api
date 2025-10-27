<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "Importing test call: call_4005c89a073926f35dac11df600\n\n";

// Get the call from calls table
$call = DB::table('calls')->where('external_id', 'call_4005c89a073926f35dac11df600')->first();

if (!$call) {
    die("❌ Call not found\n");
}

echo "✅ Found call in database\n";
echo "   Started: {$call->start_timestamp}\n";
echo "   Duration: {$call->duration_sec}s\n";
echo "   Status: {$call->call_status}\n\n";

// Create call session
$sessionId = Str::uuid();
$startedAt = \Carbon\Carbon::parse($call->start_timestamp);
$endedAt = \Carbon\Carbon::parse($call->end_timestamp);

DB::table('retell_call_sessions')->insert([
    'id' => $sessionId,
    'call_id' => $call->external_id,
    'company_id' => $call->company_id,
    'customer_id' => null, // Was anonymous
    'agent_id' => 'agent_f1ce85d06a84afb989dfbb16a9',
    'agent_version' => 21,
    'started_at' => $startedAt,
    'ended_at' => $endedAt,
    'call_status' => 'completed',
    'disconnection_reason' => 'user_hangup',
    'duration_ms' => $call->duration_ms,
    'function_call_count' => 1, // Only initialize_call was executed
    'error_count' => 0,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Created call session: {$sessionId}\n\n";

// Create initialize_call function trace
$traceStarted = $startedAt->copy()->addMilliseconds(553);

DB::table('retell_function_traces')->insert([
    'call_session_id' => $sessionId,
    'function_name' => 'initialize_call',
    'execution_sequence' => 1,
    'started_at' => $traceStarted,
    'completed_at' => $traceStarted->copy()->addMilliseconds(1011), // 1.564s - 0.553s
    'duration_ms' => 1011,
    'input_params' => json_encode([]),
    'output_result' => json_encode([
        'success' => true,
        'call_id' => $call->external_id,
        'customer' => [
            'status' => 'anonymous',
            'message' => 'Neuer Anruf. Bitte fragen Sie nach dem Namen.'
        ]
    ]),
    'status' => 'success',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Created function trace: initialize_call (1011ms)\n\n";

// Add transcript segments
$transcripts = [
    [
        'role' => 'agent',
        'text' => 'Hallo! Willkommen bei Friseur 1. Wie kann ich Ihnen heute helfen? Möchten Sie einen neuen Termin buchen oder etwas anderes?',
        'offset' => 3251,
        'sequence' => 1,
    ],
    [
        'role' => 'user',
        'text' => 'Ja, Franz Huber mein Name. Ich hätte gern einen Herrenhaarschnitt Termin für morgen zehn Uhr.',
        'offset' => 11519,
        'sequence' => 2,
    ],
    [
        'role' => 'agent',
        'text' => 'Hallo, Herr Huber! Schön, dass Sie wieder anrufen. Ich habe Ihren Namen notiert. Lassen Sie mich kurz prüfen, ob morgen um 10 Uhr für einen Herrenhaarschnitt verfügbar ist. Einen Moment bitte...',
        'offset' => 18060,
        'sequence' => 3,
    ],
    [
        'role' => 'agent',
        'text' => 'Einen Moment bitte, ich prüfe die Verfügbarkeit für Sie...',
        'offset' => 39909,
        'sequence' => 4,
    ],
];

foreach ($transcripts as $transcript) {
    DB::table('retell_transcript_segments')->insert([
        'call_session_id' => $sessionId,
        'occurred_at' => $startedAt->copy()->addMilliseconds($transcript['offset']),
        'call_offset_ms' => $transcript['offset'],
        'segment_sequence' => $transcript['sequence'],
        'role' => $transcript['role'],
        'text' => $transcript['text'],
        'word_count' => str_word_count($transcript['text']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

echo "✅ Created " . count($transcripts) . " transcript segments\n\n";

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Import Complete!                                            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "🔗 View in Filament:\n";
echo "   https://api.askproai.de/admin/retell-call-sessions/{$sessionId}\n\n";

echo "Call Details:\n";
echo "   Call ID: {$call->external_id}\n";
echo "   Duration: {$call->duration_sec}s\n";
echo "   Functions: 1 (initialize_call)\n";
echo "   Transcript Segments: " . count($transcripts) . "\n";
