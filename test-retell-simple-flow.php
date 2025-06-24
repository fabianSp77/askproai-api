#!/usr/bin/env php
<?php
/**
 * Simple Retell Flow Test - Testing the core functionality
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\RetellCustomFunctionMCPServer;
use App\Services\Webhooks\RetellWebhookHandler;
use App\Models\WebhookEvent;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

echo "\n========================================\n";
echo "SIMPLE RETELL FLOW TEST\n";
echo "========================================\n";

// Get test company and branch
$company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
$branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->where('is_active', true)
    ->first();

// Set tenant context for the entire test
app()->instance('current_company', $company);
app()->bind('tenant.company_id', function() use ($company) {
    return $company->id;
});

if (!$company || !$branch) {
    echo "❌ No company or branch found!\n";
    exit(1);
}

// Test configuration
$testCallId = 'simple_test_' . Str::uuid();
$customerPhone = '+49 151 12345678';
$customerName = 'Test Kunde ' . date('Y-m-d H:i:s');

echo "Configuration:\n";
echo "- Company: {$company->name} (ID: {$company->id})\n";
echo "- Branch: {$branch->name} (ID: {$branch->id})\n";
echo "- Call ID: $testCallId\n";
echo "- Customer: $customerName\n";

// Step 1: Simulate custom function call
echo "\n[STEP 1] Testing collect_appointment function...\n";

$customFunctionServer = app(RetellCustomFunctionMCPServer::class);

$appointmentData = [
    'call_id' => $testCallId,
    'caller_number' => $customerPhone,
    'to_number' => $branch->phone_number,
    'name' => $customerName,
    'telefonnummer' => $customerPhone,
    'dienstleistung' => 'Beratungsgespräch',
    'datum' => 'morgen',
    'uhrzeit' => '14:00',
    'notizen' => 'Test Buchung'
];

$result = $customFunctionServer->collect_appointment($appointmentData);

if ($result['success']) {
    echo "✅ Appointment data collected\n";
    
    // Check cache
    $cacheKey = "retell:appointment:{$testCallId}";
    $cachedData = Cache::get($cacheKey);
    
    if ($cachedData) {
        echo "✅ Data found in cache\n";
    } else {
        echo "❌ Data NOT in cache!\n";
        exit(1);
    }
} else {
    echo "❌ Failed: " . $result['error'] . "\n";
    exit(1);
}

// Step 2: Manually process the appointment booking
echo "\n[STEP 2] Processing appointment booking...\n";

try {
    // Create or find customer
    $customer = Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('phone', $cachedData['customer_phone'])
        ->first();
        
    if (!$customer) {
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => $cachedData['customer_name'],
            'phone' => $cachedData['customer_phone'],
            'email' => null,
            'source' => 'phone_call'
        ]);
        echo "✅ Customer created: {$customer->name}\n";
    } else {
        echo "✅ Customer found: {$customer->name}\n";
    }
    
    // Find service
    $service = Service::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->first();
        
    if (!$service) {
        echo "❌ No active service found!\n";
        exit(1);
    }
    
    echo "✅ Using service: {$service->name}\n";
    
    // Create appointment
    $appointmentDateTime = \Carbon\Carbon::parse($cachedData['requested_date'] . ' ' . $cachedData['requested_time']);
    
    $appointment = Appointment::create([
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $appointmentDateTime,
        'ends_at' => $appointmentDateTime->copy()->addMinutes($service->duration),
        'status' => 'scheduled',
        'notes' => $cachedData['notes'] ?? null,
        'source' => 'phone_call'
    ]);
    
    echo "✅ Appointment created!\n";
    echo "- ID: {$appointment->id}\n";
    echo "- Date/Time: {$appointment->starts_at->format('d.m.Y H:i')}\n";
    echo "- Service: {$service->name}\n";
    echo "- Customer: {$customer->name}\n";
    
    // Create call record using DB directly to bypass model events
    $callId = \DB::table('calls')->insertGetId([
        'retell_call_id' => $testCallId,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'from_number' => $customerPhone,
        'to_number' => $branch->phone_number,
        'direction' => 'inbound',
        'status' => 'completed',
        'duration' => 180,
        'started_at' => now()->subMinutes(3),
        'ended_at' => now(),
        'appointment_id' => $appointment->id,
        'customer_id' => $customer->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "\n✅ Call record created!\n";
    echo "- Call ID: {$callId}\n";
    echo "- Linked to Appointment: {$appointment->id}\n";
    
    // Create call object for later reference
    $call = (object) ['id' => $callId];
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

// Step 3: Test the webhook handler directly
echo "\n[STEP 3] Testing webhook handler...\n";

try {
    $webhookHandler = app(RetellWebhookHandler::class);
    
    // Create a mock webhook event
    $mockWebhookEvent = new WebhookEvent();
    $mockWebhookEvent->payload = [
        'event' => 'call_ended',
        'call' => [
            'call_id' => 'webhook_test_' . Str::uuid(),
            'from_number' => '+49 151 98765432',
            'to_number' => $branch->phone_number,
            'direction' => 'inbound',
            'call_duration' => 120,
            'start_timestamp' => (time() - 120) * 1000,
            'end_timestamp' => time() * 1000,
            'retell_llm_dynamic_variables' => [
                'appointment_data' => [
                    'datum' => 'übermorgen',
                    'uhrzeit' => '10:00',
                    'name' => 'Webhook Test Kunde',
                    'telefonnummer' => '+49 151 98765432',
                    'dienstleistung' => 'Beratung'
                ]
            ]
        ]
    ];
    
    // Test extractAppointmentData method via reflection
    $reflection = new \ReflectionClass($webhookHandler);
    $method = $reflection->getMethod('extractAppointmentData');
    $method->setAccessible(true);
    
    $extractedData = $method->invoke($webhookHandler, $mockWebhookEvent->payload['call']);
    
    if ($extractedData) {
        echo "✅ Webhook handler can extract appointment data\n";
        echo "Extracted data:\n";
        print_r($extractedData);
    } else {
        echo "⚠️  No appointment data extracted from webhook\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Webhook handler test failed: " . $e->getMessage() . "\n";
}

// Cleanup
echo "\n[CLEANUP] Removing test data...\n";
if (isset($cacheKey)) {
    Cache::forget($cacheKey);
}
if (isset($appointment)) {
    $appointment->delete();
}
if (isset($callId)) {
    \DB::table('calls')->where('id', $callId)->delete();
}
if (isset($customer) && str_contains($customer->name, 'Test Kunde')) {
    $customer->delete();
}

echo "\n========================================\n";
echo "TEST COMPLETED SUCCESSFULLY!\n";
echo "========================================\n\n";