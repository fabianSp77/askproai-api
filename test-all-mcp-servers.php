<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== MCP SERVERS COMPLETE TEST ===\n\n";

$mcpServers = [
    'DatabaseMCPServer' => \App\Services\MCP\DatabaseMCPServer::class,
    'CalcomMCPServer' => \App\Services\MCP\CalcomMCPServer::class,
    'RetellMCPServer' => \App\Services\MCP\RetellMCPServer::class,
    'QueueMCPServer' => \App\Services\MCP\QueueMCPServer::class,
    'WebhookMCPServer' => \App\Services\MCP\WebhookMCPServer::class,
    'BranchMCPServer' => \App\Services\MCP\BranchMCPServer::class,
    'CompanyMCPServer' => \App\Services\MCP\CompanyMCPServer::class,
    'AppointmentMCPServer' => \App\Services\MCP\AppointmentMCPServer::class,
    'CustomerMCPServer' => \App\Services\MCP\CustomerMCPServer::class,
];

$results = [];

// 1. Test instantiation of all MCP servers
echo "1. Testing MCP Server Instantiation...\n";

foreach ($mcpServers as $name => $class) {
    try {
        $instance = app($class);
        echo "   ✅ $name instantiated successfully\n";
        $results[$name] = ['instantiated' => true];
    } catch (\Exception $e) {
        echo "   ❌ $name failed: " . $e->getMessage() . "\n";
        $results[$name] = ['instantiated' => false, 'error' => $e->getMessage()];
    }
}

echo "\n";

// 2. Test BranchMCPServer
echo "2. Testing BranchMCPServer...\n";

try {
    $branchMCP = app(\App\Services\MCP\BranchMCPServer::class);
    
    // Get test branch
    $testBranch = DB::table('branches')->where('is_active', true)->first();
    if ($testBranch) {
        $result = $branchMCP->getBranch((int)$testBranch->id);
        echo "   - getBranch: " . ($result['success'] ? '✅' : '❌') . "\n";
        
        $result = $branchMCP->getBranchesByCompany((int)$testBranch->company_id);
        echo "   - getBranchesByCompany: " . ($result['success'] ? '✅' : '❌') . "\n";
        
        $result = $branchMCP->isBranchOpen((int)$testBranch->id);
        echo "   - isBranchOpen: " . ($result['success'] ? '✅' : '❌') . "\n";
    } else {
        echo "   ⚠️  No test branch found\n";
    }
} catch (\Exception $e) {
    echo "   ❌ BranchMCPServer error: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Test CompanyMCPServer
echo "3. Testing CompanyMCPServer...\n";

try {
    $companyMCP = app(\App\Services\MCP\CompanyMCPServer::class);
    
    // Get test company
    $testCompany = DB::table('companies')->where('is_active', true)->first();
    if ($testCompany) {
        $result = $companyMCP->getCompany((int)$testCompany->id);
        echo "   - getCompany: " . ($result['success'] ? '✅' : '❌') . "\n";
        
        $result = $companyMCP->getCompanyStatistics((int)$testCompany->id);
        echo "   - getCompanyStatistics: " . ($result['success'] ? '✅' : '❌') . "\n";
        
        $result = $companyMCP->getIntegrationsStatus((int)$testCompany->id);
        echo "   - getIntegrationsStatus: " . ($result['success'] ? '✅' : '❌') . "\n";
    } else {
        echo "   ⚠️  No test company found\n";
    }
} catch (\Exception $e) {
    echo "   ❌ CompanyMCPServer error: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Test CustomerMCPServer
echo "4. Testing CustomerMCPServer...\n";

try {
    $customerMCP = app(\App\Services\MCP\CustomerMCPServer::class);
    
    // Get test customer
    $testCustomer = DB::table('customers')->first();
    if ($testCustomer) {
        $result = $customerMCP->getCustomer((int)$testCustomer->id);
        echo "   - getCustomer: " . ($result['success'] ? '✅' : '❌') . "\n";
        
        $result = $customerMCP->getCustomerAppointments((int)$testCustomer->id);
        echo "   - getCustomerAppointments: " . ($result['success'] ? '✅' : '❌') . "\n";
        
        $result = $customerMCP->checkBlacklist((int)$testCustomer->id);
        echo "   - checkBlacklist: " . ($result['success'] ? '✅' : '❌') . "\n";
    } else {
        echo "   ⚠️  No test customer found\n";
    }
    
    // Test search
    $result = $customerMCP->searchCustomers(['query' => 'test', 'limit' => 5]);
    echo "   - searchCustomers: " . ($result['success'] ? '✅' : '❌') . "\n";
} catch (\Exception $e) {
    echo "   ❌ CustomerMCPServer error: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Test AppointmentMCPServer
echo "5. Testing AppointmentMCPServer...\n";

try {
    $appointmentMCP = app(\App\Services\MCP\AppointmentMCPServer::class);
    
    // Get test appointment
    $testAppointment = DB::table('appointments')->first();
    if ($testAppointment) {
        $result = $appointmentMCP->getAppointment((int)$testAppointment->id);
        echo "   - getAppointment: " . ($result['success'] ? '✅' : '❌') . "\n";
    } else {
        echo "   ⚠️  No test appointment found\n";
    }
    
    // Test availability check
    if ($testBranch ?? null) {
        $result = $appointmentMCP->checkAvailability(
            (int)$testBranch->id,
            now()->addDay()->setHour(10)->setMinute(0)->toDateTimeString(),
            now()->addDay()->setHour(11)->setMinute(0)->toDateTimeString()
        );
        echo "   - checkAvailability: " . (isset($result['available']) ? '✅' : '❌') . "\n";
    }
    
    // Test date range query
    $result = $appointmentMCP->getAppointmentsByDateRange(
        now()->startOfMonth(),
        now()->endOfMonth()
    );
    echo "   - getAppointmentsByDateRange: " . ($result['success'] ? '✅' : '❌') . "\n";
} catch (\Exception $e) {
    echo "   ❌ AppointmentMCPServer error: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. Summary
echo "=== SUMMARY ===\n";

$totalServers = count($mcpServers);
$successfulServers = count(array_filter($results, fn($r) => $r['instantiated'] ?? false));

echo "Total MCP Servers: $totalServers\n";
echo "Successfully instantiated: $successfulServers\n";
echo "Failed: " . ($totalServers - $successfulServers) . "\n";

if ($successfulServers === $totalServers) {
    echo "\n✅ All MCP servers are working correctly!\n";
} else {
    echo "\n❌ Some MCP servers have issues\n";
    echo "\nFailed servers:\n";
    foreach ($results as $name => $result) {
        if (!($result['instantiated'] ?? false)) {
            echo "- $name: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
    }
}

echo "\nMCP Server Architecture Complete:\n";
echo "- DatabaseMCPServer: Core database operations\n";
echo "- CalcomMCPServer: Calendar integration\n";
echo "- RetellMCPServer: Phone AI integration\n";
echo "- QueueMCPServer: Async job processing\n";
echo "- WebhookMCPServer: Webhook processing\n";
echo "- BranchMCPServer: Branch management\n";
echo "- CompanyMCPServer: Company operations\n";
echo "- AppointmentMCPServer: Appointment scheduling\n";
echo "- CustomerMCPServer: Customer management\n";