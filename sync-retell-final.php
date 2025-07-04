<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\RetellV2Service;
use App\Models\Company;
use App\Models\RetellAgent;
use Illuminate\Support\Facades\Auth;

$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

// Get company with Retell API key
$company = Company::first();
if (!$company || !$company->retell_api_key) {
    die("Error: No company with Retell API key found\n");
}

// Set company context
app()->instance('current_company_id', $company->id);

$retellService = new RetellV2Service($company->retell_api_key);

echo "📥 Lade aktuelle Konfiguration von Retell...\n";

// Get current agent configuration
$currentAgent = $retellService->getAgent($agentId);

if (!$currentAgent) {
    die("Error: Could not fetch agent configuration\n");
}

echo "✅ Agent gefunden: " . $currentAgent['agent_name'] . "\n";

// Update local database
$localAgent = RetellAgent::withoutGlobalScopes()->where('agent_id', $agentId)->first();

if (!$localAgent) {
    echo "📝 Erstelle neuen Agent in lokaler Datenbank...\n";
    $localAgent = new RetellAgent();
    $localAgent->agent_id = $agentId;
    $localAgent->company_id = $company->id;
    $localAgent->name = $currentAgent['agent_name'] ?? 'Retell Agent';
} else {
    echo "📝 Aktualisiere bestehenden Agent...\n";
}

// Update configuration
$localAgent->configuration = $currentAgent;
$localAgent->is_active = true;
$localAgent->save();

echo "✅ Lokale Datenbank aktualisiert\n";

// Verify the configuration
echo "\n🔍 VERIFIZIERUNG DER KONFIGURATION:\n";
echo str_repeat("=", 60) . "\n";

// Check if using retell-llm
if (isset($currentAgent['response_engine']['llm_id'])) {
    $llmId = $currentAgent['response_engine']['llm_id'];
    echo "✅ Verwendet Retell LLM: " . $llmId . "\n";
    
    // Get LLM configuration
    $llmConfig = $currentAgent['llm_configuration'] ?? null;
    if ($llmConfig && isset($llmConfig['general_tools'])) {
        echo "✅ " . count($llmConfig['general_tools']) . " Custom Functions gefunden\n\n";
        
        // Check each function
        $functionsWithCallId = [];
        $functionsWithoutCallId = [];
        $hasPhoneParams = false;
        
        foreach ($llmConfig['general_tools'] as $tool) {
            if ($tool['type'] === 'custom') {
                $hasCallId = false;
                $phoneParams = [];
                
                if (isset($tool['parameters']['properties'])) {
                    foreach ($tool['parameters']['properties'] as $param => $config) {
                        if (in_array($param, ['phone_number', 'telefonnummer', 'caller_phone_number', 'from_number'])) {
                            $phoneParams[] = $param;
                            $hasPhoneParams = true;
                        }
                        if ($param === 'call_id') {
                            $hasCallId = true;
                        }
                    }
                }
                
                if ($hasCallId) {
                    $functionsWithCallId[] = $tool['name'];
                } else if (in_array($tool['name'], ['check_customer', 'collect_appointment_data', 'cancel_appointment', 'reschedule_appointment', 'book_appointment'])) {
                    $functionsWithoutCallId[] = $tool['name'];
                }
                
                if (!empty($phoneParams)) {
                    echo "⚠️  Function '" . $tool['name'] . "' hat noch Telefonnummer-Parameter: " . implode(', ', $phoneParams) . "\n";
                }
            }
        }
        
        if (!$hasPhoneParams) {
            echo "✅ KEINE Telefonnummer-Parameter mehr gefunden!\n";
        }
        
        if (!empty($functionsWithCallId)) {
            echo "\n✅ Functions MIT call_id Parameter:\n";
            foreach ($functionsWithCallId as $func) {
                echo "   - " . $func . "\n";
            }
        }
        
        if (!empty($functionsWithoutCallId)) {
            echo "\n⚠️  Functions OHNE call_id Parameter (sollten einen haben):\n";
            foreach ($functionsWithoutCallId as $func) {
                echo "   - " . $func . "\n";
            }
        }
    }
    
    // Check prompt
    if (isset($llmConfig['general_prompt'])) {
        if (strpos($llmConfig['general_prompt'], 'NIEMALS nach der Telefonnummer fragen') !== false) {
            echo "\n✅ Prompt enthält korrekte Anweisung über Telefonnummer\n";
        } else {
            echo "\n⚠️  Prompt enthält KEINE explizite Anweisung über Telefonnummer\n";
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎉 SYNCHRONISATION ABGESCHLOSSEN!\n";
echo str_repeat("=", 60) . "\n";

echo "\n📞 NÄCHSTE SCHRITTE:\n";
echo "1. Mache einen Testanruf\n";
echo "2. Der Agent sollte:\n";
echo "   ✓ NICHT mehr nach der Telefonnummer fragen\n";
echo "   ✓ Die Telefonnummer automatisch erkennen\n";
echo "   ✓ Termine korrekt buchen können\n";
echo "   ✓ Bei Problemen automatisch die call_id verwenden\n";

echo "\n💡 TIPP: Wenn der Agent immer noch nach der Telefonnummer fragt,\n";
echo "stelle sicher, dass alle Functions die call_id verwenden!\n";