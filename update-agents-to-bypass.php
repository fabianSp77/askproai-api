<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEMPORÄRE Webhook-Bypass Aktivierung ===\n";
echo "⚠️  ACHTUNG: Dies deaktiviert die Signatur-Verifikation!\n";
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
$bypassUrl = 'https://api.askproai.de/api/retell/webhook-bypass';

echo "🔄 Aktualisiere Webhook-URL zu Bypass-Endpoint...\n";
echo "Neue URL: $bypassUrl\n\n";

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

// 2. Update only the main agent
$mainAgentId = 'agent_9a8202a740cd3120d96fcfda1e';
$found = false;

foreach ($agents as $agent) {
    if ($agent['agent_id'] === $mainAgentId) {
        $found = true;
        echo "\n📤 Aktualisiere Haupt-Agent: " . ($agent['agent_name'] ?? 'Unnamed') . "\n";
        echo "   Aktuelle URL: " . ($agent['webhook_url'] ?? 'Nicht gesetzt') . "\n";
        
        // Update agent
        $updateData = [
            'webhook_url' => $bypassUrl
        ];
        
        $ch = curl_init($baseUrl . '/update-agent/' . $mainAgentId);
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
            echo "   ✅ Webhook auf Bypass-URL aktualisiert!\n";
            $updated++;
        } else {
            echo "   ❌ Fehler beim Update: " . $updateResponse . "\n";
        }
        break;
    }
}

if (!$found) {
    echo "❌ Haupt-Agent nicht gefunden!\n";
} else {
    echo "\n✅ Webhook-Bypass aktiviert!\n";
    echo "\n📞 NÄCHSTE SCHRITTE:\n";
    echo "1. Führen Sie einen Test-Anruf durch\n";
    echo "2. Prüfen Sie die Logs: tail -f storage/logs/laravel.log | grep -i webhook\n";
    echo "3. Der Anruf sollte sofort verarbeitet werden\n";
    echo "\n⚠️  WICHTIG: Nach dem Test wieder auf normale URL zurücksetzen!\n";
}

echo "\n🔍 Log-Monitoring läuft bereits im Hintergrund.\n";