#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Latest Test Call Analysis (V2)\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$baseUrl = 'https://api.retellai.com/v2';

// Try different endpoints
$endpoints = [
    '/list-calls',
    '/call',
    '/calls',
];

$calls = null;
foreach ($endpoints as $endpoint) {
    echo "🔍 Trying: {$baseUrl}{$endpoint}...\n";
    
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$retellApiKey}",
    ])->get("{$baseUrl}{$endpoint}", [
        'agent_id' => $agentId,
        'limit' => 10,
        'sort_order' => 'descending'
    ]);
    
    if ($response->successful()) {
        $calls = $response->json();
        echo "✅ Success with endpoint: {$endpoint}\n\n";
        break;
    } else {
        echo "❌ Failed (Status: " . $response->status() . ")\n";
    }
}

if (!$calls) {
    echo "\n❌ ERROR: Could not fetch calls from any endpoint\n";
    exit(1);
}

// Handle both array and object responses
$callsList = [];
if (isset($calls['calls'])) {
    $callsList = $calls['calls'];
} elseif (is_array($calls)) {
    $callsList = $calls;
}

if (empty($callsList)) {
    echo "❌ No calls found\n";
    exit(1);
}

echo "✅ Found " . count($callsList) . " recent calls\n\n";

// Show overview
echo "📋 Recent Calls Overview:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

foreach (array_slice($callsList, 0, 5) as $index => $call) {
    $callId = $call['call_id'] ?? 'N/A';
    $startTime = isset($call['start_timestamp']) 
        ? Carbon::parse($call['start_timestamp'])->timezone('Europe/Berlin')->format('Y-m-d H:i:s')
        : 'N/A';
    $fromNumber = $call['from_number'] ?? 'N/A';

    echo ($index + 1) . ". {$callId}\n";
    echo "   Time: {$startTime} CET | From: {$fromNumber}\n\n";
}

// Analyze the latest call
$latestCall = $callsList[0];
$callId = $latestCall['call_id'];

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Analyzing Latest Call: {$callId}\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Fetch detailed call info
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
$timeReferences = [];
$repetitions = 0;
$previousContent = null;
$vormittagMentions = 0;
$contradictions = [];

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

    // Track time references
    if (preg_match('/(\d{1,2}):(\d{2})|(\d{1,2})\s+Uhr\s+(\d{2})?/i', $content, $matches)) {
        $timeReferences[] = [
            'role' => $role,
            'time' => $matches[0],
            'content' => substr($content, 0, 100)
        ];
    }

    // Check for "Vormittag" mentions
    if (stripos($content, 'vormittag') !== false) {
        $vormittagMentions++;
    }

    // Check for contradictions (no time available BUT offers time)
    if ($role === 'agent') {
        $hasNoAvailable = (stripos($content, 'kein') !== false || stripos($content, 'nicht frei') !== false) 
                          && stripos($content, 'vormittag') !== false;
        $hasTimeOffer = preg_match('/\d{1,2}:\d{2}|\d{1,2}\s+Uhr/i', $content);
        
        if ($hasNoAvailable && $hasTimeOffer) {
            $contradictions[] = [
                'timestamp' => $timestamp,
                'content' => $content
            ];
        }
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
echo " Issue Analysis\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

echo "📊 Statistics:\n";
echo "  Total Messages: " . count($transcript) . "\n";
echo "  Availability Checks: {$availabilityChecks}\n";
echo "  Time References: " . count($timeReferences) . "\n";
echo "  'Vormittag' Mentions: {$vormittagMentions}\n";
echo "  Potential Repetitions: {$repetitions}\n";
echo "  Contradictions Found: " . count($contradictions) . "\n";
echo "\n";

if (!empty($timeReferences)) {
    echo "🕐 Time References:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($timeReferences as $ref) {
        echo "  [{$ref['role']}] {$ref['time']}: " . substr($ref['content'], 0, 60) . "...\n";
    }
    echo "\n";
}

if (!empty($contradictions)) {
    echo "⚠️  CONTRADICTIONS DETECTED:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($contradictions as $c) {
        echo "[{$c['timestamp']}] {$c['content']}\n\n";
    }
}

// Check backend logs
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    echo "🔍 Checking backend logs for {$callId}...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $logs = shell_exec("grep '{$callId}' {$logFile} 2>/dev/null | tail -100");
    
    if (!empty($logs)) {
        echo $logs;
        echo "\n";
    } else {
        echo "No backend logs found for this call ID\n\n";
    }
}

echo "\n";
echo "✅ Analysis Complete\n";
echo "\n";
