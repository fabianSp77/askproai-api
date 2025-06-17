<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test: Bidirektionale Retell.ai Kommunikation ===\n\n";

// Test-Webhook simulieren
$webhookUrl = 'http://localhost:8000/api/retell/webhook';

// Test 1: Normal call_inbound ohne Verfügbarkeitsprüfung
echo "Test 1: Normal inbound call\n";
$payload1 = [
    'event' => 'call_inbound',
    'call_id' => 'test_call_001',
    'call_inbound' => [
        'from_number' => '+491234567890',
        'to_number' => '+493041735870'
    ]
];

$response1 = Http::post($webhookUrl, $payload1);
echo "Response Status: " . $response1->status() . "\n";
echo "Response Body:\n";
print_r($response1->json());
echo "\n---\n\n";

// Test 2: Inbound call mit Verfügbarkeitsprüfung
echo "Test 2: Inbound call mit Verfügbarkeitsprüfung\n";
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$payload2 = [
    'event' => 'call_inbound',
    'call_id' => 'test_call_002',
    'call_inbound' => [
        'from_number' => '+491234567890',
        'to_number' => '+493041735870'
    ],
    'dynamic_variables' => [
        'check_availability' => true,
        'requested_date' => $tomorrow,
        'event_type_id' => 1 // Annahme: Event Type 1 existiert
    ]
];

$response2 = Http::post($webhookUrl, $payload2);
echo "Response Status: " . $response2->status() . "\n";
echo "Response Body:\n";
print_r($response2->json());
echo "\n---\n\n";

// Test 3: Direkte Antwort-Struktur prüfen
echo "Test 3: Prüfe Antwortstruktur für Retell.ai\n";
if ($response2->successful()) {
    $data = $response2->json();
    
    if (isset($data['response'])) {
        echo "✓ Response-Struktur vorhanden\n";
        
        if (isset($data['response']['agent_id'])) {
            echo "✓ Agent ID: " . $data['response']['agent_id'] . "\n";
        }
        
        if (isset($data['response']['dynamic_variables'])) {
            echo "✓ Dynamic Variables:\n";
            foreach ($data['response']['dynamic_variables'] as $key => $value) {
                echo "  - $key: $value\n";
            }
        }
        
        if (isset($data['response']['dynamic_variables']['available_slots'])) {
            echo "\n✓ Verfügbare Slots gefunden: " . $data['response']['dynamic_variables']['available_slots'] . "\n";
            echo "✓ Anzahl Slots: " . ($data['response']['dynamic_variables']['slots_count'] ?? 0) . "\n";
        }
    } else {
        echo "✗ Keine Response-Struktur in der Antwort\n";
    }
}

echo "\n=== Test abgeschlossen ===\n";
echo "\nHinweise für Retell.ai Agent-Konfiguration:\n";
echo "1. Der Agent kann auf folgende dynamic_variables zugreifen:\n";
echo "   - available_slots: String mit verfügbaren Zeiten\n";
echo "   - slots_count: Anzahl der verfügbaren Slots\n";
echo "   - availability_checked: Boolean ob Prüfung durchgeführt wurde\n";
echo "\n2. Beispiel-Prompt für den Agent:\n";
echo "   'Wenn availability_checked = true und slots_count > 0, sage:\n";
echo "   \"Ich habe folgende Termine gefunden: {available_slots}. Welche Zeit passt Ihnen am besten?\"'\n";
echo "\n3. Für die Verfügbarkeitsprüfung muss der Agent folgende Variables setzen:\n";
echo "   - check_availability: true\n";
echo "   - requested_date: YYYY-MM-DD Format\n";
echo "   - event_type_id: ID des gewünschten Services\n";