<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\Branch;
use App\Services\RetellService;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('phone_number', '+493083793369')
    ->first();

if (!$branch) {
    echo "Branch not found\n";
    exit(1);
}

$company = $branch->company;

if (!$company) {
    echo "Company not found\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Agent ID: {$company->retell_agent_id}\n";
echo "API Key exists: " . (!empty($company->retell_api_key) ? 'Yes' : 'No') . "\n\n";

$service = new RetellService($company->retell_api_key);

echo "Fetching all agents...\n";
$agents = $service->getAgents();

if (is_array($agents)) {
    echo "Found " . count($agents) . " agents:\n";
    foreach ($agents as $ag) {
        echo "- ID: " . $ag['agent_id'] . " | Name: " . $ag['agent_name'] . " | Version: V" . $ag['version'] . "\n";
    }
    echo "\n";
}

$agent = $service->getAgent($company->retell_agent_id);

if (!$agent) {
    echo "Agent with ID {$company->retell_agent_id} not found\n";
    
    // Try to find the agent from the list - get the latest version (V30)
    if (is_array($agents)) {
        $latestVersion = -1;
        $latestAgent = null;
        
        foreach ($agents as $ag) {
            if ($ag['agent_id'] === $company->retell_agent_id) {
                $version = (int) $ag['version'];
                if ($version > $latestVersion) {
                    $latestVersion = $version;
                    $latestAgent = $ag;
                }
            }
        }
        
        if ($latestAgent) {
            echo "Found agent in list! Using version V{$latestVersion}...\n";
            $agent = $latestAgent;
        }
    }
    
    if (!$agent) {
        exit(1);
    }
}

echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
echo "Version: V" . ($agent['version'] ?? 'N/A') . "\n\n";

echo "Current Prompt (full):\n";
echo str_repeat('=', 80) . "\n";
echo ($agent['llm_instructions'] ?? 'No prompt found') . "\n";
echo str_repeat('=', 80) . "\n\n";

echo "LLM Configuration:\n";
echo "- Model: " . ($agent['llm_config']['model'] ?? 'N/A') . "\n";
echo "- Temperature: " . ($agent['llm_config']['temperature'] ?? 'N/A') . "\n\n";

if (isset($agent['general_tools']) && !empty($agent['general_tools'])) {
    echo "Custom Functions:\n";
    foreach($agent['general_tools'] as $tool) {
        echo "- {$tool['name']}\n";
        if (isset($tool['description'])) {
            echo "  Description: {$tool['description']}\n";
        }
    }
} else {
    echo "No custom functions configured\n";
}

echo "\n\nDynamic Variables Available:\n";
echo "- caller_phone_number\n";
echo "- current_time_berlin\n";
echo "- current_date\n";
echo "- current_time\n";
echo "- weekday\n";