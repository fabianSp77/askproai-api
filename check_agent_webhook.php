<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 CHECKING AGENT WEBHOOK CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch agent! HTTP $httpCode\n");
}

$agent = json_decode($response, true);

echo "Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
echo "Version: " . ($agent['version'] ?? 'N/A') . "\n\n";

echo "🔗 WEBHOOK CONFIGURATION:\n";
echo "─────────────────────────────────────────\n";
echo "Webhook URL: " . ($agent['webhook_url'] ?? '❌ NOT SET') . "\n";
echo "Webhook Timeout (ms): " . ($agent['webhook_timeout_ms'] ?? 'N/A') . "\n\n";

if (empty($agent['webhook_url'])) {
    echo "🚨 CRITICAL: NO WEBHOOK URL CONFIGURED!\n";
    echo "   This is why no events are being received!\n\n";
} else {
    $webhookUrl = $agent['webhook_url'];
    echo "✅ Webhook URL is set\n\n";

    // Test webhook URL accessibility
    echo "🧪 Testing webhook URL accessibility...\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Response: $httpCode\n";

    if ($httpCode === 405) {
        echo "✅ Endpoint exists (405 = Method Not Allowed for HEAD)\n";
    } elseif ($httpCode === 200) {
        echo "✅ Endpoint accessible\n";
    } else {
        echo "⚠️  Unexpected response code\n";
    }
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "📋 FULL AGENT CONFIG\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo json_encode($agent, JSON_PRETTY_PRINT);
