#!/usr/bin/env php
<?php

/**
 * Detailed Test Call Analysis
 * Analyzes transcript, function calls, data flow
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use Carbon\Carbon;

$callId = trim(file_get_contents('/tmp/latest_test_call_id.txt'));

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " DETAILED TEST CALL ANALYSIS\n";
echo " Call ID: {$callId}\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$call = Call::where('retell_call_id', $callId)->first();

if (!$call) {
    echo "❌ Call nicht gefunden\n";
    exit(1);
}

echo "📞 Call Metadata:\n";
echo "   Zeit: " . $call->created_at->format('Y-m-d H:i:s') . " Uhr\n";
echo "   Dauer: " . ($call->duration_sec ?? 'N/A') . " Sekunden\n";
echo "   Status: " . $call->call_status . "\n";
echo "\n";

// Parse raw data
$raw = is_string($call->raw) ? json_decode($call->raw, true) : $call->raw;

echo "═══════════════════════════════════════════════════════════════\n";
echo " VOLLSTÄNDIGES TRANSKRIPT\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
echo $call->transcript . "\n";
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo " FUNCTION CALLS ANALYSE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Extract function calls from transcript
$lines = explode("\n", $call->transcript);
$functionCalls = [];
$currentTime = null;

foreach ($lines as $line) {
    // Look for function call patterns
    if (preg_match('/\[(\d{2}:\d{2}:\d{2})\]/', $line, $timeMatch)) {
        $currentTime = $timeMatch[1];
    }

    if (strpos($line, 'Function:') !== false || strpos($line, 'Tool:') !== false) {
        $functionCalls[] = [
            'time' => $currentTime,
            'line' => $line
        ];
    }
}

if (count($functionCalls) > 0) {
    echo "📋 Function Calls im Transkript:\n\n";
    foreach ($functionCalls as $fc) {
        echo "[{$fc['time']}] {$fc['line']}\n";
    }
} else {
    echo "⚠️  Keine Function Calls im Transkript gefunden\n";
}

echo "\n";

// Check for tool calls in raw data
if (isset($raw['tool_calls']) && is_array($raw['tool_calls'])) {
    echo "📋 Tool Calls (Raw Data):\n\n";
    foreach ($raw['tool_calls'] as $idx => $toolCall) {
        echo "Tool Call #" . ($idx + 1) . ":\n";
        echo "  Function: " . ($toolCall['name'] ?? 'unknown') . "\n";
        echo "  Parameters: " . json_encode($toolCall['arguments'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        echo "  Response: " . json_encode($toolCall['result'] ?? 'no result', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        echo "\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo " PROBLEM ANALYSE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Problem 1: Preise und Dauer bei Service-Disambiguierung
echo "🔍 Problem 1: Preise/Dauer in Service-Disambiguierung\n";
$transcript = $call->transcript;
if (preg_match('/Herrenhaarschnitt.*?(\d+).*?Min.*?(\d+).*?€/i', $transcript, $match1) ||
    preg_match('/Damenhaarschnitt.*?(\d+).*?Min.*?(\d+).*?€/i', $transcript, $match2)) {
    echo "   ❌ GEFUNDEN: Agent nennt Preise/Dauer bei Service-Frage\n";
} else {
    echo "   ✅ OK: Keine Preise/Dauer gefunden\n";
}

echo "\n";

// Problem 2: Termine in der Vergangenheit
echo "🔍 Problem 2: Termine in der Vergangenheit\n";
$now = Carbon::now('Europe/Berlin');
echo "   Aktuelle Zeit: " . $now->format('H:i') . " Uhr\n";

// Look for time mentions in transcript
if (preg_match_all('/(\d{1,2}):(\d{2})(?:\s*Uhr)?/i', $transcript, $timeMatches, PREG_SET_ORDER)) {
    echo "   Gefundene Uhrzeiten im Transkript:\n";
    foreach ($timeMatches as $tm) {
        $hour = (int)$tm[1];
        $minute = (int)$tm[2];
        $timeStr = sprintf('%02d:%02d', $hour, $minute);

        $mentionedTime = Carbon::createFromTime($hour, $minute, 0, 'Europe/Berlin');
        $isPast = $mentionedTime->lt($now);

        echo "      - {$timeStr} Uhr";
        if ($isPast) {
            echo " ❌ IN DER VERGANGENHEIT (jetzt: " . $now->format('H:i') . ")";
        } else {
            echo " ✅ In der Zukunft";
        }
        echo "\n";
    }
}

echo "\n";

// Save detailed analysis
$analysis = [
    'call_id' => $call->retell_call_id,
    'timestamp' => $call->created_at->format('Y-m-d H:i:s'),
    'duration_sec' => $call->duration_sec,
    'transcript' => $call->transcript,
    'raw_data' => $raw,
    'analysis_time' => Carbon::now('Europe/Berlin')->format('Y-m-d H:i:s'),
];

file_put_contents(
    '/tmp/test_call_detailed_analysis_' . date('Y-m-d_His') . '.json',
    json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "✅ Detaillierte Analyse gespeichert\n";
echo "\n";
