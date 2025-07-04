<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.retell.api_key') ?? env('RETELL_TOKEN');
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$newWebhookUrl = 'https://api.askproai.de/api/retell/webhook-simple';

echo "=== Update Retell Agent Webhook URL ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo "Agent ID: $agentId\n";
echo "Neue Webhook URL: $newWebhookUrl\n\n";

// Update agent
$ch = curl_init("https://api.retellai.com/update-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'webhook_url' => $newWebhookUrl
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $agent = json_decode($response, true);
    echo "✅ Agent erfolgreich aktualisiert!\n";
    echo "Aktuelle Webhook URL: " . ($agent['webhook_url'] ?? 'NICHT GESETZT') . "\n";
} else {
    echo "❌ Fehler beim Update: HTTP $httpCode\n";
    echo "Response: $response\n";
}

// Verify update
echo "\n=== Verifiziere Update ===\n";
$ch = curl_init("https://api.retellai.com/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $agent = json_decode($response, true);
    echo "Webhook URL: " . ($agent['webhook_url'] ?? 'NICHT GESETZT') . "\n";
    
    if ($agent['webhook_url'] === $newWebhookUrl) {
        echo "✅ Update erfolgreich verifiziert!\n";
    } else {
        echo "⚠️ Webhook URL stimmt nicht überein!\n";
    }
} else {
    echo "❌ Fehler bei Verifizierung\n";
}