#!/usr/bin/env php
<?php
/**
 * Publish Conversation Flow V82 (call_id fix)
 *
 * After uploading the fix, we need to publish it to make it active
 *
 * Date: 2025-11-08
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';
$conversationFlowId = 'conversation_flow_a58405e3f67a';

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  📢 PUBLISHING CONVERSATION FLOW V82\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

echo "🔧 Configuration:\n";
echo "  Flow ID: $conversationFlowId\n";
echo "  Version: 82 (call_id fix)\n";
echo "\n";

echo "📤 Publishing to Retell AI...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/publish-conversation-flow/$conversationFlowId");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "\n";

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);

    echo "═══════════════════════════════════════════════════════════\n";
    echo "  ✅ PUBLISH SUCCESSFUL!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";

    echo "📊 Published Flow Details:\n";
    if ($result) {
        echo "  Flow ID: " . ($result['conversation_flow_id'] ?? 'N/A') . "\n";
        echo "  Version: " . ($result['version'] ?? 'N/A') . "\n";
        echo "  Published: " . (($result['is_published'] ?? false) ? '✅ YES' : '❌ NO') . "\n";
    } else {
        echo "  Published successfully (empty response)\n";
    }

    echo "\n";

    echo "═══════════════════════════════════════════════════════════\n";
    echo "  🎉 FIX IS NOW LIVE!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";

    echo "🧪 Ready for Testing:\n";
    echo "1. Call: +493033081738\n";
    echo "2. Request: 'Herrenhaarschnitt für morgen'\n";
    echo "3. When offered time is not available, ask for alternatives\n";
    echo "4. Select alternative time (triggers two-step flow)\n";
    echo "5. Confirm booking\n";
    echo "6. Expected: ✅ Appointment created successfully\n";
    echo "\n";

    echo "🔍 Verify in database:\n";
    echo "  php artisan tinker\n";
    echo "  >>> \\App\\Models\\Appointment::latest()->first()\n";
    echo "  >>> Should show: call_id, service_id, staff_id, branch_id\n";
    echo "\n";

    echo "📊 Monitor logs:\n";
    echo "  tail -f storage/logs/laravel.log | grep -i 'start_booking\\|confirm_booking'\n";
    echo "\n";

    exit(0);

} else {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  ❌ PUBLISH FAILED!\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";

    echo "Response:\n";
    echo $response . "\n";
    echo "\n";

    // Try to decode error
    $error = json_decode($response, true);
    if ($error) {
        echo "Error Details:\n";
        print_r($error);
        echo "\n";
    }

    exit(1);
}
