<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 PUBLISH AGENT WITH DEBUG OUTPUT\n";
echo "═══════════════════════════════════════════════════════\n\n";

$url = "https://api.retellai.com/publish-agent/$agentId";

echo "📤 Calling: POST $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n\n";

echo "Raw Response:\n";
echo $response . "\n\n";

echo "Response Length: " . strlen($response) . " bytes\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);

    echo "Decoded Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

    if (empty($response)) {
        echo "⚠️  Response is EMPTY - this is likely the issue!\n";
        echo "   The publish endpoint may return an empty 200 response on success.\n\n";
    }
} else {
    echo "❌ HTTP $httpCode - Publish failed\n";
}

// Now fetch the agent to check its status
echo "═══════════════════════════════════════════════════════\n";
echo "🔍 FETCHING AGENT TO VERIFY PUBLISHED STATUS\n";
echo "═══════════════════════════════════════════════════════\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$getResponse = curl_exec($ch);
$getHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($getHttpCode === 200) {
    $agent = json_decode($getResponse, true);

    echo "✅ Agent Details:\n";
    echo "   Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
    echo "   Version: " . ($agent['version'] ?? 'N/A') . "\n";
    echo "   is_published: ";
    var_dump($agent['is_published'] ?? null);
    echo "\n";
    echo "   Version Title: " . ($agent['version_title'] ?? 'N/A') . "\n";

    if (isset($agent['last_modification_timestamp'])) {
        $timestamp = $agent['last_modification_timestamp'] / 1000;
        $dateTime = new DateTime('@' . $timestamp);
        $dateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
        echo "   Last Modified: " . $dateTime->format('Y-m-d H:i:s') . " (Berlin)\n";
    }

    echo "\n";

    if ($agent['is_published'] === true) {
        echo "✅ ✅ ✅ AGENT IS NOW PUBLISHED! ✅ ✅ ✅\n\n";
    } else {
        echo "❌ Agent is still NOT published\n\n";
    }
}
