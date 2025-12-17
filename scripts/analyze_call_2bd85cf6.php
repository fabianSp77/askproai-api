#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';
$callId = 'call_2bd85cf6b264b20e11d8decb91a';

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  TESTCALL DETAILANALYSE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Call ID: $callId\n\n";

// Get call details
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
    echo "❌ Failed to get call\n";
    echo "HTTP Status: $httpCode\n";
    exit(1);
}

$call = json_decode($response, true);

// Save full JSON
$jsonFile = '/var/www/api-gateway/testcall_' . date('Y-m-d_His') . '.json';
file_put_contents($jsonFile, json_encode($call, JSON_PRETTY_PRINT));
echo "✅ Full data saved to: $jsonFile\n\n";

// Basic info
echo "═══════════════════════════════════════════════════════════\n";
echo "  CALL INFORMATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Agent: " . ($call['agent_id'] ?? 'N/A') . "\n";
echo "From: " . ($call['from_number'] ?? 'N/A') . "\n";
echo "To: " . ($call['to_number'] ?? 'N/A') . "\n";
echo "Duration: " . (($call['call_analysis']['call_summary']['duration_ms'] ?? 0) / 1000) . " seconds\n\n";

// Collected variables
if (isset($call['collected_dynamic_variables'])) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  COLLECTED VARIABLES\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    foreach ($call['collected_dynamic_variables'] as $key => $value) {
        echo "$key: " . ($value ?? 'null') . "\n";
    }
    echo "\n";
}

// Extract tool calls from transcript
echo "═══════════════════════════════════════════════════════════\n";
echo "  FUNCTION CALLS TIMELINE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$transcript = $call['transcript_with_tool_calls'] ?? [];
$lastNode = null;

foreach ($transcript as $entry) {
    $role = $entry['role'] ?? '';
    $timeSec = $entry['time_sec'] ?? 0;

    if ($role === 'node_transition') {
        $fromNode = $entry['former_node_name'] ?? '';
        $toNode = $entry['new_node_name'] ?? '';
        echo sprintf("[%05.1fs] NODE: %s → %s\n", $timeSec, $fromNode, $toNode);
        $lastNode = $toNode;
    }

    elseif ($role === 'tool_call_invocation') {
        $name = $entry['name'] ?? 'unknown';
        $args = $entry['arguments'] ?? '';

        if ($args) {
            $argsDecoded = json_decode($args, true);
            $argsFormatted = json_encode($argsDecoded, JSON_PRETTY_PRINT);
        } else {
            $argsFormatted = '(no args)';
        }

        echo sprintf("[%05.1fs] CALL: %s\n", $timeSec, $name);
        echo "         Args: " . str_replace("\n", "\n               ", trim($argsFormatted)) . "\n";
    }

    elseif ($role === 'tool_call_result') {
        $content = $entry['content'] ?? '';
        $successful = $entry['successful'] ?? null;

        $contentDecoded = json_decode($content, true);
        if ($contentDecoded) {
            $contentFormatted = json_encode($contentDecoded, JSON_PRETTY_PRINT);
        } else {
            $contentFormatted = $content;
        }

        $status = $successful === true ? '✅' : ($successful === false ? '❌' : '⚠️');

        echo sprintf("[%05.1fs] %s RESULT:\n", $timeSec, $status);
        echo "         " . str_replace("\n", "\n         ", trim($contentFormatted)) . "\n";
    }

    elseif ($role === 'agent') {
        $content = $entry['content'] ?? '';
        if (strlen($content) > 100) {
            $content = substr($content, 0, 100) . '...';
        }
        echo sprintf("[%05.1fs] AGENT: %s\n", $timeSec, $content);
    }

    elseif ($role === 'user') {
        $content = $entry['content'] ?? '';
        echo sprintf("[%05.1fs] USER: %s\n", $timeSec, $content);
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  DONE\n";
echo "═══════════════════════════════════════════════════════════\n\n";
