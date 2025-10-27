#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "📞 DETAILED CALL ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get latest call from Retell
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get('https://api.retellai.com/list-calls');

if (!$response->successful()) {
    echo "❌ Failed to get calls\n";
    exit(1);
}

$calls = $response->json();

if (empty($calls)) {
    echo "No calls found\n";
    exit(1);
}

// Get the most recent call
$latestCall = $calls[0];
$callId = $latestCall['call_id'];

echo "Latest Call:\n";
echo "  ID: $callId\n";
echo "  Started: {$latestCall['start_timestamp']}\n";
echo "  Agent Version: {$latestCall['agent_version']}\n";
echo "  Disconnection Reason: {$latestCall['disconnection_reason']}\n\n";

// Get full call details
$detailResponse = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-call/$callId");

$call = $detailResponse->json();

// Show transcript
if (isset($call['transcript'])) {
    echo "TRANSCRIPT:\n";
    echo "───────────────────────────────────────────────────────────\n";
    foreach ($call['transcript'] as $segment) {
        $role = $segment['role'] === 'agent' ? 'AI' : 'USER';
        $content = $segment['content'];
        echo "[$role]: $content\n";
    }
    echo "\n";
}

// Show function calls
if (isset($call['call_analysis']['call_summary'])) {
    echo "CALL SUMMARY:\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo $call['call_analysis']['call_summary'] . "\n\n";
}

// Check for function call errors in metadata
if (isset($call['metadata'])) {
    echo "METADATA:\n";
    echo "───────────────────────────────────────────────────────────\n";
    print_r($call['metadata']);
    echo "\n";
}

// Most importantly: check call_analysis
if (isset($call['call_analysis'])) {
    echo "CALL ANALYSIS:\n";
    echo "───────────────────────────────────────────────────────────\n";

    if (isset($call['call_analysis']['in_voicemail'])) {
        echo "Voicemail: " . ($call['call_analysis']['in_voicemail'] ? 'YES' : 'NO') . "\n";
    }

    if (isset($call['call_analysis']['user_sentiment'])) {
        echo "User Sentiment: {$call['call_analysis']['user_sentiment']}\n";
    }

    echo "\n";
}

// Save full JSON for inspection
file_put_contents(__DIR__ . '/failed_call_full.json', json_encode($call, JSON_PRETTY_PRINT));
echo "✅ Full call data saved to failed_call_full.json\n\n";

// Critical: Check if there are any errors in the call
if (isset($call['retell_llm_dynamic_variables'])) {
    echo "LLM DYNAMIC VARIABLES:\n";
    echo "───────────────────────────────────────────────────────────\n";
    print_r($call['retell_llm_dynamic_variables']);
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "DIAGNOSIS:\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Check if initialize_call was even attempted
$transcriptText = implode(' ', array_map(fn($s) => $s['content'], $call['transcript'] ?? []));

if (strpos($transcriptText, 'Guten Tag bei Friseur 1') !== false) {
    echo "✅ Initialize call greeting was spoken\n";
} else {
    echo "❌ Initialize call greeting NOT spoken (stuck before init?)\n";
}

if (strpos($transcriptText, 'prüfe die Verfügbarkeit') !== false) {
    echo "✅ AI mentioned checking availability\n";
    echo "   BUT: Did the function actually get called?\n";
    echo "   → Backend logs show: NO webhook received\n";
} else {
    echo "❌ AI never mentioned checking availability\n";
}

echo "\n";
