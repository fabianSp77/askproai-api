<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$callId = 'call_965e403dd01058ce7d0a25bc9c5'; // Latest test call

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 ANALYZING TEST CALL: $callId\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Get call details
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/v2/get-call/$callId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch call! HTTP $httpCode\nResponse: $response\n");
}

$call = json_decode($response, true);

// Display call overview
echo "📞 CALL OVERVIEW:\n";
echo "─────────────────────────────────────────\n";
echo "Call ID: " . ($call['call_id'] ?? 'N/A') . "\n";
echo "Status: " . ($call['call_status'] ?? 'N/A') . "\n";
echo "Type: " . ($call['call_type'] ?? 'N/A') . "\n";
echo "Direction: " . ($call['direction'] ?? 'N/A') . "\n";
echo "From: " . ($call['from_number'] ?? 'N/A') . "\n";
echo "To: " . ($call['to_number'] ?? 'N/A') . "\n";
echo "Duration: " . ($call['duration_ms'] ?? 0) / 1000 . " seconds\n";
echo "Disconnect Reason: " . ($call['disconnection_reason'] ?? 'N/A') . "\n";
echo "Agent ID: " . ($call['agent_id'] ?? 'N/A') . "\n\n";

// Display transcript
if (isset($call['transcript']) && !empty($call['transcript'])) {
    echo "📝 CONVERSATION TRANSCRIPT:\n";
    echo "─────────────────────────────────────────\n";
    foreach ($call['transcript'] as $entry) {
        $role = $entry['role'] ?? 'unknown';
        $content = $entry['content'] ?? '';
        $timestamp = isset($entry['timestamp']) ? date('H:i:s', $entry['timestamp'] / 1000) : 'N/A';

        $prefix = $role === 'agent' ? '🤖 Agent' : '👤 User';
        echo "[$timestamp] $prefix: $content\n";
    }
    echo "\n";
} else {
    echo "❌ NO TRANSCRIPT AVAILABLE\n\n";
}

// Display function calls
if (isset($call['tool_calls']) && !empty($call['tool_calls'])) {
    echo "🔧 FUNCTION CALLS:\n";
    echo "─────────────────────────────────────────\n";
    foreach ($call['tool_calls'] as $toolCall) {
        $name = $toolCall['name'] ?? 'unknown';
        $args = json_encode($toolCall['arguments'] ?? [], JSON_PRETTY_PRINT);
        $result = isset($toolCall['result']) ? json_encode($toolCall['result'], JSON_PRETTY_PRINT) : 'N/A';

        echo "Function: $name\n";
        echo "Arguments:\n$args\n";
        echo "Result:\n$result\n";
        echo "─────────────────────────────────────────\n";
    }
} else {
    echo "❌ NO FUNCTION CALLS RECORDED\n\n";
}

// Display call analysis
if (isset($call['call_analysis'])) {
    echo "📊 CALL ANALYSIS:\n";
    echo "─────────────────────────────────────────\n";
    echo "Summary: " . ($call['call_analysis']['call_summary'] ?? 'N/A') . "\n";
    echo "User Sentiment: " . ($call['call_analysis']['user_sentiment'] ?? 'N/A') . "\n";
    echo "In Voicemail: " . ($call['call_analysis']['in_voicemail'] ?? 'N/A' ? 'Yes' : 'No') . "\n\n";
}

// Save full call data
file_put_contents(__DIR__ . '/last_test_call_analysis.json', json_encode($call, JSON_PRETTY_PRINT));
echo "✅ Full call data saved to: last_test_call_analysis.json\n\n";

echo "═══════════════════════════════════════════════════════\n";
echo "🎯 KEY FINDINGS:\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Analyze for repeated questions
$transcriptText = '';
if (isset($call['transcript'])) {
    foreach ($call['transcript'] as $entry) {
        if ($entry['role'] === 'agent') {
            $transcriptText .= $entry['content'] . ' ';
        }
    }
}

// Count how many times agent asks "wann" or "Uhrzeit"
$whennCount = preg_match_all('/\bwann\b/i', $transcriptText);
$timeCount = preg_match_all('/\bUhrzeit\b/i', $transcriptText);

echo "⚠️  Agent asked 'wann': $whennCount times\n";
echo "⚠️  Agent asked 'Uhrzeit': $timeCount times\n\n";

if ($whennCount + $timeCount > 2) {
    echo "🚨 PROBLEM DETECTED: Agent repeatedly asking for time!\n";
    echo "   This indicates the agent is not properly extracting/storing the time.\n\n";
}

// Check if function was called
$functionCallCount = isset($call['tool_calls']) ? count($call['tool_calls']) : 0;
echo "📊 Function calls made: $functionCallCount\n\n";

if ($functionCallCount === 0) {
    echo "🚨 CRITICAL: NO FUNCTION CALLS MADE!\n";
    echo "   The agent never called check_availability or collect_appointment_info.\n";
    echo "   This is why the booking failed.\n\n";
}
