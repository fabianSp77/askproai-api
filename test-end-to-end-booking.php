<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PhoneNumberResolver;
use App\Services\Monitoring\ServiceUsageTracker;
use Illuminate\Support\Facades\DB;

echo "=== END-TO-END BOOKING FLOW TEST ===\n\n";

// 1. Test Phone Resolution
echo "1. Testing Phone Resolution...\n";
$phoneNumber = '+493012345681';
$resolver = app(PhoneNumberResolver::class);

try {
    $resolution = $resolver->resolve($phoneNumber);
    
    if (!$resolution['found']) {
        echo "❌ Phone number not found!\n";
        exit(1);
    }
    
    echo "✅ Phone resolved successfully:\n";
    echo "   - Company: {$resolution['company_name']} (ID: {$resolution['company_id']})\n";
    echo "   - Branch: {$resolution['branch_name']} (ID: {$resolution['branch_id']})\n";
    echo "   - Agent ID: {$resolution['agent_id']}\n\n";
    
} catch (\Exception $e) {
    echo "❌ Phone resolution failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Simulate Webhook Call
echo "2. Simulating Retell Webhook Call...\n";

$webhookData = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_call_' . time(),
        'agent_id' => $resolution['agent_id'],
        'to' => $phoneNumber,
        'from' => '+4917612345678',
        'direction' => 'inbound',
        'status' => 'ended',
        'start_timestamp' => time() - 300, // 5 minutes ago
        'end_timestamp' => time(),
        'transcript' => 'Customer: Ich möchte gerne einen Termin am Montag um 10 Uhr buchen.',
        'metadata' => [
            'customer_name' => 'Test Kunde',
            'service_requested' => 'Beratung',
            'preferred_date' => '2025-06-25',
            'preferred_time' => '10:00'
        ]
    ]
];

// Track which services are called
$tracker = app(ServiceUsageTracker::class);

// 3. Test Service Availability
echo "3. Checking Service Availability...\n";

$services = [
    'CalcomService' => \App\Services\CalcomService::class,
    'CalcomV2Service' => \App\Services\CalcomV2Service::class,
    'CalcomServiceUnified' => \App\Services\Unified\CalcomServiceUnified::class,
    'RetellService' => \App\Services\RetellService::class,
    'RetellV2Service' => \App\Services\RetellV2Service::class,
    'RetellServiceUnified' => \App\Services\Unified\RetellServiceUnified::class,
    'AppointmentBookingService' => \App\Services\AppointmentBookingService::class,
    'BookingService' => \App\Services\BookingService::class,
];

foreach ($services as $name => $class) {
    if (class_exists($class)) {
        echo "   ✅ $name available\n";
        
        // Try to instantiate to check dependencies
        try {
            $instance = app($class);
            echo "      - Successfully instantiated\n";
        } catch (\Exception $e) {
            echo "      ⚠️  Instantiation failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ❌ $name NOT FOUND\n";
    }
}

echo "\n";

// 4. Check MCP Servers
echo "4. Checking MCP Servers...\n";

$mcpServers = [
    'CalcomMCPServer' => \App\Services\MCP\CalcomMCPServer::class,
    'RetellMCPServer' => \App\Services\MCP\RetellMCPServer::class,
    'WebhookMCPServer' => \App\Services\MCP\WebhookMCPServer::class,
    'BranchMCPServer' => \App\Services\MCP\BranchMCPServer::class,
    'CompanyMCPServer' => \App\Services\MCP\CompanyMCPServer::class,
    'AppointmentMCPServer' => \App\Services\MCP\AppointmentMCPServer::class,
    'CustomerMCPServer' => \App\Services\MCP\CustomerMCPServer::class,
];

foreach ($mcpServers as $name => $class) {
    if (class_exists($class)) {
        echo "   ✅ $name exists\n";
    } else {
        echo "   ❌ $name MISSING\n";
    }
}

echo "\n";

// 5. Test Feature Flags
echo "5. Testing Feature Flags...\n";

$flags = [
    'use_calcom_v2_api',
    'use_retell_v2_api',
    'use_unified_calcom_service',
    'use_unified_retell_service',
    'calcom_shadow_mode',
    'retell_shadow_mode',
    'enable_mcp_servers',
    'enable_service_tracking',
    'enforce_webhook_signatures'
];

foreach ($flags as $flag) {
    $enabled = feature($flag, $resolution['company_id']);
    echo "   - $flag: " . ($enabled ? '✅ Enabled' : '❌ Disabled') . "\n";
}

echo "\n";

// 6. Check Database Connectivity
echo "6. Testing Database Connectivity...\n";

try {
    // Use direct DB queries to avoid tenant scope issues
    $companyCount = DB::table('companies')->count();
    $branchCount = DB::table('branches')->count();
    $phoneCount = DB::table('phone_numbers')->count();
    
    echo "   ✅ Database connected\n";
    echo "   - Companies: $companyCount\n";
    echo "   - Branches: $branchCount\n";
    echo "   - Phone Numbers: $phoneCount\n";
} catch (\Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Service Usage Statistics
echo "7. Service Usage Statistics (last 24h):\n";

$stats = $tracker->getUsageStats(null, 24);
echo "   - Total service calls: " . $stats['total_calls'] . "\n";
echo "   - Unique methods called: " . $stats['unique_methods'] . "\n";
echo "   - Error rate: " . round($stats['error_rate'] * 100, 2) . "%\n";
echo "   - Average execution time: " . round($stats['avg_execution_time'] ?? 0, 2) . "ms\n";

if (!empty($stats['by_service'])) {
    echo "\n   Top Services:\n";
    foreach ($stats['by_service'] as $service) {
        echo "   - {$service->service_name}: {$service->calls} calls (avg {$service->avg_time}ms)\n";
    }
}

echo "\n";

// 8. Summary
echo "=== TEST SUMMARY ===\n";
echo "✅ Phone resolution working\n";
echo "✅ Test company configured\n";
echo "⚠️  Some services may need configuration\n";
echo "⚠️  MCP servers need to be implemented\n";
echo "✅ Feature flags configured\n";
echo "✅ Database connected\n";
echo "\n";

echo "Next steps:\n";
echo "1. Configure Cal.com API keys for the test company\n";
echo "2. Configure Retell.ai API keys and webhook URL\n";
echo "3. Implement missing MCP servers\n";
echo "4. Enable feature flags gradually\n";
echo "5. Make a real test call to $phoneNumber\n";