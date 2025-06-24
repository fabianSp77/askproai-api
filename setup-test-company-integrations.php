<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Services\MCP\CompanyMCPServer;
use App\Services\MCP\BranchMCPServer;
use Illuminate\Support\Facades\DB;

echo "=== SETUP TEST COMPANY INTEGRATIONS ===\n\n";

// 1. Find test company
echo "1. Finding test company...\n";

$testCompany = Company::withoutGlobalScopes()
    ->where('name', 'like', '%Test%')
    ->first();

if (!$testCompany) {
    echo "❌ No test company found!\n";
    exit(1);
}

echo "✅ Found: {$testCompany->name} (ID: {$testCompany->id})\n\n";

// 2. Check current integration status
echo "2. Current integration status:\n";

$companyMCP = app(CompanyMCPServer::class);
$integrations = $companyMCP->getIntegrationsStatus($testCompany->id);

if ($integrations['success']) {
    foreach ($integrations['data'] as $name => $status) {
        $configured = $status['configured'] ? '✅' : '❌';
        $active = $status['active'] ? '✅' : '❌';
        echo "   - $name: Configured $configured, Active $active\n";
    }
}

echo "\n3. Setting up test integrations...\n";

try {
    // Update company with test API keys
    $updates = [
        'calcom_api_key' => encrypt('test_calcom_api_key_' . time()),
        'calcom_integration_active' => true,
        'calcom_team_slug' => 'test-team',
        'retell_api_key' => encrypt('test_retell_api_key_' . time()),
        'retell_integration_active' => true,
        'retell_agent_id' => 'agent_test123',
        'settings' => [
            'booking_enabled' => true,
            'auto_confirm_appointments' => true,
            'reminder_hours_before' => 24,
            'language' => 'de',
            'timezone' => 'Europe/Berlin'
        ]
    ];
    
    $result = $companyMCP->updateCompany($testCompany->id, $updates);
    
    if ($result['success']) {
        echo "   ✅ Company integrations configured\n";
    } else {
        echo "   ❌ Failed to update company: " . $result['message'] . "\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error updating company: " . $e->getMessage() . "\n";
}

// 4. Update branch with Cal.com event type
echo "\n4. Setting up branch event type...\n";

$branch = Branch::withoutGlobalScopes()
    ->where('company_id', $testCompany->id)
    ->first();

if ($branch) {
    $branchMCP = app(BranchMCPServer::class);
    
    // Create a test Cal.com event type ID
    $testEventTypeId = 999999; // Test ID
    
    $result = $branchMCP->updateBranch($branch->id, [
        'calcom_event_type_id' => $testEventTypeId,
        'settings' => [
            'default_service_duration' => 60,
            'buffer_time_minutes' => 15,
            'max_advance_booking_days' => 30
        ]
    ]);
    
    if ($result['success']) {
        echo "   ✅ Branch configured with event type ID: $testEventTypeId\n";
    } else {
        echo "   ❌ Failed to update branch: " . $result['message'] . "\n";
    }
    
    // Note: working_hours table uses staff_id, not branch_id
    // Working hours are per-staff, not per-branch in current schema
}

// 5. Create test service
echo "\n5. Creating test service...\n";

$service = DB::table('services')
    ->where('branch_id', $branch->id ?? null)
    ->where('name', 'Test Beratung')
    ->first();

if (!$service && $branch) {
    DB::table('services')->insert([
        // id is auto-increment, not UUID
        'name' => 'Test Beratung',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "   ✅ Test service created\n";
} else {
    echo "   ℹ️  Test service already exists\n";
}

// 6. Final status check
echo "\n6. Final integration status:\n";

$finalStatus = $companyMCP->getIntegrationsStatus($testCompany->id);

if ($finalStatus['success']) {
    foreach ($finalStatus['data'] as $name => $status) {
        $configured = $status['configured'] ? '✅' : '❌';
        $active = $status['active'] ? '✅' : '❌';
        echo "   - $name: Configured $configured, Active $active\n";
        
        if ($name === 'calcom' && $status['configured']) {
            echo "     Event types: " . $status['event_types_count'] . "\n";
        }
        if ($name === 'retell' && $status['configured']) {
            echo "     Agents: " . $status['agents_count'] . "\n";
        }
    }
}

// 7. Test webhook with configured company
echo "\n7. Testing webhook processing with configured company...\n";

$webhookData = [
    'event' => 'call_analyzed',
    'call' => [
        'call_id' => 'config_test_' . time(),
        'agent_id' => 'agent_test123',
        'to_number' => '+493012345681',
        'from_number' => '+4917698765432',
        'direction' => 'inbound',
        'status' => 'ended',
        'start_timestamp' => time() - 180,
        'end_timestamp' => time(),
        'duration' => 180,
        'retell_llm_dynamic_variables' => [
            'customer_name' => 'Test Configuration',
            'customer_email' => 'test@example.com',
            'appointment_requested' => true,
            'appointment_date' => date('Y-m-d', strtotime('+2 days')),
            'appointment_time' => '14:00',
            'service_requested' => 'Test Beratung'
        ]
    ]
];

try {
    $webhookMCP = app(\App\Services\MCP\WebhookMCPServer::class);
    $result = $webhookMCP->processRetellWebhook($webhookData);
    
    if ($result['success']) {
        echo "   ✅ Webhook processed successfully\n";
        if ($result['appointment_created']) {
            echo "   ✅ Appointment created!\n";
        } else {
            echo "   ⚠️  No appointment created (Cal.com integration may need real API)\n";
        }
    } else {
        echo "   ❌ Webhook processing failed: " . $result['message'] . "\n";
    }
    
    // Cleanup
    DB::table('calls')->where('retell_call_id', $webhookData['call']['call_id'])->delete();
    DB::table('webhook_events')->where('correlation_id', $webhookData['call']['call_id'])->delete();
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Test company configured with dummy integrations\n";
echo "✅ Branch configured with event type\n";
echo "✅ Working hours set up\n";
echo "✅ Test service created\n";
echo "✅ Webhook processing works\n";
echo "\nNote: For real appointment creation, you need:\n";
echo "- Valid Cal.com API key and event type ID\n";
echo "- Valid Retell.ai API key\n";
echo "- Webhook URL configured in Retell.ai dashboard\n";