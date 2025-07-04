<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Update Retell Agent Webhook Configuration ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

// Get API key
$apiKey = config('services.retell.api_key') ?? env('RETELL_TOKEN');
if (!$apiKey) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    if (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
        $apiKey = trim($matches[1]);
    }
}

$baseUrl = 'https://api.retellai.com';
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';

// 1. Get all agents
echo "1. Hole alle Agenten...\n";
$ch = curl_init($baseUrl . '/list-agents');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    echo "❌ Fehler beim Abrufen der Agenten\n";
    exit(1);
}

$agents = json_decode($response, true);
$updated = 0;
$skipped = 0;

// 2. Update each agent with webhook URL
foreach ($agents as $agent) {
    $agentId = $agent['agent_id'];
    $agentName = $agent['agent_name'] ?? 'Unnamed';
    $currentWebhook = $agent['webhook_url'] ?? '';
    
    echo "\n📤 Agent: $agentName\n";
    echo "   ID: $agentId\n";
    echo "   Aktueller Webhook: " . ($currentWebhook ?: 'NICHT GESETZT') . "\n";
    
    if ($currentWebhook === $webhookUrl) {
        echo "   ✅ Webhook bereits korrekt\n";
        $skipped++;
        continue;
    }
    
    // Update agent with webhook
    $updateData = [
        'webhook_url' => $webhookUrl
    ];
    
    $ch = curl_init($baseUrl . '/update-agent/' . $agentId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    $updateResponse = curl_exec($ch);
    $updateHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($updateHttpCode == 200) {
        echo "   ✅ Webhook aktualisiert!\n";
        $updated++;
    } else {
        echo "   ❌ Fehler beim Update (HTTP $updateHttpCode)\n";
        echo "   Response: " . substr($updateResponse, 0, 200) . "\n";
    }
}

// 3. Summary
echo "\n=== Zusammenfassung ===\n";
echo "✅ Aktualisiert: $updated\n";
echo "⏭️  Übersprungen (bereits korrekt): $skipped\n";
echo "📊 Gesamt: " . count($agents) . "\n";

// 4. Test webhook manually
echo "\n=== Teste Webhook manuell ===\n";
echo "Sende Test-Event an: $webhookUrl\n";

$testPayload = [
    'event' => 'test',
    'timestamp' => time() * 1000,
    'message' => 'Manual webhook test from update script'
];

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: RetellAI-Webhook-Test'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$testResponse = curl_exec($ch);
$testHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Test Response: HTTP $testHttpCode\n";
echo "Body: " . $testResponse . "\n";

echo "\n✅ Webhook-Konfiguration abgeschlossen!\n";
echo "Führen Sie jetzt einen neuen Testanruf durch.\n";