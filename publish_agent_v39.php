<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "═══════════════════════════════════════════════════════\n";
echo "📢 PUBLISHING AGENT V39\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "🎯 Agent ID: $agentId\n\n";

// PATCH agent to publish it
$payload = json_encode([
    'is_published' => true,
    'version_title' => 'V39 Flow Canvas Fix - check_availability edges added'
]);

echo "📤 Publishing agent...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/update-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to publish agent! HTTP $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$agent = json_decode($response, true);

echo "✅ Agent published successfully!\n\n";
echo "📊 Agent Details:\n";
echo "   Version: " . ($agent['version'] ?? 'N/A') . "\n";
echo "   Published: " . (($agent['is_published'] ?? false) ? 'YES' : 'NO') . "\n";
echo "   Version Title: " . ($agent['version_title'] ?? 'N/A') . "\n";

if (isset($agent['last_modification_timestamp'])) {
    $timestamp = $agent['last_modification_timestamp'] / 1000;
    $dateTime = new DateTime('@' . $timestamp);
    $dateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
    echo "   Last Modified: " . $dateTime->format('Y-m-d H:i:s') . " (Berlin)\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "🎉 SUCCESS! AGENT IS NOW LIVE!\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "⏳ WAIT 60 SECONDS for deployment to propagate\n\n";

echo "🧪 THEN TEST:\n";
echo "   1. Call: +493033081738\n";
echo "   2. Say: \"Termin heute 16 Uhr für Herrenhaarschnitt\"\n";
echo "   3. Expected: Agent pauses, then gives CORRECT availability\n\n";

echo "═══════════════════════════════════════════════════════\n";
