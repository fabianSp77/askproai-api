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

// Get the agent details
$agentResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $company->retell_api_key,
])->get("https://api.retellai.com/get-agent/{$agentId}");

if (!$agentResponse->successful()) {
    die("Error fetching agent: " . $agentResponse->body() . "\n");
}

$agent = $agentResponse->json();

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

// Export the current configuration for analysis
$timestamp = date('Y-m-d-His');
$filename = "retell-llm-config-{$timestamp}.json";
file_put_contents($filename, json_encode($llmConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "LLM configuration exported to: {$filename}\n\n";

// Analyze the current functions
echo "Current custom functions:\n";
foreach ($llmConfig['general_tools'] as $index => $tool) {
    echo "\n[$index] " . $tool['name'] . " (type: " . $tool['type'] . ")\n";
    
    // Show structure
    echo "  Structure:\n";
    foreach ($tool as $key => $value) {
        if ($key === 'parameters' && is_array($value)) {
            echo "    - $key:\n";
            if (isset($value['properties'])) {
                echo "      - properties: " . implode(', ', array_keys($value['properties'])) . "\n";
            }
            if (isset($value['required'])) {
                echo "      - required: " . implode(', ', $value['required']) . "\n";
            }
        } else if (!is_array($value)) {
            echo "    - $key: " . (is_string($value) ? substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') : json_encode($value)) . "\n";
        }
    }
}

echo "\nDone!\n";