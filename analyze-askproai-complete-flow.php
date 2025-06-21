<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n=== ASKPROAI END-TO-END FLOW ANALYSIS ===\n\n";

// 1. RETELL.AI INTEGRATION ANALYSIS
echo "1. RETELL.AI MCP INTEGRATION\n";
echo "===========================\n\n";

// Check Retell webhook configuration
echo "a) Webhook Configuration:\n";
$retellRoutes = collect(\Route::getRoutes())->filter(function($route) {
    return str_contains($route->uri(), 'retell');
});

foreach ($retellRoutes as $route) {
    echo "   - " . strtoupper(implode('|', $route->methods())) . " /" . $route->uri() . "\n";
    echo "     Controller: " . ($route->getActionName() ?? 'N/A') . "\n";
    echo "     Middleware: " . implode(', ', $route->gatherMiddleware() ?? []) . "\n\n";
}

// Check MCP services
echo "b) MCP Services:\n";
$mcpServices = [
    'RetellMCPServer' => App\Services\MCP\RetellMCPServer::class,
    'CalcomMCPServer' => App\Services\MCP\CalcomMCPServer::class,
    'WebhookMCPServer' => App\Services\MCP\WebhookMCPServer::class,
    'DatabaseMCPServer' => App\Services\MCP\DatabaseMCPServer::class,
];

foreach ($mcpServices as $name => $class) {
    if (class_exists($class)) {
        echo "   ✓ $name: Available\n";
        
        // Check key methods
        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $keyMethods = array_filter($methods, function($m) {
            return !str_starts_with($m->getName(), '__');
        });
        
        echo "     Methods: " . implode(', ', array_map(fn($m) => $m->getName(), array_slice($keyMethods, 0, 5))) . "...\n";
    } else {
        echo "   ✗ $name: Not found\n";
    }
}

// 2. CAL.COM INTEGRATION ANALYSIS
echo "\n2. CAL.COM MCP INTEGRATION\n";
echo "==========================\n\n";

// Check Cal.com configuration
echo "a) API Configuration:\n";
$calcomConfig = [
    'api_key' => config('services.calcom.api_key') ? 'Set' : 'Not set',
    'api_version' => config('services.calcom.api_version', 'v1'),
    'base_url' => config('services.calcom.base_url', 'https://api.cal.com'),
];

foreach ($calcomConfig as $key => $value) {
    echo "   - $key: $value\n";
}

// 3. DATA FLOW ANALYSIS
echo "\n3. DATA FLOW ANALYSIS\n";
echo "=====================\n\n";

