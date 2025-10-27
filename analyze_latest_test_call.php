<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$callId = 'call_a3d98bb4522f6f1e0bfac5964b1'; // Latest test call

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
echo "Duration: " . (($call['duration_ms'] ?? 0) / 1000) . " seconds\n";
echo "Disconnect Reason: " . ($call['disconnection_reason'] ?? 'N/A') . "\n";
echo "Call Successful: " . (($call['call_analysis']['call_successful'] ?? false) ? 'YES' : 'NO') . "\n";
echo "User Sentiment: " . ($call['call_analysis']['user_sentiment'] ?? 'N/A') . "\n\n";

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

// Save full call data
file_put_contents(__DIR__ . '/latest_test_call_analysis.json', json_encode($call, JSON_PRETTY_PRINT));
echo "✅ Full call data saved to: latest_test_call_analysis.json\n\n";
