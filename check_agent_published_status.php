<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

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

$agent = json_decode($response, true);

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 AGENT PUBLISHED STATUS CHECK\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Raw is_published value: ";
var_dump($agent['is_published'] ?? null);
echo "\n";

echo "Type: " . gettype($agent['is_published'] ?? null) . "\n\n";

if (isset($agent['is_published'])) {
    if ($agent['is_published'] === true) {
        echo "✅ AGENT IS PUBLISHED!\n\n";
    } elseif ($agent['is_published'] === false) {
        echo "❌ AGENT IS NOT PUBLISHED\n\n";
    } else {
        echo "⚠️  Unknown published state: " . json_encode($agent['is_published']) . "\n\n";
    }
} else {
    echo "⚠️  is_published field not found in response\n\n";
}

echo "Full Agent Data:\n";
echo json_encode($agent, JSON_PRETTY_PRINT);
