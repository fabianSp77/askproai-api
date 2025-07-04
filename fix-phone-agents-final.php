<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Telefonnummern-Agent-Zuordnung korrigieren ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

// Get API key
$apiKey = config('services.retell.api_key') ?? env('RETELL_TOKEN');
if (!$apiKey) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    if (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
        $apiKey = trim($matches[1]);
    }
}

if (!$apiKey) {
    echo "❌ RETELL_TOKEN nicht gefunden!\n";
    exit(1);
}

$baseUrl = 'https://api.retellai.com';

// 1. Get all phone numbers
echo "1. Hole alle Telefonnummern...\n\n";

$ch = curl_init($baseUrl . '/list-phone-numbers');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    echo "❌ Fehler beim Abrufen der Telefonnummern: " . $response . "\n";
    exit(1);
}

$phoneNumbers = json_decode($response, true);
$targetAgentId = 'agent_9a8202a740cd3120d96fcfda1e'; // Der Agent mit korrektem Webhook
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$updated = 0;
$errors = 0;

echo "Gefundene Telefonnummern: " . count($phoneNumbers) . "\n";
echo "Ziel-Agent: " . $targetAgentId . "\n";
echo "Webhook URL: " . $webhookUrl . "\n\n";

// 2. Process each phone number
foreach ($phoneNumbers as $phone) {
    $phoneNumber = $phone['phone_number'] ?? 'Unknown';
    $phoneId = $phone['phone_number_id'] ?? $phone['id'] ?? null;
    $currentAgentId = $phone['agent_id'] ?? $phone['inbound_agent_id'] ?? null;
    
    echo "📞 Nummer: " . $phoneNumber . "\n";
    
    if (!$phoneId) {
        echo "   ❌ Keine ID gefunden - überspringe\n\n";
        continue;
    }
    
    echo "   ID: " . $phoneId . "\n";
    echo "   Aktueller Agent: " . ($currentAgentId ?? 'NICHT ZUGEWIESEN') . "\n";
    
    // Update phone number
    $updateData = [
        'inbound_agent_id' => $targetAgentId,
        'webhook_url' => $webhookUrl
    ];
    
    $ch = curl_init($baseUrl . '/update-phone-number/' . $phoneId);
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
        echo "   ✅ Agent erfolgreich zugewiesen!\n";
        $updated++;
    } else {
        echo "   ❌ Fehler (HTTP $updateHttpCode): " . $updateResponse . "\n";
        $errors++;
    }
    
    echo "\n";
}

// 3. Summary
echo "=== Zusammenfassung ===\n";
echo "✅ Erfolgreich aktualisiert: " . $updated . "\n";
echo "❌ Fehler: " . $errors . "\n";
echo "📊 Gesamt: " . count($phoneNumbers) . "\n\n";

// 4. Test recent calls again
echo "4. Prüfe aktuelle Anrufe...\n";
$ch = curl_init($baseUrl . '/v2/list-calls');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'limit' => 5,
    'sort_order' => 'descending'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $calls = json_decode($response, true);
    echo "✅ " . count($calls) . " aktuelle Anrufe gefunden\n";
    foreach ($calls as $call) {
        echo "   - " . ($call['from_number'] ?? 'Unknown') . " → " . 
             ($call['to_number'] ?? 'Unknown') . " (" . 
             date('Y-m-d H:i:s', ($call['start_timestamp'] ?? 0) / 1000) . ")\n";
    }
} else {
    echo "❌ Fehler beim Abrufen der Anrufe\n";
}

echo "\n=== Fertig ===\n";
echo "\nNächste Schritte:\n";
echo "1. Führen Sie einen Test-Anruf durch\n";
echo "2. Prüfen Sie das Webhook-Log: tail -f storage/logs/laravel.log | grep -i retell\n";
echo "3. Falls keine Webhooks ankommen, prüfen Sie die Agent-Konfiguration in Retell.ai\n";