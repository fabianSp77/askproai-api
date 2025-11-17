#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  LATEST CALL ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get calls for this agent
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/list-calls?agent_id=$agentId&limit=5");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to list calls\n";
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$callsData = json_decode($response, true);
$calls = $callsData['calls'] ?? [];

if (empty($calls)) {
    echo "❌ No calls found for agent $agentId\n";
    exit(1);
}

echo "Found " . count($calls) . " recent calls\n\n";

// Get the most recent call
$latestCall = $calls[0];
$callId = $latestCall['call_id'];

echo "Latest Call ID: $callId\n";
echo "Start Time: " . ($latestCall['start_timestamp'] ?? 'N/A') . "\n";
echo "End Time: " . ($latestCall['end_timestamp'] ?? 'N/A') . "\n";
echo "Direction: " . ($latestCall['call_type'] ?? 'N/A') . "\n\n";

// Get detailed call information
echo "Fetching detailed call data...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/get-call/$callId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to get call details\n";
    exit(1);
}

$callDetails = json_decode($response, true);

// Save full response
file_put_contents('/var/www/api-gateway/latest_call_full_' . date('Y-m-d_His') . '.json', json_encode($callDetails, JSON_PRETTY_PRINT));

echo "═══════════════════════════════════════════════════════════\n";
echo "  CALL DETAILS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Call ID: " . $callDetails['call_id'] . "\n";
echo "Agent: " . ($callDetails['agent_id'] ?? 'N/A') . "\n";
echo "From: " . ($callDetails['from_number'] ?? 'N/A') . "\n";
echo "To: " . ($callDetails['to_number'] ?? 'N/A') . "\n";
echo "Duration: " . ($callDetails['call_analysis']['call_summary']['duration_ms'] ?? 0) / 1000 . " seconds\n\n";

// Transcript
if (isset($callDetails['transcript'])) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  TRANSCRIPT\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    foreach ($callDetails['transcript'] as $idx => $turn) {
        $timestamp = ($turn['timestamp'] ?? 0) / 1000;
        $role = $turn['role'] ?? 'unknown';
        $content = $turn['content'] ?? '';

        echo sprintf("[%05.1fs] %s: %s\n", $timestamp, strtoupper($role), $content);
    }
    echo "\n";
}

// Function calls
if (isset($callDetails['call_analysis']['custom_analysis_data'])) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  CUSTOM ANALYSIS DATA\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo json_encode($callDetails['call_analysis']['custom_analysis_data'], JSON_PRETTY_PRINT);
    echo "\n\n";
}

// Check for tool calls in transcript
echo "═══════════════════════════════════════════════════════════\n";
echo "  FUNCTION CALLS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$functionCalls = [];
foreach ($callDetails['transcript'] as $turn) {
    if (isset($turn['role']) && $turn['role'] === 'tool_call') {
        $functionCalls[] = $turn;
    }
}

if (empty($functionCalls)) {
    echo "No function calls found in transcript\n\n";
} else {
    foreach ($functionCalls as $idx => $call) {
        $timestamp = ($call['timestamp'] ?? 0) / 1000;
        echo sprintf("[%05.1fs] FUNCTION CALL #%d\n", $timestamp, $idx + 1);

        if (isset($call['content'])) {
            $content = json_decode($call['content'], true);
            if ($content) {
                echo "  Name: " . ($content['name'] ?? 'N/A') . "\n";
                echo "  Arguments:\n";
                echo "    " . json_encode($content['arguments'] ?? [], JSON_PRETTY_PRINT) . "\n";
            }
        }

        // Look for tool response
        if (isset($callDetails['transcript'][$idx + 1]) &&
            $callDetails['transcript'][$idx + 1]['role'] === 'tool_response') {
            $response = $callDetails['transcript'][$idx + 1];
            $responseTime = ($response['timestamp'] ?? 0) / 1000;
            echo sprintf("  [%05.1fs] Response: %s\n", $responseTime, substr($response['content'] ?? '', 0, 200));
        }

        echo "\n";
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if (isset($callDetails['call_analysis']['call_summary'])) {
    $summary = $callDetails['call_analysis']['call_summary'];
    echo "Call successful: " . ($summary['call_successful'] ?? 'N/A') . "\n";
    echo "User sentiment: " . ($summary['user_sentiment'] ?? 'N/A') . "\n";
}

echo "\n";
echo "Full data saved to: latest_call_full_" . date('Y-m-d_His') . ".json\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "  DONE\n";
echo "═══════════════════════════════════════════════════════════\n\n";
