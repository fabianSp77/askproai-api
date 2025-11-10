#!/usr/bin/env php
<?php

/**
 * Analyze Latest Test Call
 * Fetches recent calls from Retell, finds the latest test call,
 * and analyzes timezone, availability, and conversation flow issues
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Latest Test Call Analysis\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// Fetch recent calls
echo "🔍 Fetching recent calls from Retell API...\n";
$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/list-calls", [
    'agent_id' => $agentId,
    'limit' => 20,
    'sort_order' => 'descending'
]);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch calls\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
    exit(1);
}

$calls = $response->json();

echo "✅ Found " . count($calls) . " recent calls\n\n";

// Find the most recent call
if (empty($calls)) {
    echo "❌ No calls found\n";
    exit(1);
}

// Show overview
echo "📋 Recent Calls Overview:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

foreach (array_slice($calls, 0, 10) as $index => $call) {
    $callId = $call['call_id'] ?? 'N/A';
    $startTime = isset($call['start_timestamp']) 
        ? Carbon::parse($call['start_timestamp'])->timezone('Europe/Berlin')->format('Y-m-d H:i:s')
        : 'N/A';
    $fromNumber = $call['from_number'] ?? 'N/A';

    echo ($index + 1) . ". {$callId}\n";
    echo "   Time: {$startTime} CET | From: {$fromNumber}\n\n";
}

// Analyze the latest call
$latestCall = $calls[0];
$callId = $latestCall['call_id'];

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Analyzing Latest Call: {$callId}\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Fetch detailed call info with transcript
$detailResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-call/{$callId}");

if (!$detailResponse->successful()) {
    echo "❌ ERROR: Failed to fetch call details\n";
    exit(1);
}

$callDetails = $detailResponse->json();

// Extract key information
$startTime = isset($callDetails['start_timestamp']) 
    ? Carbon::parse($callDetails['start_timestamp'])->timezone('Europe/Berlin') 
    : null;
$endTime = isset($callDetails['end_timestamp']) 
    ? Carbon::parse($callDetails['end_timestamp'])->timezone('Europe/Berlin') 
    : null;
$transcript = $callDetails['transcript'] ?? [];

echo "📞 Call Metadata:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Start Time: " . ($startTime ? $startTime->format('Y-m-d H:i:s T') : 'N/A') . "\n";
echo "End Time: " . ($endTime ? $endTime->format('Y-m-d H:i:s T') : 'N/A') . "\n";
echo "Duration: " . ($startTime && $endTime ? $endTime->diffInSeconds($startTime) . 's' : 'N/A') . "\n";
echo "From: " . ($callDetails['from_number'] ?? 'N/A') . "\n";
echo "\n";

// Analyze transcript
echo "💬 Full Conversation:\n";
echo str_repeat("─", 63) . "\n\n";

$availabilityChecks = 0;
$timeReferences = 0;
$repetitions = 0;
$previousContent = null;

foreach ($transcript as $index => $message) {
    $role = $message['role'] ?? 'unknown';
    $content = $message['content'] ?? '';
    $timestamp = isset($message['timestamp']) 
        ? Carbon::createFromTimestampMs($message['timestamp'])->timezone('Europe/Berlin')->format('H:i:s')
        : 'N/A';

    $roleIcon = $role === 'agent' ? '🤖' : '👤';
    $roleLabel = $role === 'agent' ? 'AGENT' : 'USER';

    echo "[{$timestamp}] {$roleIcon} {$roleLabel}:\n";
    echo "{$content}\n\n";

    // Count issues
    if (stripos($content, 'verfügbar') !== false || stripos($content, 'prüf') !== false) {
        $availabilityChecks++;
    }

    if (preg_match('/\d{1,2}:\d{2}|\d{1,2}\s+Uhr/i', $content)) {
        $timeReferences++;
    }

    if ($role === 'agent' && $previousContent && similar_text($previousContent, $content) > 30) {
        $repetitions++;
    }

    if ($role === 'agent') {
        $previousContent = $content;
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Issue Summary\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

echo "📊 Statistics:\n";
echo "  Total Messages: " . count($transcript) . "\n";
echo "  Availability Checks: {$availabilityChecks}\n";
echo "  Time References: {$timeReferences}\n";
echo "  Potential Repetitions: {$repetitions}\n";
echo "\n";

// Check backend logs
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    echo "🔍 Checking backend logs for {$callId}...\n\n";
    
    $logs = shell_exec("grep '{$callId}' {$logFile} 2>/dev/null | tail -50");
    
    if (!empty($logs)) {
        echo "📋 Backend Logs (last 50 lines):\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo $logs;
        echo "\n";
    }
}

echo "\n";
echo "✅ Analysis Complete\n";
echo "\n";