// Check AskProAI company setup
echo "a) Company Configuration:\n";
$company = DB::table('companies')->where('name', 'LIKE', '%AskProAI%')->first();
if ($company) {
    echo "   Company: {$company->name} (ID: {$company->id})\n";
    echo "   - Retell API Key: " . ($company->retell_api_key ? 'Set' : 'Not set') . "\n";
    echo "   - Cal.com API Key: " . ($company->calcom_api_key ? 'Set' : 'Not set') . "\n";
    echo "   - Active: " . ($company->is_active ? 'Yes' : 'No') . "\n";
    
    // Check branches
    echo "\nb) Branch Configuration:\n";
    $branches = DB::table('branches')->where('company_id', $company->id)->get();
    foreach ($branches as $branch) {
        echo "   Branch: {$branch->name} (ID: {$branch->id})\n";
        echo "   - Phone: " . ($branch->phone_number ?? 'Not set') . "\n";
        echo "   - Active: " . ($branch->is_active ? 'Yes' : 'No') . "\n";
        echo "   - Cal.com Event Type ID: " . ($branch->calcom_event_type_id ?? 'Not set') . "\n";
        echo "   - Retell Agent ID: " . ($branch->retell_agent_id ?? 'Not set') . "\n";
        
        // Check phone numbers
        $phoneNumbers = DB::table('phone_numbers')->where('branch_id', $branch->id)->get();
        if ($phoneNumbers->count() > 0) {
            echo "   - Phone Numbers:\n";
            foreach ($phoneNumbers as $phone) {
                echo "     • {$phone->number} (Active: " . ($phone->active ? 'Yes' : 'No') . ")\n";
            }
        }
    }
    
    // Check Cal.com event types
    echo "\nc) Cal.com Event Types:\n";
    $eventTypes = DB::table('calcom_event_types')->where('company_id', $company->id)->get();
    if ($eventTypes->count() > 0) {
        foreach ($eventTypes as $et) {
            echo "   - {$et->name} (ID: {$et->id}, Cal.com ID: " . ($et->calcom_numeric_event_type_id ?? 'N/A') . ")\n";
            echo "     Duration: {$et->duration_minutes} min, Active: " . ($et->is_active ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "   No event types configured\n";
    }
}

// 4. MCP SERVICE ORCHESTRATION
echo "\n4. MCP SERVICE ORCHESTRATION\n";
echo "============================\n\n";

// Analyze the flow
echo "a) Webhook Processing Flow:\n";
echo "   1. Retell Webhook → RetellWebhookMCPController\n";
echo "   2. Rate Limiting → ApiRateLimiter\n";
echo "   3. Signature Verification → VerifyRetellSignature\n";
echo "   4. Context Resolution → MCPContextResolver\n";
echo "   5. Webhook Processing → WebhookMCPServer\n";
echo "      - Phone → Branch Resolution\n";
echo "      - Customer Creation/Matching\n";
echo "      - Call Record Creation\n";
echo "      - Appointment Creation (if needed)\n";
echo "   6. Cal.com Booking → CalcomMCPServer\n";
echo "\n";

// Test the flow
echo "b) Testing Phone Resolution:\n";
$testPhone = '+493083793369';
echo "   Test Phone: $testPhone\n";

// Check phone resolution
$phoneResolver = app(\App\Services\PhoneNumberResolver::class);
$resolution = $phoneResolver->resolve($testPhone);
echo "   Resolution Result: " . json_encode($resolution, JSON_PRETTY_PRINT) . "\n";

// 5. CRITICAL CONFIGURATION ISSUES
echo "\n5. CRITICAL CONFIGURATION ISSUES\n";
echo "================================\n\n";

$issues = [];

// Check company configuration
if (!$company) {
    $issues[] = "No AskProAI company found";
} else {
    if (!$company->retell_api_key) {
        $issues[] = "Retell API key not configured for company";
    }
    if (!$company->calcom_api_key) {
        $issues[] = "Cal.com API key not configured for company";
    }
}

// Check branch configuration
if ($branches->isEmpty()) {
    $issues[] = "No branches configured";
} else {
    foreach ($branches as $branch) {
        if (!$branch->calcom_event_type_id) {
            $issues[] = "Branch '{$branch->name}' missing Cal.com event type";
        }
        if (!$branch->phone_number && DB::table('phone_numbers')->where('branch_id', $branch->id)->count() == 0) {
            $issues[] = "Branch '{$branch->name}' has no phone numbers";
        }
    }
}

// Check event types
if ($eventTypes->isEmpty()) {
    $issues[] = "No Cal.com event types configured";
}

// Check webhook signature
if (!config('services.retell.webhook_secret')) {
    $issues[] = "Retell webhook secret not configured";
}

if (empty($issues)) {
    echo "✓ No critical issues found\n";
} else {
    foreach ($issues as $issue) {
        echo "✗ $issue\n";
    }
}

// 6. TEST PLAN FOR ASKPROAI
echo "\n6. COMPREHENSIVE TEST PLAN\n";
echo "==========================\n\n";

echo "a) Setup Requirements:\n";
echo "   1. Configure Retell API key for company\n";
echo "   2. Configure Cal.com API key for company\n";
echo "   3. Set up phone number for branch\n";
echo "   4. Configure Cal.com event type\n";
echo "   5. Set Retell agent ID\n";
echo "\n";

echo "b) Test Scenarios:\n";
echo "   1. Phone Call → Branch Resolution\n";
echo "      - Call to configured phone number\n";
echo "      - Verify correct branch identified\n";
echo "\n";
echo "   2. Customer Creation\n";
echo "      - New caller → Create customer\n";
echo "      - Existing caller → Match customer\n";
echo "\n";
echo "   3. Appointment Booking\n";
echo "      - Request appointment date/time\n";
echo "      - Check availability via Cal.com\n";
echo "      - Create booking\n";
echo "      - Verify in database\n";
echo "\n";
echo "   4. Error Handling\n";
echo "      - Invalid phone number\n";
echo "      - No availability\n";
echo "      - API failures\n";
echo "\n";

// 7. MCP FUNCTION ANALYSIS
echo "7. MCP FUNCTION ANALYSIS\n";
echo "========================\n\n";

// Analyze WebhookMCPServer functions
echo "a) WebhookMCPServer Functions:\n";
$webhookMCP = new App\Services\MCP\WebhookMCPServer(
    app(App\Services\MCP\CalcomMCPServer::class),
    app(App\Services\MCP\RetellMCPServer::class),
    app(App\Services\MCP\DatabaseMCPServer::class),
    app(App\Services\MCP\QueueMCPServer::class)
);

$webhookMethods = [
    'processRetellWebhook' => 'Main webhook processing',
    'resolvePhoneNumber' => 'Phone → Branch resolution',
    'findOrCreateCustomer' => 'Customer management',
    'saveCallRecord' => 'Call record creation',
    'shouldCreateAppointment' => 'Appointment decision logic',
    'createAppointmentViaMCP' => 'Cal.com booking creation',
];

foreach ($webhookMethods as $method => $description) {
    echo "   - $method: $description\n";
}

// Recent webhook activity
echo "\nb) Recent Webhook Activity:\n";
$recentCalls = DB::table('calls')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($recentCalls->count() > 0) {
    foreach ($recentCalls as $call) {
        echo "   - Call {$call->retell_call_id}\n";
        echo "     From: {$call->from_number} → To: {$call->to_number}\n";
        echo "     Branch: " . ($call->branch_id ?? 'Not resolved') . "\n";
        echo "     Appointment: " . ($call->appointment_id ? "Created (ID: {$call->appointment_id})" : 'None') . "\n";
        echo "     Created: {$call->created_at}\n\n";
    }
} else {
    echo "   No recent calls found\n";
}

// 8. RECOMMENDATIONS
echo "\n8. RECOMMENDATIONS\n";
echo "==================\n\n";

echo "1. Immediate Actions:\n";
echo "   - Set up API keys in company record\n";
echo "   - Configure phone numbers for branches\n";
echo "   - Import Cal.com event types\n";
echo "   - Set Retell agent IDs\n";
echo "\n";

echo "2. Testing Strategy:\n";
echo "   - Use test-mcp-webhook-final.php for webhook testing\n";
echo "   - Monitor logs with: tail -f storage/logs/laravel.log | grep MCP\n";
echo "   - Check database after each test\n";
echo "\n";

echo "3. Monitoring:\n";
echo "   - Enable debug logging for MCP services\n";
echo "   - Set up alerts for failed bookings\n";
echo "   - Monitor API rate limits\n";
echo "\n";

echo "=== ANALYSIS COMPLETE ===\n";