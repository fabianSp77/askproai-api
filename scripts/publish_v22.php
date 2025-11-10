<?php

/**
 * Publish V22 - call_id removed from parameters
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "📦 PUBLISHING V22\n";
echo str_repeat('=', 80) . "\n\n";

// Publish
echo "Publishing agent...\n";

$ch = curl_init("https://api.retellai.com/publish-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$publishResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Published!\n\n";

    echo str_repeat('=', 80) . "\n";
    echo "✅ V22 IS LIVE!\n\n";
    echo "Changes:\n";
    echo "  - Removed call_id from tool definitions\n";
    echo "  - Removed call_id from parameter mappings\n";
    echo "  - Backend extracts call_id from webhook context\n\n";
    echo "🎯 READY FOR TEST CALL\n\n";
    echo "To test:\n";
    echo "1. Enable logging: ./scripts/enable_testcall_logging.sh\n";
    echo "2. Call: +49 30 33081738\n";
    echo "3. Request: \"Herrenhaarschnitt für morgen 09:00 Uhr\"\n";
    echo "4. Verify: Availability check should now work!\n";
    echo "5. Disable logging: ./scripts/disable_testcall_logging.sh\n";

} else {
    echo "❌ Publish failed! HTTP {$httpCode}\n";
    echo "Response: {$publishResponse}\n";
}
