#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$callId = $argv[1] ?? 'call_60b00c74e96fd6b65dff12ec572';

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Call Details: {$callId}\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');

// Try different base URLs
$baseUrls = [
    'https://api.retellai.com/v2',
    'https://api.retellai.com',
];

$callDetails = null;
foreach ($baseUrls as $baseUrl) {
    echo "🔍 Trying: {$baseUrl}/get-call/{$callId}...\n";
    
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$retellApiKey}",
    ])->get("{$baseUrl}/get-call/{$callId}");
    
    if ($response->successful()) {
        $callDetails = $response->json();
        echo "✅ Success!\n\n";
        break;
    } else {
        echo "❌ Failed (Status: " . $response->status() . ")\n";
    }
}

if (!$callDetails) {
    echo "\n❌ ERROR: Could not fetch call details\n";
    exit(1);
}

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
$vormittagMentions = 0;
$contradictions = [];
$repetitions = 0;
$previousContent = null;

foreach ($transcript as $index => $message) {
    $role = $message['role'] ?? 'unknown';
    $content = $message['content'] ?? '';
    $timestamp = isset($message['timestamp']) 
        ? Carbon::createFromTimestampMs($message['timestamp'])->timezone('Europe/Berlin')->format('H:i:s')
        : 'N/A';

    $roleIcon = $role === 'agent' ? '🤖' : '👤';
    $roleLabel = strtoupper($role);

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
        $hasNoAvailable = (stripos($content, 'kein') !== false || stripos($content, 'nicht') !== false || stripos($content, 'leider') !== false) 
                          && (stripos($content, 'vormittag') !== false || stripos($content, 'frei') !== false);
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

echo "\n";
echo "✅ Analysis Complete\n";
echo "\n";
