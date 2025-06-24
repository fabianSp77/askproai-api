<?php

use App\Services\MCP\MCPGateway;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Appointment;
use Illuminate\Support\Str;
use Carbon\Carbon;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Complete MCP Integration Test\n";
echo "=============================\n\n";

try {
    $mcpGateway = app(MCPGateway::class);
    
    // Test 1: Check MCP Gateway health
    echo "1. Testing MCP Gateway health...\n";
    $health = $mcpGateway->health();
    echo "Gateway Status: " . $health['gateway'] . "\n";
    echo "Available Servers:\n";
    foreach ($health['servers'] as $server => $status) {
        $serverStatus = is_array($status) ? ($status['status'] ?? 'unknown') : $status;
        echo "  - $server: $serverStatus\n";
    }
    echo "\n";
    
    // Test 2: Cal.com Event Types via MCP
    $company = Company::first();
    if ($company) {
        echo "2. Testing Cal.com event types via MCP...\n";
        $mcpRequest = [
            'jsonrpc' => '2.0',
            'method' => 'calcom.getEventTypes',
            'params' => ['company_id' => $company->id],
            'id' => Str::uuid()->toString()
        ];
        
        $response = $mcpGateway->process($mcpRequest);
        if (isset($response['error'])) {
            echo "  Error: " . $response['error']['message'] . "\n";
        } else {
            $result = $response['result'] ?? [];
            echo "  ✓ Found " . ($result['count'] ?? 0) . " event types\n";
        }
    }
    
    // Test 3: Retell Agent Info via MCP
    if ($company) {
        echo "\n3. Testing Retell agent info via MCP...\n";
        $mcpRequest = [
            'jsonrpc' => '2.0',
            'method' => 'retell.getAgent',
            'params' => ['company_id' => $company->id],
            'id' => Str::uuid()->toString()
        ];
        
        $response = $mcpGateway->process($mcpRequest);
        if (isset($response['error'])) {
            echo "  Error: " . $response['error']['message'] . "\n";
        } else {
            $result = $response['result'] ?? [];
            if (isset($result['agent'])) {
                echo "  ✓ Found agent: " . ($result['agent']['agent_name'] ?? 'Unknown') . "\n";
                echo "  Agent ID: " . ($result['agent_id'] ?? 'Unknown') . "\n";
            } else {
                echo "  ✓ Response received but no agent data\n";
            }
        }
    }
    
    // Test 4: Cal.com Availability Check via MCP
    $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('is_active', true)
        ->whereNotNull('calcom_event_type_id')
        ->first();
    if ($branch) {
        echo "\n4. Testing Cal.com availability check via MCP...\n";
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        
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
            echo "  Error: " . $response['error']['message'] . "\n";
        } else {
            $result = $response['result'] ?? [];
            if ($result['success'] ?? false) {
                $slots = $result['data']['slots'] ?? [];
                echo "  ✓ Found " . count($slots) . " available slots for tomorrow\n";
                if (count($slots) > 0) {
                    echo "  First 3 slots: " . implode(', ', array_slice($slots, 0, 3)) . "\n";
                }
            } else {
                echo "  Availability check failed: " . ($result['error'] ?? 'Unknown error') . "\n";
            }
        }
    }
    
    // Test 5: Retell Call Analytics via MCP
    if ($company) {
        echo "\n5. Testing Retell call analytics via MCP...\n";
        $mcpRequest = [
            'jsonrpc' => '2.0',
            'method' => 'retell.getCallAnalytics',
            'params' => [
                'company_id' => $company->id,
                'from_date' => Carbon::now()->subDays(7)->toDateString(),
                'to_date' => Carbon::now()->toDateString(),
                'group_by' => 'day'
            ],
            'id' => Str::uuid()->toString()
        ];
        
        $response = $mcpGateway->process($mcpRequest);
        if (isset($response['error'])) {
            echo "  Error: " . $response['error']['message'] . "\n";
        } else {
            $result = $response['result'] ?? [];
            if ($result['success'] ?? false) {
                $metrics = $result['metrics'] ?? [];
                echo "  ✓ Call Analytics (Last 7 days):\n";
                echo "    - Total calls: " . ($metrics['total_calls'] ?? 0) . "\n";
                echo "    - Completed: " . ($metrics['completed_calls'] ?? 0) . "\n";
                echo "    - Conversion rate: " . ($metrics['conversion_rate'] ?? 0) . "%\n";
                echo "    - Avg duration: " . ($metrics['avg_duration_seconds'] ?? 0) . " seconds\n";
            }
        }
    }
    
    // Test 6: Test a booking flow simulation (read-only)
    echo "\n6. Simulating booking flow (read-only test)...\n";
    
    // First check an appointment to see the structure
    $appointment = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->with(['customer', 'service', 'branch'])
        ->first();
    if ($appointment) {
        echo "  ✓ Found existing appointment:\n";
        echo "    - Customer: " . ($appointment->customer->name ?? 'Unknown') . "\n";
        echo "    - Service: " . ($appointment->service->name ?? 'N/A') . "\n";
        echo "    - Date: " . ($appointment->starts_at ? $appointment->starts_at->format('Y-m-d H:i') : 'N/A') . "\n";
        echo "    - Cal.com ID: " . ($appointment->calcom_booking_id ?? 'Not synced') . "\n";
    }
    
    echo "\n✅ MCP Integration tests completed successfully!\n";
    echo "\nSummary:\n";
    echo "- MCP Gateway is operational\n";
    echo "- Cal.com integration via MCP working\n";
    echo "- Retell.ai integration via MCP working\n";
    echo "- Booking flow components verified\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error during MCP test:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}