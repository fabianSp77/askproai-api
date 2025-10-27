#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\RetellCallSession;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "RETELL API - CALL DETAILS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get latest call ID from our DB
$latestCall = RetellCallSession::orderBy('created_at', 'desc')->first();

if (!$latestCall) {
    echo "❌ No call in DB\n";
    exit(1);
}

$callId = $latestCall->call_id;

echo "Fetching call data for: $callId\n";
echo "From our DB: Started {$latestCall->started_at}, Agent V{$latestCall->agent_version}\n\n";

// Get all calls from Retell
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->post("https://api.retellai.com/v2/list-calls", [
    'limit' => 10,
    'sort_order' => 'descending'
]);

if (!$response->successful()) {
    echo "❌ API call failed: {$response->status()}\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}

$calls = $response->json();

// Find our call
$call = null;
foreach ($calls as $c) {
    if ($c['call_id'] === $callId) {
        $call = $c;
        break;
    }
}

if (!$call && count($calls) > 0) {
    echo "⚠️  Call $callId not found in latest 10 calls, using most recent call instead\n\n";
    $call = $calls[0];
}

echo "CALL DATA FROM RETELL API:\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Call ID: " . ($call['call_id'] ?? 'N/A') . "\n";
echo "Call Type: " . ($call['call_type'] ?? 'N/A') . "\n";
echo "Call Status: " . ($call['call_status'] ?? 'N/A') . "\n";
echo "Agent ID: " . ($call['agent_id'] ?? 'N/A') . "\n";
echo "Start Time: " . ($call['start_timestamp'] ?? 'N/A') . "\n";
echo "End Time: " . ($call['end_timestamp'] ?? 'N/A') . "\n";
echo "Disconnect Reason: " . ($call['disconnect_reason'] ?? 'N/A') . "\n";
echo "Duration: " . ($call['call_duration_ms'] ?? 'N/A') . " ms\n";
echo "From: " . ($call['from_number'] ?? 'N/A') . "\n";
echo "To: " . ($call['to_number'] ?? 'N/A') . "\n\n";

// Check for transcript
if (isset($call['transcript'])) {
    echo "TRANSCRIPT:\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo $call['transcript'] . "\n\n";
}

// Check for recording URL
if (isset($call['recording_url'])) {
    echo "Recording: {$call['recording_url']}\n\n";
}

// Full JSON for details
echo "\nFULL CALL JSON:\n";
echo "═══════════════════════════════════════════════════════════\n";
echo json_encode($call, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
