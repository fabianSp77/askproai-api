<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get Agent ID and API Key
$agentId = config('services.retellai.agent_id') ?: 'agent_9a8202a740cd3120d96fcfda1e';
$apiKey = config('services.retellai.api_key');

if (!$apiKey) {
    die("ERROR: Retell API Key not found in config/services.php\n");
}

echo "🔍 Fetching Retell Agent Configuration...\n";
echo "Agent ID: $agentId\n\n";

// Fetch agent from Retell API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/v2/agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("ERROR: Failed to fetch agent (HTTP $httpCode)\n$response\n");
}

$agent = json_decode($response, true);

if (!isset($agent['general_prompt'])) {
    die("ERROR: No general_prompt found in agent response\n");
}

$generalPrompt = $agent['general_prompt'];

echo "=== GENERAL PROMPT (first 1000 chars) ===\n";
echo substr($generalPrompt, 0, 1000) . "...\n\n";

echo "=== ANALYSIS ===\n";

// Check if Hairdetox is mentioned
if (stripos($generalPrompt, 'Hairdetox') !== false || stripos($generalPrompt, 'Hair Detox') !== false) {
    echo "✅ Hairdetox ist erwähnt\n";
} else {
    echo "❌ Hairdetox ist NICHT erwähnt\n";
}

// Check if services are listed
$serviceCount = 0;
$services = [
    'Herrenhaarschnitt',
    'Damenhaarschnitt',
    'Kinderhaarschnitt',
    'Balayage',
    'Dauerwelle',
    'Ansatzfärbung',
    'Hairdetox',
    'Olaplex'
];

foreach ($services as $service) {
    if (stripos($generalPrompt, $service) !== false) {
        $serviceCount++;
    }
}

echo "✅ $serviceCount von " . count($services) . " Beispiel-Services gefunden\n";

if ($serviceCount < 3) {
    echo "\n⚠️ PROBLEM: Nur wenige Services im General Prompt!\n";
    echo "   Der Agent sollte ALLE verfügbaren Services kennen.\n";
}

// Get all active services from database
echo "\n=== ALLE AKTIVEN SERVICES IN DATENBANK ===\n";
$allServices = DB::table('services')
    ->where('company_id', 1)
    ->where('is_active', true)
    ->orderBy('name')
    ->get(['id', 'name', 'price', 'duration_minutes']);

foreach ($allServices as $svc) {
    $inPrompt = stripos($generalPrompt, $svc->name) !== false ? '✅' : '❌';
    echo "$inPrompt {$svc->name} (ID: {$svc->id}, {$svc->price}€, {$svc->duration_minutes} Min)\n";
}

echo "\n=== EMPFEHLUNG ===\n";
echo "1. ⚠️ Seeder ausführen: php artisan db:seed --class=Friseur1ServiceSynonymsSeeder --force\n";
echo "   → Fügt ~150 Synonyme hinzu, inkl. 'Hair Detox' für 'Hairdetox'\n\n";

echo "2. 🔧 General Prompt aktualisieren mit ALLEN Services:\n";
echo "   → Agent muss wissen, welche Services verfügbar sind\n";
echo "   → Agent sollte NIEMALS aus LLM-Wissen sagen 'wir bieten das nicht an'\n";
echo "   → Stattdessen: Immer Backend fragen oder Service-Liste prüfen\n\n";

echo "3. 📝 Conversation Flow anpassen:\n";
echo "   → Bei unbekanntem Service: Backend nach ähnlichen Services fragen\n";
echo "   → NICHT selbst entscheiden, ob Service existiert\n";
