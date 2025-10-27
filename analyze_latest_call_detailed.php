#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RetellCallSession;
use App\Models\RetellFunctionTrace;
use App\Models\RetellTranscriptSegment;

echo "\n═══════════════════════════════════════════════════════════\n";
echo "LETZTER ANRUF - ULTRA-DETAILLIERTE ANALYSE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get latest call
$latestCall = RetellCallSession::orderBy('created_at', 'desc')->first();

if (!$latestCall) {
    echo "❌ Kein Call gefunden\n";
    exit(1);
}

echo "CALL OVERVIEW:\n";
echo "───────────────────────────────────────────────────────────\n";
echo "Call ID: {$latestCall->call_id}\n";
echo "Status: {$latestCall->call_status}\n";
echo "Started: {$latestCall->started_at}\n";
echo "Ended: " . ($latestCall->ended_at ?? 'N/A') . "\n";
echo "Duration: " . ($latestCall->duration_ms ?? 'N/A') . " ms\n";
echo "Disconnect Reason: " . ($latestCall->disconnection_reason ?? 'N/A') . "\n";
echo "Agent ID: {$latestCall->agent_id}\n";
echo "Agent Version: " . ($latestCall->agent_version ?? 'N/A') . "\n";
echo "Function Calls: " . ($latestCall->function_call_count ?? 0) . "\n";
echo "Transcript Segments: " . ($latestCall->transcript_segment_count ?? 0) . "\n\n";

// Get all function traces
$functions = RetellFunctionTrace::where('call_session_id', $latestCall->id)
    ->orderBy('started_at', 'asc')
    ->get();

echo "FUNCTION CALLS ({$functions->count()}):\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($functions as $idx => $func) {
    $num = $idx + 1;
    echo "[{$num}] {$func->function_name}\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "Started: {$func->started_at}\n";
    echo "Completed: " . ($func->completed_at ?? 'N/A') . "\n";

    if ($func->input_params) {
        $request = json_decode($func->input_params, true);
        echo "\nINPUT PARAMS:\n";
        echo json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    if ($func->output_result) {
        $response = json_decode($func->output_result, true);
        echo "\nOUTPUT RESULT:\n";
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "\nDuration: " . ($func->duration_ms ?? 'N/A') . " ms\n";
    echo "Status: " . ($func->status ?? 'N/A') . "\n";

    if ($func->error_details) {
        echo "❌ ERROR: {$func->error_details}\n";
    }

    echo "\n";
}

// Get transcript
$transcript = RetellTranscriptSegment::where('call_session_id', $latestCall->id)
    ->orderBy('segment_sequence', 'asc')
    ->get();

echo "\nTRANSCRIPT ({$transcript->count()} segments):\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($transcript as $segment) {
    $role = strtoupper($segment->role);
    $time = $segment->occurred_at ? $segment->occurred_at->format('H:i:s') : 'N/A';
    echo "[{$time}] {$role}: {$segment->text}\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "ANALYSE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Analyze what happened
if ($functions->count() === 0) {
    echo "❌ KEINE FUNCTIONS WURDEN AUFGERUFEN\n";
    echo "   → Agent Version ist wahrscheinlich nicht published\n";
    echo "   → Oder Flow hat keine function nodes\n\n";
}

// Check if initialize was called
$hasInitialize = $functions->where('function_name', 'initialize_call')->count() > 0;
echo ($hasInitialize ? "✅" : "❌") . " initialize_call wurde " . ($hasInitialize ? "" : "NICHT ") . "aufgerufen\n";

// Check if check_availability was called
$hasCheck = $functions->filter(function($f) {
    return str_contains($f->function_name, 'check_availability');
})->count() > 0;
echo ($hasCheck ? "✅" : "❌") . " check_availability wurde " . ($hasCheck ? "" : "NICHT ") . "aufgerufen\n";

// Check if book was called
$hasBook = $functions->filter(function($f) {
    return str_contains($f->function_name, 'book_appointment');
})->count() > 0;
echo ($hasBook ? "✅" : "❌") . " book_appointment wurde " . ($hasBook ? "" : "NICHT ") . "aufgerufen\n";

echo "\n";

// Analyze disconnect reason
if ($latestCall->disconnection_reason) {
    echo "DISCONNECT REASON:\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "{$latestCall->disconnection_reason}\n\n";
}

// Check for availability response
$checkFunc = $functions->filter(function($f) {
    return str_contains($f->function_name, 'check_availability');
})->first();

if ($checkFunc && $checkFunc->output_result) {
    $response = json_decode($checkFunc->output_result, true);
    echo "VERFÜGBARKEITS-PRÜFUNG:\n";
    echo "───────────────────────────────────────────────────────────\n";

    if (isset($response['available'])) {
        echo "Available: " . ($response['available'] ? "JA ✅" : "NEIN ❌") . "\n";
    }

    if (isset($response['alternatives'])) {
        echo "Alternativen: " . json_encode($response['alternatives'], JSON_UNESCAPED_UNICODE) . "\n";
    }

    if (isset($response['message'])) {
        echo "Message: {$response['message']}\n";
    }

    echo "\n";
}

echo "\n";
