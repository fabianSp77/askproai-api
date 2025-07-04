<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Retell Phone Agent Assignment Fix ===\n";
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

// First, let's check what fields the API actually returns
echo "1. Teste API Response Format...\n";

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

// Debug first phone number structure
if (!empty($phoneNumbers)) {
    echo "\nStruktur der ersten Telefonnummer:\n";
    print_r($phoneNumbers[0]);
    echo "\n";
}

// Get available agent
echo "\n2. Hole verfügbare Agenten...\n";
$ch = curl_init($baseUrl . '/list-agents');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$agentResponse = curl_exec($ch);
$agentHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($agentHttpCode == 200) {
    $agents = json_decode($agentResponse, true);
    echo "✅ " . count($agents) . " Agent(en) gefunden\n\n";
    
    $targetAgent = null;
    foreach ($agents as $agent) {
        if ($agent['webhook_url'] === 'https://api.askproai.de/api/retell/webhook') {
            $targetAgent = $agent;
            break;
        }
    }
    
    if (!$targetAgent && !empty($agents)) {
        $targetAgent = $agents[0]; // Fallback to first agent
    }
    
    if ($targetAgent) {
        echo "Verwende Agent: " . ($targetAgent['agent_name'] ?? 'Unnamed') . "\n";
        echo "Agent ID: " . $targetAgent['agent_id'] . "\n\n";
        
        // Now try different update approaches
        echo "3. Versuche Telefonnummern zu aktualisieren...\n\n";
        
        foreach ($phoneNumbers as $phone) {
            $phoneNumber = $phone['phone_number'] ?? null;
            if (!$phoneNumber) continue;
            
            echo "📞 Verarbeite: " . $phoneNumber . "\n";
            
            // Try approach 1: Update using phone number directly
            $updateData = [
                'agent_id' => $targetAgent['agent_id'],
                'inbound_agent_id' => $targetAgent['agent_id'],
                'webhook_url' => 'https://api.askproai.de/api/retell/webhook'
            ];
            
            // First try with phone number as identifier
            $ch = curl_init($baseUrl . '/update-phone-number/' . urlencode($phoneNumber));
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
                echo "   ✅ Erfolgreich aktualisiert!\n";
            } else {
                echo "   ❌ Fehler (HTTP $updateHttpCode): " . substr($updateResponse, 0, 100) . "...\n";
                
                // Try approach 2: Different field names
                if (isset($phone['id'])) {
                    echo "   🔄 Versuche mit ID: " . $phone['id'] . "\n";
                    
                    $ch = curl_init($baseUrl . '/update-phone-number/' . $phone['id']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json'
                    ]);
                    
                    $updateResponse2 = curl_exec($ch);
                    $updateHttpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($updateHttpCode2 == 200) {
                        echo "   ✅ Erfolgreich mit ID!\n";
                    } else {
                        echo "   ❌ Auch mit ID fehlgeschlagen\n";
                    }
                }
            }
            echo "\n";
        }
    }
}

echo "\n=== Empfehlung ===\n";
echo "Falls die automatische Zuordnung nicht funktioniert:\n";
echo "1. Loggen Sie sich bei Retell.ai ein\n";
echo "2. Gehen Sie zu Phone Numbers\n";
echo "3. Weisen Sie jeder Nummer den Agent zu\n";
echo "4. Stellen Sie sicher, dass der Webhook korrekt ist:\n";
echo "   https://api.askproai.de/api/retell/webhook\n";