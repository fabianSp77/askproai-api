<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\RetellAgent;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the first company with retell API key
$company = Company::whereNotNull('retell_api_key')->first();

if (!$company) {
    die("No company with Retell API key found\n");
}

echo "Testing Agent Versions for Company: {$company->name}\n";
echo str_repeat("=", 80) . "\n\n";

// Initialize Retell service
$retellService = new RetellV2Service($company->retell_api_key);

try {
    // 1. Get all agents from Retell API
    echo "1. Fetching agents from Retell API...\n";
    $result = $retellService->listAgents();
    $agents = $result['agents'] ?? [];
    
    echo "   Found " . count($agents) . " agents in Retell\n\n";
    
    // 2. Find agents with "Fabian Spitzer" in the name
    echo "2. Looking for 'Fabian Spitzer' agents...\n";
    $fabianAgents = array_filter($agents, function($agent) {
        return stripos($agent['agent_name'] ?? '', 'Fabian Spitzer') !== false;
    });
    
    if (empty($fabianAgents)) {
        echo "   No agents found with 'Fabian Spitzer' in the name\n";
    } else {
        echo "   Found " . count($fabianAgents) . " agents with 'Fabian Spitzer':\n\n";
        
        foreach ($fabianAgents as $agent) {
            echo "   Agent ID: " . $agent['agent_id'] . "\n";
            echo "   Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
            echo "   Status: " . ($agent['status'] ?? 'N/A') . "\n";
            echo "   Created: " . ($agent['created_at'] ?? 'N/A') . "\n";
            echo "   Updated: " . ($agent['updated_at'] ?? 'N/A') . "\n";
            
            // Check if this is a versioned name
            if (preg_match('/V(\d+)/', $agent['agent_name'] ?? '', $matches)) {
                echo "   Version Found: V" . $matches[1] . "\n";
            }
            
            echo "   ---\n";
        }
    }
    
    // 3. Analyze how agents are being parsed in the control center
    echo "\n3. Analyzing agent name parsing logic...\n";
    
    // Helper functions from RetellUltimateControlCenter
    function parseAgentName($name) {
        // Remove version suffix (V1, V2, etc.) if present
        $cleanName = preg_replace('/\s*V\d+\s*$/', '', $name);
        return trim($cleanName);
    }
    
    function extractVersion($name) {
        // Extract version number from agent name
        if (preg_match('/V(\d+)\s*$/', $name, $matches)) {
            return 'V' . $matches[1];
        }
        return 'V1';
    }
    
    function getBaseName($name) {
        return parseAgentName($name);
    }
    
    // Test the parsing logic
    echo "\n   Testing name parsing:\n";
    $testNames = [
        'Assistent für Fabian Spitzer Rechtliches V33',
        'Assistent für Fabian Spitzer Rechtliches V1',
        'Assistent für Fabian Spitzer Rechtliches',
        'Test Agent V2',
        'Simple Agent'
    ];
    
    foreach ($testNames as $testName) {
        echo "   Input: '$testName'\n";
        echo "   Base Name: '" . getBaseName($testName) . "'\n";
        echo "   Version: '" . extractVersion($testName) . "'\n";
        echo "   ---\n";
    }
    
    // 4. Group all agents by base name to see how they're grouped
    echo "\n4. Grouping all agents by base name...\n";
    $agentGroups = [];
    
    foreach ($agents as $agent) {
        $baseName = getBaseName($agent['agent_name'] ?? '');
        $version = extractVersion($agent['agent_name'] ?? '');
        
        if (!isset($agentGroups[$baseName])) {
            $agentGroups[$baseName] = [];
        }
        
        $agentGroups[$baseName][] = [
            'agent_id' => $agent['agent_id'],
            'agent_name' => $agent['agent_name'] ?? '',
            'version' => $version,
            'status' => $agent['status'] ?? 'inactive',
            'is_active' => ($agent['status'] ?? 'inactive') === 'active'
        ];
    }
    
    // Sort groups and show those with multiple versions
    echo "\n   Agent groups with multiple versions:\n";
    foreach ($agentGroups as $baseName => $versions) {
        if (count($versions) > 1) {
            echo "\n   Base Name: '$baseName'\n";
            echo "   Versions: " . count($versions) . "\n";
            
            // Sort by version number
            usort($versions, function($a, $b) {
                $aNum = (int) str_replace('V', '', $a['version']);
                $bNum = (int) str_replace('V', '', $b['version']);
                return $bNum - $aNum; // Descending order
            });
            
            foreach ($versions as $v) {
                echo "     - {$v['version']} ({$v['agent_id']}) - Status: {$v['status']}\n";
            }
        }
    }
    
    // 5. Check local database for the Fabian Spitzer agent
    echo "\n\n5. Checking local database for Fabian Spitzer agents...\n";
    $localAgents = RetellAgent::where('company_id', $company->id)
        ->where('name', 'like', '%Fabian Spitzer%')
        ->get();
    
    echo "   Found " . $localAgents->count() . " agents in local database\n";
    
    foreach ($localAgents as $agent) {
        echo "\n   Local Agent:\n";
        echo "   - ID: {$agent->id}\n";
        echo "   - Agent ID: {$agent->agent_id}\n";
        echo "   - Name: {$agent->name}\n";
        echo "   - Active: " . ($agent->is_active ? 'Yes' : 'No') . "\n";
        echo "   - Last Synced: " . ($agent->last_synced_at ?? 'Never') . "\n";
        echo "   - Sync Status: " . ($agent->sync_status ?? 'unknown') . "\n";
    }
    
    // 6. Get detailed info for a specific agent if found
    if (!empty($fabianAgents)) {
        $firstAgent = reset($fabianAgents);
        echo "\n\n6. Getting detailed info for agent: {$firstAgent['agent_id']}...\n";
        
        try {
            $agentDetails = $retellService->getAgent($firstAgent['agent_id']);
            
            if ($agentDetails) {
                echo "   Agent Details:\n";
                echo "   - Name: " . ($agentDetails['agent_name'] ?? 'N/A') . "\n";
                echo "   - Voice: " . ($agentDetails['voice_id'] ?? 'N/A') . "\n";
                echo "   - Language: " . ($agentDetails['language'] ?? 'N/A') . "\n";
                echo "   - Response Engine: " . ($agentDetails['response_engine']['type'] ?? 'N/A') . "\n";
                
                if (isset($agentDetails['response_engine']['llm_id'])) {
                    echo "   - LLM ID: " . $agentDetails['response_engine']['llm_id'] . "\n";
                }
            }
        } catch (\Exception $e) {
            echo "   Error getting agent details: " . $e->getMessage() . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n\nDone.\n";