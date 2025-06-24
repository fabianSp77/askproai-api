<?php

use App\Services\MCP\MCPGateway;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Str;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing MCP Integration for Cal.com\n";
echo "===================================\n\n";

try {
    // Initialize MCP Gateway
    $mcpGateway = app(MCPGateway::class);
    
    // Test 1: Check MCP Gateway health
    echo "1. Testing MCP Gateway health...\n";
    $health = $mcpGateway->health();
    echo "Gateway Status: " . $health['gateway'] . "\n";
    echo "Available Servers: " . implode(', ', array_keys($health['servers'])) . "\n\n";
    
    // Test 2: List available methods
    echo "2. Listing available MCP methods...\n";
    $methods = $mcpGateway->listMethods();
    foreach ($methods as $server => $serverMethods) {
        echo "Server: $server\n";
        foreach ($serverMethods as $method) {
            echo "  - $method\n";
        }
    }
    echo "\n";
    
    // Test 3: Test Cal.com event types retrieval via MCP
    $company = Company::first();
    if ($company) {
        echo "3. Testing Cal.com event types retrieval via MCP...\n";
        $mcpRequest = [
            'jsonrpc' => '2.0',
            'method' => 'calcom.getEventTypes',
            'params' => [
                'company_id' => $company->id
            ],
            'id' => Str::uuid()->toString()
        ];
        
        $response = $mcpGateway->process($mcpRequest);
        
        if (isset($response['error'])) {
            echo "Error: " . $response['error']['message'] . "\n";
        } else {
            $result = $response['result'] ?? [];
            echo "Success! Found " . ($result['count'] ?? 0) . " event types\n";
            echo "Company: " . ($result['company'] ?? 'Unknown') . "\n";
        }
    } else {
        echo "3. No company found for testing\n";
    }
    
    // Test 4: Test availability check via MCP
    $branch = Branch::where('is_active', true)->whereNotNull('calcom_event_type_id')->first();
    if ($branch) {
        echo "\n4. Testing availability check via MCP...\n";
        $tomorrow = \Carbon\Carbon::tomorrow()->format('Y-m-d');
        
        $mcpRequest = [
            'jsonrpc' => '2.0',
            'method' => 'calcom.checkAvailability',
            'params' => [
                'event_type_id' => $branch->calcom_event_type_id,
                'date' => $tomorrow,
                'timezone' => 'Europe/Berlin'
            ],
            'id' => Str::uuid()->toString()
        ];
        
        $response = $mcpGateway->process($mcpRequest);
        
        if (isset($response['error'])) {
            echo "Error: " . $response['error']['message'] . "\n";
        } else {
            $result = $response['result'] ?? [];
            if ($result['success'] ?? false) {
                $slots = $result['data']['slots'] ?? [];
                echo "Success! Found " . count($slots) . " available slots for tomorrow\n";
                if (count($slots) > 0) {
                    echo "First 3 slots: " . implode(', ', array_slice($slots, 0, 3)) . "\n";
                }
            } else {
                echo "Availability check failed: " . ($result['error'] ?? 'Unknown error') . "\n";
            }
        }
    } else {
        echo "\n4. No active branch with Cal.com event type found for testing\n";
    }
    
    echo "\n✅ MCP Integration tests completed\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error during MCP integration test:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}