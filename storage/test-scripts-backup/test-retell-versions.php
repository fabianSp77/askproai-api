<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\RetellAgent;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the first company with retell API key
$company = Company::whereNotNull('retell_api_key')->first();

if (!$company) {
    die("No company with Retell API key found\n");
}

echo "Investigating Retell Agent Versions\n";
echo str_repeat("=", 80) . "\n\n";

// Decrypt API key if needed
$apiKey = $company->retell_api_key;
if (strlen($apiKey) > 50) {
    try {
        $apiKey = decrypt($apiKey);
    } catch (\Exception $e) {
        // Use as-is if decryption fails
    }
}

echo "Company: {$company->name}\n";
echo "API Key: " . substr($apiKey, 0, 20) . "...\n\n";

// Test direct API call to avoid service layer issues
echo "1. Testing direct API call to Retell...\n";

try {
    $response = Http::withToken($apiKey)
        ->timeout(30)
        ->get('https://api.retellai.com/list-agents');
    
    if ($response->successful()) {
        $data = $response->json();
        $agents = is_array($data) && !isset($data['agents']) ? $data : ($data['agents'] ?? []);
        
        echo "   SUCCESS: Found " . count($agents) . " agents\n\n";
        
        // Look for all agents with "Fabian" in the name
        echo "2. Searching for agents with 'Fabian' in the name...\n";
        $fabianAgents = [];
        
        foreach ($agents as $agent) {
            $name = $agent['agent_name'] ?? '';
            if (stripos($name, 'Fabian') !== false) {
                $fabianAgents[] = $agent;
            }
        }
        
        echo "   Found " . count($fabianAgents) . " agents with 'Fabian'\n\n";
        
        if (count($fabianAgents) > 0) {
            echo "3. Detailed analysis of Fabian agents:\n\n";
            
            foreach ($fabianAgents as $idx => $agent) {
                echo "   Agent #" . ($idx + 1) . ":\n";
                echo "   - ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
                echo "   - Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
                echo "   - Status: " . ($agent['status'] ?? 'N/A') . "\n";
                echo "   - Created: " . ($agent['created_at'] ?? 'N/A') . "\n";
                echo "   - Updated: " . ($agent['last_modification_timestamp'] ?? $agent['updated_at'] ?? 'N/A') . "\n";
                
                // Parse version
                $name = $agent['agent_name'] ?? '';
                if (preg_match('/V(\d+)/', $name, $matches)) {
                    echo "   - Version: V" . $matches[1] . "\n";
                } else {
                    echo "   - Version: Not found in name\n";
                }
                
                // Get base name
                $baseName = preg_replace('/[\s\/]*V\d+\s*$/', '', $name);
                $baseName = trim(str_replace('Online: ', '', $baseName));
                echo "   - Base Name: \"$baseName\"\n";
                
                echo "   ---\n\n";
            }
            
            // Group by base name
            echo "4. Grouping analysis:\n\n";
            $groups = [];
            
            foreach ($fabianAgents as $agent) {
                $name = $agent['agent_name'] ?? '';
                $baseName = preg_replace('/[\s\/]*V\d+\s*$/', '', $name);
                $baseName = trim(str_replace('Online: ', '', $baseName));
                
                if (!isset($groups[$baseName])) {
                    $groups[$baseName] = [];
                }
                $groups[$baseName][] = $agent;
            }
            
            foreach ($groups as $baseName => $agents) {
                echo "   Group: \"$baseName\"\n";
                echo "   - Count: " . count($agents) . " version(s)\n";
                
                foreach ($agents as $agent) {
                    $version = 'V1';
                    if (preg_match('/V(\d+)/', $agent['agent_name'] ?? '', $matches)) {
                        $version = 'V' . $matches[1];
                    }
                    echo "     * $version - {$agent['agent_id']} ({$agent['status']})\n";
                }
                echo "\n";
            }
        }
        
        // Check all agent names for versioning patterns
        echo "5. All agents with version patterns:\n\n";
        $versionedAgents = [];
        
        foreach ($agents as $agent) {
            $name = $agent['agent_name'] ?? '';
            if (preg_match('/V\d+/', $name)) {
                $versionedAgents[] = [
                    'name' => $name,
                    'id' => $agent['agent_id'] ?? '',
                    'status' => $agent['status'] ?? ''
                ];
            }
        }
        
        echo "   Found " . count($versionedAgents) . " agents with version patterns\n";
        if (count($versionedAgents) > 0 && count($versionedAgents) <= 20) {
            foreach ($versionedAgents as $va) {
                echo "   - {$va['name']} ({$va['id']})\n";
            }
        }
        
    } else {
        echo "   FAILED: HTTP " . $response->status() . "\n";
        echo "   Body: " . substr($response->body(), 0, 500) . "\n";
    }
    
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Check local database
echo "\n\n6. Checking local database sync status...\n";

$localFabianAgent = RetellAgent::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->where('name', 'like', '%Fabian%')
    ->first();

if ($localFabianAgent) {
    echo "   Local agent found:\n";
    echo "   - Name: {$localFabianAgent->name}\n";
    echo "   - Agent ID: {$localFabianAgent->agent_id}\n";
    echo "   - Last Synced: " . ($localFabianAgent->last_synced_at ? $localFabianAgent->last_synced_at->format('Y-m-d H:i:s') : 'Never') . "\n";
    echo "   - Sync Status: {$localFabianAgent->sync_status}\n";
    
    // Check if configuration matches what we see in API
    $config = $localFabianAgent->configuration ?? [];
    if (isset($config['agent_name'])) {
        echo "   - Config Name: {$config['agent_name']}\n";
    }
}

echo "\n\nDone.\n";