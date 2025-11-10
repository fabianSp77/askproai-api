<?php

/**
 * Publish V24 - Fixed Conversation Flow Prompts
 *
 * Changes:
 * - Agent analyzes user's latest message FIRST before checking variables
 * - Extracts information from transcript to avoid redundant questions
 * - No redundant confirmations when user selects alternative times
 * - Natural conversation flow
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "📦 PUBLISHING V24\n";
echo str_repeat('=', 80) . "\n\n";

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
    echo "✅ V24 IS LIVE!\n\n";

    echo "FIXES:\n";
    echo "  1. Agent analyzes user's LATEST message FIRST\n";
    echo "  2. Extracts all information from user's initial statement\n";
    echo "  3. Only asks for TRULY missing data\n";
    echo "  4. No redundant confirmations:\n";
    echo "     ❌ 'Ist es morgen, wie Sie gesagt haben?'\n";
    echo "     ❌ 'Sie haben gesagt, um neun Uhr, richtig?'\n";
    echo "     ❌ 'Also, um das klarzustellen...'\n";
    echo "  5. Natural conversation flow\n\n";

    echo "🎯 READY FOR TEST CALL!\n\n";
    echo "To test:\n";
    echo "1. Enable logging: ./scripts/enable_testcall_logging.sh\n";
    echo "2. Call: +49 30 33081738\n";
    echo "3. Say: \"Hans Schuster, Herrenhaarschnitt für morgen 09:00 Uhr\"\n";
    echo "4. Verify: Agent should proceed DIRECTLY to availability check\n";
    echo "5. Select alternative when offered\n";
    echo "6. Verify: No redundant confirmation, direct booking\n";
    echo "7. Disable logging: ./scripts/disable_testcall_logging.sh\n";

} else {
    echo "❌ Publish failed! HTTP {$httpCode}\n";
    echo "Response: {$publishResponse}\n";
}
