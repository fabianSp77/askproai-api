<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Company;

$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

$company = Company::first();
if (!$company || !$company->retell_api_key) {
    die("Error: No company with Retell API key found\n");
}

echo "🔍 ÜBERPRÜFE RETELL WEBHOOK KONFIGURATION\n";
echo str_repeat("=", 60) . "\n\n";

// Get the agent details
$agentResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $company->retell_api_key,
])->get("https://api.retellai.com/get-agent/{$agentId}");

if (!$agentResponse->successful()) {
    die("Error fetching agent: " . $agentResponse->body() . "\n");
}

$agent = $agentResponse->json();
echo "Agent: " . $agent['agent_name'] . "\n";
echo "Webhook URL: " . ($agent['webhook_url'] ?? 'NICHT GESETZT') . "\n\n";

// Check if it's using retell-llm
if (!isset($agent['response_engine']['llm_id'])) {
    die("Error: Agent is not using retell-llm\n");
}

$llmId = $agent['response_engine']['llm_id'];

// Get current LLM configuration
$llmResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $company->retell_api_key,
])->get("https://api.retellai.com/get-retell-llm/{$llmId}");

if (!$llmResponse->successful()) {
    die("Error fetching LLM: " . $llmResponse->body() . "\n");
}

$llmConfig = $llmResponse->json();

echo "📋 CUSTOM FUNCTIONS KONFIGURATION:\n";
echo str_repeat("-", 60) . "\n";

if (!isset($llmConfig['general_tools']) || empty($llmConfig['general_tools'])) {
    echo "❌ KEINE Custom Functions gefunden!\n";
} else {
    echo "Gefundene Functions (" . count($llmConfig['general_tools']) . "):\n\n";
    
    foreach ($llmConfig['general_tools'] as $index => $tool) {
        echo "[" . ($index + 1) . "] " . $tool['name'] . " (Type: " . $tool['type'] . ")\n";
        
        if ($tool['type'] === 'custom') {
            echo "    URL: " . ($tool['url'] ?? 'NICHT GESETZT') . "\n";
            
            // Check if URL is correct
            if (isset($tool['url'])) {
                if (strpos($tool['url'], 'api.askproai.de') !== false) {
                    echo "    ✅ URL zeigt auf unser System\n";
                } else {
                    echo "    ⚠️  URL zeigt auf externes System\n";
                }
                
                // Check specific endpoints
                if (strpos($tool['url'], '/api/retell/') !== false) {
                    $endpoint = str_replace('https://api.askproai.de', '', $tool['url']);
                    echo "    Endpoint: " . $endpoint . "\n";
                }
            }
            
            // Check parameters
            if (isset($tool['parameters']['properties'])) {
                $params = array_keys($tool['parameters']['properties']);
                echo "    Parameters: " . implode(', ', $params) . "\n";
                
                // Check for call_id
                if (in_array('call_id', $params)) {
                    echo "    ✅ Hat call_id Parameter\n";
                }
            }
        }
        echo "\n";
    }
}

echo str_repeat("=", 60) . "\n";
echo "🔧 ERWARTETE ENDPOINTS:\n";
echo str_repeat("=", 60) . "\n\n";

$expectedEndpoints = [
    'check_customer' => 'https://api.askproai.de/api/retell/check-customer',
    'check_availability' => 'https://api.askproai.de/api/retell/check-availability',
    'collect_appointment_data' => 'https://api.askproai.de/api/retell/collect-appointment',
    'cancel_appointment' => 'https://api.askproai.de/api/retell/cancel-appointment',
    'reschedule_appointment' => 'https://api.askproai.de/api/retell/reschedule-appointment',
    'current_time_berlin' => 'https://api.askproai.de/api/retell/current-time-berlin'
];

foreach ($expectedEndpoints as $funcName => $expectedUrl) {
    $found = false;
    foreach ($llmConfig['general_tools'] ?? [] as $tool) {
        if ($tool['name'] === $funcName && isset($tool['url'])) {
            if ($tool['url'] === $expectedUrl) {
                echo "✅ " . $funcName . " -> Korrekte URL\n";
            } else {
                echo "⚠️  " . $funcName . " -> Falsche URL: " . $tool['url'] . "\n";
            }
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "❌ " . $funcName . " -> FEHLT oder hat keine URL\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📝 DIAGNOSE:\n";
echo str_repeat("=", 60) . "\n\n";

// Check webhook secret
if (!$company->retell_webhook_secret) {
    echo "⚠️  Kein Retell Webhook Secret in der Datenbank gespeichert\n";
} else {
    echo "✅ Retell Webhook Secret vorhanden\n";
}

// Show correct configuration
echo "\n🔧 KORREKTE KONFIGURATION:\n";
echo "Webhook URL: https://api.askproai.de/api/retell/webhook\n";
echo "Function Call URL Pattern: https://api.askproai.de/api/retell/{function-name}\n";

echo "\n💡 WICHTIG: Alle Custom Functions müssen die korrekte URL haben!\n";