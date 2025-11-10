#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$retellApiKey = config('services.retellai.api_key');
$callId = $argv[1] ?? 'call_e99f4d7921d53754cfc820f4f6e';

echo "\n═══════════════════════════════════════════════════════════════\n";
echo " Call Analysis: {$callId}\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Fetch call data
$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("https://api.retellai.com/v2/get-call/{$callId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch call (Status: " . $response->status() . ")\n";
    echo "Response: " . $response->body() . "\n";
    exit(1);
}

$call = $response->json();

// Save raw JSON
file_put_contents(
    "/var/www/api-gateway/testcall_{$callId}_detailed.json",
    json_encode($call, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "✅ Call data saved to: testcall_{$callId}_detailed.json\n\n";

// Metadata
echo "📞 CALL METADATA\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Agent ID: " . ($call['agent_id'] ?? 'N/A') . "\n";
echo "Start: " . ($call['start_timestamp'] ? Carbon::createFromTimestampMs($call['start_timestamp'])->timezone('Europe/Berlin')->format('Y-m-d H:i:s') : 'N/A') . "\n";
echo "End: " . ($call['end_timestamp'] ? Carbon::createFromTimestampMs($call['end_timestamp'])->timezone('Europe/Berlin')->format('Y-m-d H:i:s') : 'N/A') . "\n";
$duration = isset($call['end_timestamp'], $call['start_timestamp'])
    ? round(($call['end_timestamp'] - $call['start_timestamp']) / 1000, 1)
    : 0;
echo "Duration: {$duration}s\n";
echo "Status: " . ($call['call_status'] ?? 'N/A') . "\n";
echo "Disconnection: " . ($call['disconnection_reason'] ?? 'N/A') . "\n";
echo "From: " . ($call['from_number'] ?? 'anonymous') . "\n\n";

// Transcript
if (isset($call['transcript']) && is_array($call['transcript'])) {
    echo "💬 TRANSCRIPT (" . count($call['transcript']) . " messages)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    foreach ($call['transcript'] as $index => $message) {
        $role = $message['role'] ?? 'unknown';
        $content = $message['content'] ?? '';
        $timestamp = isset($message['time_sec'])
            ? sprintf("[%05.1fs]", $message['time_sec'])
            : '[??]';

        $icon = $role === 'agent' ? '🤖' : '👤';
        echo "\n{$timestamp} {$icon} " . strtoupper($role) . ":\n";
        echo $content . "\n";
    }
} else {
    echo "⚠️ No transcript available\n";
}

// Tool calls
if (isset($call['transcript'])) {
    $toolCalls = array_filter($call['transcript'], fn($m) => ($m['role'] ?? '') === 'tool_call');
    $toolResults = array_filter($call['transcript'], fn($m) => ($m['role'] ?? '') === 'tool_call_result');

    if (!empty($toolCalls)) {
        echo "\n\n🔧 TOOL CALLS (" . count($toolCalls) . " total)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        foreach ($toolCalls as $tool) {
            $timestamp = isset($tool['time_sec'])
                ? sprintf("[%05.1fs]", $tool['time_sec'])
                : '[??]';

            echo "\n{$timestamp} Tool: " . ($tool['content'] ?? 'N/A') . "\n";

            if (isset($tool['arguments'])) {
                echo "Arguments:\n";
                echo json_encode(json_decode($tool['arguments'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
    }

    if (!empty($toolResults)) {
        echo "\n\n✅ TOOL RESULTS (" . count($toolResults) . " total)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        foreach ($toolResults as $result) {
            $timestamp = isset($result['time_sec'])
                ? sprintf("[%05.1fs]", $result['time_sec'])
                : '[??]';

            echo "\n{$timestamp} Result:\n";
            echo $result['content'] . "\n";
        }
    }
}

echo "\n\n═══════════════════════════════════════════════════════════════\n";
echo "Analysis complete. JSON saved to: testcall_{$callId}_detailed.json\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
