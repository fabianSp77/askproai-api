<?php

/**
 * Holt die Functions für den Agent
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RetellAgent;
use App\Models\Company;
use App\Services\RetellV2Service;

echo "\n=== Fix Agent Functions ===\n\n";

$company = Company::first();
$apiKey = $company->retell_api_key ?: config('services.retell.api_key');
$retellService = new RetellV2Service(is_encrypted($apiKey) ? decrypt($apiKey) : $apiKey);

// Get agent from API
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$remoteAgent = $retellService->getAgent($agentId);

if (!$remoteAgent || !isset($remoteAgent['llm_id'])) {
    echo "❌ Agent oder LLM ID nicht gefunden\n";
    exit(1);
}

echo "Agent LLM ID: {$remoteAgent['llm_id']}\n";

// Get LLM config with functions
$llmConfig = $retellService->getRetellLLM($remoteAgent['llm_id']);
if (!$llmConfig) {
    echo "❌ LLM Config nicht gefunden\n";
    exit(1);
}

echo "Functions gefunden: " . count($llmConfig['functions'] ?? []) . "\n\n";

// Update agent in DB
$agent = RetellAgent::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('agent_id', $agentId)
    ->first();

if ($agent) {
    // Merge configurations
    $config = is_string($agent->configuration) ? json_decode($agent->configuration, true) : $agent->configuration;
    $config['functions'] = $llmConfig['functions'] ?? [];
    $config['llm_id'] = $remoteAgent['llm_id'];
    $config['llm_config'] = $llmConfig;
    
    $agent->configuration = $config;
    $agent->save();
    
    echo "✅ Agent aktualisiert mit Functions:\n";
    foreach ($config['functions'] as $func) {
        echo "   - {$func['name']}\n";
    }
} else {
    echo "❌ Agent nicht in DB gefunden\n";
}

function is_encrypted($value) {
    return strpos($value, 'eyJpdiI6') === 0;
}