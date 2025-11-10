<?php

/**
 * Check Published Agent Version
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "🔍 CHECKING PUBLISHED AGENT VERSION\n";
echo str_repeat('=', 80) . "\n\n";

$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$agentResponse = curl_exec($ch);
curl_close($ch);

$agent = json_decode($agentResponse, true);

echo "Agent ID: {$agent['agent_id']}\n";
echo "Agent Name: {$agent['agent_name']}\n\n";

echo "📌 PUBLISHED VERSION (Live):\n";
echo "  Version: V{$agent['last_published_version']}\n";
echo "  Flow Version: V" . ($agent['last_published_response_engine']['version'] ?? 'N/A') . "\n\n";

echo "📝 DRAFT VERSION (Unpublished):\n";
echo "  Version: V{$agent['version']}\n";
echo "  Flow Version: V{$agent['response_engine']['version']}\n\n";

if ($agent['version'] != $agent['last_published_version']) {
    echo "⚠️  MISMATCH DETECTED!\n";
    echo "The draft version (V{$agent['version']}) is DIFFERENT from published (V{$agent['last_published_version']})\n";
    echo "This means recent changes are NOT live!\n\n";
} else {
    echo "✅ Published and draft are in sync\n\n";
}

echo "Test Call Analysis:\n";
echo "  Test call used agent_version: 20\n";
echo "  Current published version: {$agent['last_published_version']}\n\n";

if ($agent['last_published_version'] != 24) {
    echo "❌ PROBLEM: Test call did NOT use V24!\n";
    echo "   Expected V24, but got V20\n";
    echo "   This means the phone number might be configured to use an older version!\n";
}
