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

echo "Fetching updated agent configuration...\n";
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $company->retell_api_key,
])->get("https://api.retellai.com/get-agent/{$agentId}");

if ($response->successful()) {
    $agent = $response->json();
    
    echo "\n✅ Agent Name: " . ($agent['agent_name'] ?? 'Unknown') . "\n";
    echo "✅ Version: " . ($agent['version_id'] ?? 'Unknown') . "\n";
    
    // Check if prompt contains the new instruction
    $prompt = $agent['llm_configuration']['general_prompt'] ?? '';
    if (strpos($prompt, 'NIEMALS nach der Telefonnummer fragen') !== false) {
        echo "✅ Prompt contains phone number instruction\n";
    } else {
        echo "❌ Prompt missing phone number instruction\n";
    }
    
    // Check custom functions
    $functions = $agent['llm_configuration']['general_tools'] ?? [];
    echo "\n📋 Custom Functions (" . count($functions) . " total):\n";
    
    foreach ($functions as $func) {
        $hasCallId = false;
        if (isset($func['properties']['parameters']['properties']['call_id'])) {
            $hasCallId = true;
        }
        echo "- " . $func['name'] . ($hasCallId ? " ✅ (has call_id)" : " ❌ (missing call_id)") . "\n";
    }
    
} else {
    echo "Error fetching agent: " . $response->body() . "\n";
}