<?php

/**
 * Repariert Retell Agents
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RetellAgent;
use App\Models\Company;
use App\Services\RetellV2Service;

echo "\n=== Retell Agent Repair ===\n\n";

// Expected agent
$expectedAgentId = 'agent_9a8202a740cd3120d96fcfda1e';
$expectedAgentName = 'Online: Assistent für Fabian Spitzer Rechtliches/V33';

// Get company
$company = Company::first();
$apiKey = $company->retell_api_key ?: config('services.retell.api_key');
$retellService = new RetellV2Service(is_encrypted($apiKey) ? decrypt($apiKey) : $apiKey);

echo "1. Hole Agent von Retell API...\n";
try {
    $remoteAgent = $retellService->getAgent($expectedAgentId);
    if (!$remoteAgent) {
        echo "❌ Agent nicht in Retell gefunden\n";
        exit(1);
    }
    
    echo "✅ Agent gefunden: " . ($remoteAgent['agent_name'] ?? 'Unknown') . "\n";
    
    // Get LLM config
    $llmConfig = null;
    if (isset($remoteAgent['llm_id'])) {
        echo "\n2. Hole LLM Konfiguration...\n";
        $llmConfig = $retellService->getRetellLLM($remoteAgent['llm_id']);
        if ($llmConfig) {
            echo "✅ LLM gefunden mit " . count($llmConfig['functions'] ?? []) . " Functions\n";
        }
    }
    
    // Merge configurations
    $fullConfig = $remoteAgent;
    if ($llmConfig) {
        $fullConfig['functions'] = $llmConfig['functions'] ?? [];
        $fullConfig['llm_config'] = $llmConfig;
    }
    
    echo "\n3. Update lokale Datenbank...\n";
    
    // Find or create agent
    $agent = RetellAgent::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('name', 'LIKE', '%Fabian Spitzer%')
        ->first();
    
    if ($agent) {
        echo "Gefunden: {$agent->name}\n";
        
        // Update with correct data
        $agent->retell_agent_id = $expectedAgentId;
        $agent->configuration = $fullConfig;
        $agent->llm_id = $remoteAgent['llm_id'] ?? null;
        $agent->voice_id = $remoteAgent['voice_id'] ?? null;
        $agent->is_active = true;
        $agent->version = $agent->version ?: 'V33';
        $agent->base_name = 'Online: Assistent für Fabian Spitzer Rechtliches';
        $agent->last_synced_at = now();
        $agent->save();
        
        echo "✅ Agent aktualisiert!\n";
    } else {
        // Create new
        $agent = RetellAgent::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'company_id' => $company->id,
            'retell_agent_id' => $expectedAgentId,
            'name' => $expectedAgentName,
            'configuration' => $fullConfig,
            'llm_id' => $remoteAgent['llm_id'] ?? null,
            'voice_id' => $remoteAgent['voice_id'] ?? null,
            'is_active' => true,
            'version' => 'V33',
            'base_name' => 'Online: Assistent für Fabian Spitzer Rechtliches',
            'last_synced_at' => now(),
        ]);
        
        echo "✅ Agent erstellt!\n";
    }
    
    // Show result
    echo "\n4. Agent Status:\n";
    echo "   ID: {$agent->retell_agent_id}\n";
    echo "   Name: {$agent->name}\n";
    echo "   Active: " . ($agent->is_active ? '✅ JA' : '❌ NEIN') . "\n";
    echo "   Version: {$agent->version}\n";
    echo "   Functions: " . count($fullConfig['functions'] ?? []) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

function is_encrypted($value) {
    return strpos($value, 'eyJpdiI6') === 0;
}