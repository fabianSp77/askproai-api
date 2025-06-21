<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Services\RetellV2Service;
use App\Services\MCP\RetellMCPServer;
use Illuminate\Support\Facades\Log;

echo "DETAILED MCP DEBUG\n";
echo str_repeat('=', 50) . "\n\n";

// Enable debug logging
Log::getLogger()->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));

$company = Company::find(1);

echo "1. Testing RetellV2Service directly...\n";
try {
    $service = new RetellV2Service(decrypt($company->retell_api_key));
    $result = $service->listAgents();
    echo "   ✅ Direct API call works! Found " . count($result['agents']) . " agents\n";
} catch (\Exception $e) {
    echo "   ❌ Direct API call failed: " . $e->getMessage() . "\n";
}

echo "\n2. Testing MCP getAgentsWithPhoneNumbers...\n";

// Create a custom MCP server with debug output
class DebugRetellMCPServer extends RetellMCPServer {
    public function getAgentsWithPhoneNumbers(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'company_id is required'];
        }
        
        echo "   - Company ID: $companyId\n";
        
        $cacheKey = $this->getCacheKey('agents_with_phones', ['company_id' => $companyId]);
        echo "   - Cache key: $cacheKey\n";
        
        // Skip cache for debugging
        echo "   - Skipping cache for debug\n";
        
        try {
            $company = Company::find($companyId);
            if (!$company || !$company->retell_api_key) {
                echo "   - Company not found or no API key\n";
                return ['error' => 'Company not found or Retell not configured'];
            }
            
            echo "   - Company found: {$company->name}\n";
            echo "   - API key exists: Yes\n";
            
            $retellService = new RetellV2Service(decrypt($company->retell_api_key));
            
            // Get agents
            echo "   - Calling listAgents()...\n";
            try {
                $agentsResponse = $retellService->listAgents();
                $agents = $agentsResponse['agents'] ?? [];
                echo "   - ✅ Got " . count($agents) . " agents\n";
            } catch (\Exception $e) {
                echo "   - ❌ listAgents failed: " . $e->getMessage() . "\n";
                throw $e;
            }
            
            // Get phone numbers
            echo "   - Calling listPhoneNumbers()...\n";
            try {
                $phoneResponse = $retellService->listPhoneNumbers();
                $phoneNumbers = $phoneResponse['phone_numbers'] ?? [];
                echo "   - ✅ Got " . count($phoneNumbers) . " phone numbers\n";
            } catch (\Exception $e) {
                echo "   - ⚠️ listPhoneNumbers failed: " . $e->getMessage() . "\n";
                $phoneNumbers = [];
            }
            
            // Map phone numbers to agents
            echo "   - Mapping phone numbers to agents...\n";
            foreach ($agents as &$agent) {
                $agent['phone_numbers'] = [];
                $agentPhoneIds = $agent['phone_number_ids'] ?? [];
                
                foreach ($phoneNumbers as $phone) {
                    if (in_array($phone['phone_number_id'], $agentPhoneIds)) {
                        $agent['phone_numbers'][] = $phone;
                    }
                }
            }
            
            echo "   - ✅ Successfully processed real data\n";
            
            return [
                'agents' => $agents,
                'total_agents' => count($agents),
                'total_phone_numbers' => count($phoneNumbers),
                'company' => $company->name,
                'is_mock' => false
            ];
            
        } catch (\Exception $e) {
            echo "   - ❌ EXCEPTION in outer try-catch: " . $e->getMessage() . "\n";
            echo "   - Stack trace:\n";
            echo $e->getTraceAsString() . "\n";
            
            // Return mock data when API is completely down
            return $this->getMockAgentsData($companyId);
        }
    }
}

$debugMcp = new DebugRetellMCPServer();
$result = $debugMcp->getAgentsWithPhoneNumbers(['company_id' => 1]);

echo "\n3. Result:\n";
if (isset($result['is_mock']) && $result['is_mock']) {
    echo "   ⚠️  Got mock data\n";
} else {
    echo "   ✅ Got real data!\n";
    echo "   - Agents: " . $result['total_agents'] . "\n";
    echo "   - Phone numbers: " . $result['total_phone_numbers'] . "\n";
}

echo "\n✅ DEBUG COMPLETE\n";