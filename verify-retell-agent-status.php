#!/usr/bin/env php
<?php
/**
 * Verify Retell Agent Status and Configuration
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellService;
use App\Models\Company;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\033[1;36m================================================================================\033[0m\n";
echo "\033[1;33m🔍 Retell Agent Status Verification\033[0m\n";
echo "\033[1;36m================================================================================\033[0m\n\n";

// Your specific agent ID
$AGENT_ID = 'agent_d7da9e5c49c4ccfff2526df5c1';

// Also check the default agent
$DEFAULT_AGENT_ID = env('DEFAULT_RETELL_AGENT_ID', 'agent_9a8202a740cd3120d96fcfda1e');

// Get API key
$apiKey = env('RETELL_API_KEY', 'key_6ff998ba48e842092e04a5455d19');

echo "📋 Checking Agent Configurations\n";
echo str_repeat('-', 80) . "\n\n";

$retellService = new RetellService($apiKey);

// Check your specific agent
echo "\033[1;35m1️⃣ Your Agent (from URL): $AGENT_ID\033[0m\n";
try {
    $agent = $retellService->getAgent($AGENT_ID);
    if ($agent) {
        echo "\033[0;32m✓ Agent found!\033[0m\n";
        echo "   Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
        echo "   Language: " . ($agent['language'] ?? 'N/A') . "\n";
        echo "   Voice ID: " . ($agent['voice_id'] ?? 'N/A') . "\n";
        echo "   Webhook URL: " . ($agent['webhook_url'] ?? 'N/A') . "\n";
        echo "   Last Updated: " . ($agent['last_modification'] ?? 'N/A') . "\n";
        
        // Check prompt
        $prompt = $agent['general_prompt'] ?? '';
        if (strpos($prompt, 'Friseursalon') !== false || strpos($prompt, 'Paula') !== false) {
            echo "\033[0;32m   ✓ Hair Salon prompt detected!\033[0m\n";
        } else {
            echo "\033[0;33m   ⚠ Original prompt still active\033[0m\n";
            echo "   First 200 chars: " . substr($prompt, 0, 200) . "...\n";
        }
    } else {
        echo "\033[0;31m✗ Agent not found!\033[0m\n";
    }
} catch (\Exception $e) {
    echo "\033[0;31m✗ Error accessing agent: " . $e->getMessage() . "\033[0m\n";
    
    // Check if it's an authentication issue
    if (strpos($e->getMessage(), '401') !== false || strpos($e->getMessage(), 'Unauthorized') !== false) {
        echo "\n\033[1;33m⚠️ Authentication Issue Detected!\033[0m\n";
        echo "This might mean:\n";
        echo "1. The API key doesn't have access to this specific agent\n";
        echo "2. The agent belongs to a different Retell account\n";
        echo "3. The agent ID is incorrect\n";
    }
}

// Check default agent
echo "\n\033[1;35m2️⃣ Default Agent (from .env): $DEFAULT_AGENT_ID\033[0m\n";
try {
    $defaultAgent = $retellService->getAgent($DEFAULT_AGENT_ID);
    if ($defaultAgent) {
        echo "\033[0;32m✓ Default agent found!\033[0m\n";
        echo "   Name: " . ($defaultAgent['agent_name'] ?? 'N/A') . "\n";
        echo "   Language: " . ($defaultAgent['language'] ?? 'N/A') . "\n";
    }
} catch (\Exception $e) {
    echo "\033[0;31m✗ Error: " . $e->getMessage() . "\033[0m\n";
}

// Check what agents are accessible with this API key
echo "\n\033[1;35m3️⃣ Listing All Accessible Agents\033[0m\n";
try {
    // Try to list agents (if API supports it)
    $ch = curl_init('https://api.retellai.com/list-agents');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $agents = json_decode($response, true);
        if (is_array($agents)) {
            echo "Found " . count($agents) . " agents:\n";
            foreach ($agents as $agent) {
                $agentId = $agent['agent_id'] ?? $agent['id'] ?? 'unknown';
                $agentName = $agent['agent_name'] ?? $agent['name'] ?? 'Unnamed';
                echo "   • $agentId: $agentName\n";
                
                if ($agentId == $AGENT_ID) {
                    echo "     \033[0;32m↑ This is your agent!\033[0m\n";
                }
            }
        }
    } else {
        echo "Could not list agents (HTTP $httpCode)\n";
    }
} catch (\Exception $e) {
    echo "Error listing agents: " . $e->getMessage() . "\n";
}

// Summary
echo "\n\033[1;36m================================================================================\033[0m\n";
echo "\033[1;33m📊 Analysis\033[0m\n";
echo "\033[1;36m================================================================================\033[0m\n\n";

echo "🔍 Possible Issues:\n";
echo "1. The agent ID might belong to a different Retell account\n";
echo "2. The API key in .env might not have access to your specific agent\n";
echo "3. The update might need a different API key (your account's key)\n\n";

echo "📝 Direct Dashboard Link:\n";
echo "\033[1;34mhttps://dashboard.retellai.com/agents/$AGENT_ID\033[0m\n\n";

echo "🔑 Current API Key (first/last 4 chars):\n";
$keyStart = substr($apiKey, 0, 4);
$keyEnd = substr($apiKey, -4);
echo "$keyStart..." . str_repeat('*', 20) . "...$keyEnd\n\n";

echo "💡 Next Steps:\n";
echo "1. Check if the agent ID is correct in the dashboard\n";
echo "2. Verify the API key belongs to the same account as the agent\n";
echo "3. You might need to use YOUR Retell account's API key\n";
echo "   (not the one in .env which might be for a different account)\n";

echo "\n\033[1;32m✅ Verification completed!\033[0m\n";