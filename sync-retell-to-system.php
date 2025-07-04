<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\RetellV2Service;
use App\Models\Company;
use App\Models\RetellAgent;

$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

// Get company with Retell API key
$company = Company::first();
if (!$company || !$company->retell_api_key) {
    die("Error: No company with Retell API key found\n");
}

$retellService = new RetellV2Service($company->retell_api_key);

echo "📥 Lade aktuelle Konfiguration von Retell...\n";

// Get current agent configuration
$currentAgent = $retellService->getAgent($agentId);

if (!$currentAgent) {
    die("Error: Could not fetch agent configuration\n");
}

echo "✅ Agent gefunden: " . $currentAgent['agent_name'] . "\n";

// Update local database
$localAgent = RetellAgent::where('agent_id', $agentId)->first();

if (!$localAgent) {
    echo "📝 Erstelle neuen Agent in lokaler Datenbank...\n";
    $localAgent = new RetellAgent();
    $localAgent->agent_id = $agentId;
    $localAgent->company_id = $company->id;
    $localAgent->name = $currentAgent['agent_name'] ?? 'Retell Agent';
}

// Update configuration
$localAgent->configuration = $currentAgent;
$localAgent->is_active = true;
$localAgent->save();

echo "✅ Lokale Datenbank aktualisiert\n";

// Verify the configuration
echo "\n🔍 Verifiziere Konfiguration...\n";

// Check if using retell-llm
if (isset($currentAgent['response_engine']['llm_id'])) {
    $llmId = $currentAgent['response_engine']['llm_id'];
    echo "✅ Verwendet Retell LLM: " . $llmId . "\n";
    
    // Get LLM configuration
    $llmConfig = $currentAgent['llm_configuration'] ?? null;
    if ($llmConfig && isset($llmConfig['general_tools'])) {
        echo "✅ " . count($llmConfig['general_tools']) . " Custom Functions gefunden\n";
        
        // Check each function
        $hasPhoneParams = false;
        foreach ($llmConfig['general_tools'] as $tool) {
            if ($tool['type'] === 'custom' && isset($tool['parameters']['properties'])) {
                foreach (array_keys($tool['parameters']['properties']) as $param) {
                    if (in_array($param, ['phone_number', 'telefonnummer', 'caller_phone_number', 'from_number'])) {
                        $hasPhoneParams = true;
                        echo "⚠️  Function " . $tool['name'] . " hat noch Telefonnummer-Parameter: " . $param . "\n";
                    }
                }
            }
        }
        
        if (!$hasPhoneParams) {
            echo "✅ Keine Telefonnummer-Parameter mehr gefunden\n";
        }
    }
}

echo "\n🎉 Synchronisation abgeschlossen!\n";
echo "\n📞 Du kannst jetzt einen Testanruf machen um zu prüfen ob alles funktioniert.\n";
echo "Der Agent sollte:\n";
echo "  - NICHT mehr nach der Telefonnummer fragen\n";
echo "  - Die Telefonnummer automatisch erkennen\n";
echo "  - Termine korrekt buchen können\n";