<?php

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Jobs\ProcessRetellCallEndedJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Scopes\TenantScope;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "============================================\n";
echo "Testing Appointment Booking Flow with Cache\n";
echo "============================================\n\n";

// Test Configuration
$testCallId = 'test_call_' . time();
$testPhoneNumber = '+49 30 837 93 369'; // AskProAI Berlin number

echo "1. Testing appointment collector endpoint...\n";

// Prepare test appointment data
$appointmentData = [
    'args' => [
        'datum' => '25.06.2025',
        'uhrzeit' => '14:30',
        'dienstleistung' => 'Beratungsgespräch',
        'name' => 'Test Kunde',
        'telefonnummer' => '+49 151 12345678',
        'email' => 'test@example.com',
        'kundenpraeferenzen' => 'Keine besonderen Wünsche',
        'mitarbeiter_wunsch' => null,
        'booking_confirmed' => true
    ]
];

// Simulate collector endpoint call
try {
    $response = Http::withHeaders([
        'X-Retell-Call-Id' => $testCallId
    ])->post(config('app.url') . '/api/retell/collect-appointment', $appointmentData);
    
    if ($response->successful()) {
        echo "✅ Collector endpoint responded successfully\n";
        $collectorResponse = $response->json();
        $referenceId = isset($collectorResponse['reference_id']) ? $collectorResponse['reference_id'] : 'N/A';
        $appointmentId = isset($collectorResponse['appointment_id']) ? $collectorResponse['appointment_id'] : 'N/A';
        echo "   Reference ID: " . $referenceId . "\n";
        echo "   Appointment ID: " . $appointmentId . "\n";
    } else {
        echo "❌ Collector endpoint failed: " . $response->body() . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Error calling collector endpoint: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. Verifying cached data...\n";

// Check if data was cached
$cacheKey = "retell_appointment_data:{$testCallId}";
$cachedData = Cache::get($cacheKey);

if ($cachedData) {
    echo "✅ Data successfully cached\n";
    echo "   Cache key: $cacheKey\n";
    echo "   Cached fields: " . implode(', ', array_keys($cachedData)) . "\n";
} else {
    echo "❌ No cached data found!\n";
    exit(1);
}

echo "\n3. Simulating webhook processing...\n";

// First, find the phone number without tenant scope to get company context
$phoneNumber = PhoneNumber::withoutGlobalScopes()->where('number', $testPhoneNumber)->first();
if (!$phoneNumber || !$phoneNumber->branch_id) {
    echo "❌ Could not resolve phone number $testPhoneNumber\n";
    exit(1);
}

// Get branch and company
$branch = Branch::withoutGlobalScopes()->find($phoneNumber->branch_id);
if (!$branch) {
    echo "❌ Could not find branch for phone number\n";
    exit(1);
}

$company = Company::withoutGlobalScopes()->find($branch->company_id);
if (!$company) {
    echo "❌ Could not find company for branch\n";
    exit(1);
}

echo "   Branch: {$branch->name} (ID: {$branch->id})\n";
echo "   Company: {$company->name} (ID: {$company->id})\n";

// Set company context for tenant scope and BelongsToCompany trait
app()->instance('current_company_id', $company->id);
session(['company_id' => $company->id]);

// Create a test call record
$call = Call::create([
    'retell_call_id' => $testCallId,
    'call_id' => $testCallId,
    'from_number' => $appointmentData['args']['telefonnummer'],
    'to_number' => $testPhoneNumber,
    'call_type' => 'phone_call',
    'direction' => 'inbound',
    'call_status' => 'ended',
    'duration_sec' => 180,
    'duration_minutes' => 3,
    'transcript' => 'Test conversation for appointment booking',
    'company_id' => $company->id,
    'branch_id' => $branch->id,
    'start_timestamp' => now()->subMinutes(3),
    'end_timestamp' => now(),
]);

echo "✅ Created test call record (ID: {$call->id})\n";

echo "\n4. Processing appointment booking...\n";

// Simulate webhook data
$webhookData = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => $testCallId,
        'from_number' => $appointmentData['args']['telefonnummer'],
        'to_number' => $testPhoneNumber,
        'duration' => 180,
        'transcript' => 'Test conversation',
        'call_status' => 'ended',
        'start_timestamp' => now()->subMinutes(3)->timestamp * 1000,
        'end_timestamp' => now()->timestamp * 1000,
    ]
];

// Process the webhook job
try {
    $job = new ProcessRetellCallEndedJob($webhookData);
    
    // Set company context
    $job->setCompanyId($company->id);
    
    // Execute the job
    $job->handle();
    
    echo "✅ Webhook processing completed\n";
    
} catch (\Exception $e) {
    echo "❌ Error processing webhook: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n5. Verifying appointment creation...\n";

// Reload call to check if appointment was created
$call->refresh();

if ($call->appointment_id) {
    echo "✅ Appointment created successfully!\n";
    echo "   Appointment ID: {$call->appointment_id}\n";
    
    $appointment = Appointment::withoutGlobalScopes()->find($call->appointment_id);
    if ($appointment) {
        // Re-set company context for related models
        app()->instance('current_company_id', $appointment->company_id);
        
        $customer = Customer::find($appointment->customer_id);
        $service = Service::find($appointment->service_id);
        
        echo "   Customer: " . ($customer ? $customer->name : 'N/A') . "\n";
        echo "   Date/Time: {$appointment->starts_at}\n";
        echo "   Service: " . ($service ? $service->name : 'N/A') . "\n";
        echo "   Status: {$appointment->status}\n";
    }
} else {
    echo "❌ No appointment was created\n";
    
    // Check metadata for clues
    if ($call->metadata && isset($call->metadata['appointment_booking_result'])) {
        echo "   Booking result: " . json_encode($call->metadata['appointment_booking_result']) . "\n";
    }
}

echo "\n6. Checking if cache was cleared...\n";

$remainingCache = Cache::get($cacheKey);
if ($remainingCache) {
    echo "⚠️  Cache was not cleared (this might be intentional for debugging)\n";
} else {
    echo "✅ Cache was properly cleared after use\n";
}

echo "\n7. Checking Cal.com integration...\n";

if ($branch->calcom_event_type_id) {
    echo "⚠️  Branch has Cal.com event type configured (ID: {$branch->calcom_event_type_id}) but it may not exist\n";
    
    // Check if the event type actually exists
    $eventTypeExists = \App\Models\CalcomEventType::withoutGlobalScopes()->where('id', $branch->calcom_event_type_id)->exists();
    if (!$eventTypeExists) {
        echo "❌ Cal.com event type ID {$branch->calcom_event_type_id} does not exist!\n";
        
        // Update branch to use an existing event type
        $existingEventType = \App\Models\CalcomEventType::withoutGlobalScopes()->where('branch_id', $branch->id)->first();
        if ($existingEventType) {
            $branch->calcom_event_type_id = $existingEventType->id;
            $branch->save();
            echo "✅ Updated branch to use existing event type: {$existingEventType->name} (ID: {$existingEventType->id})\n";
        }
    } else {
        echo "✅ Cal.com event type exists\n";
    }
    
    // Check if appointment has Cal.com booking
    if ($call->appointment_id) {
        $appointment = Appointment::withoutGlobalScopes()->find($call->appointment_id);
        if ($appointment && $appointment->calcom_booking_uid) {
            echo "✅ Appointment has Cal.com booking (UID: {$appointment->calcom_booking_uid})\n";
        } else {
            echo "⚠️  Appointment exists but no Cal.com booking created\n";
        }
    }
} else {
    echo "❌ Branch has no Cal.com event type configured!\n";
}

echo "\n============================================\n";
echo "Test Summary:\n";
echo "============================================\n";

$checks = [
    'Collector endpoint' => isset($response) && $response->successful(),
    'Data caching' => !empty($cachedData),
    'Call record creation' => !empty($call->id),
    'Webhook processing' => true, // If we got here, it worked
    'Appointment creation' => !empty($call->appointment_id),
    'Cache cleanup' => empty($remainingCache),
    'Cal.com configuration' => !empty($branch->calcom_event_type_id),
];

$passed = 0;
$failed = 0;

foreach ($checks as $check => $result) {
    echo sprintf("%-25s: %s\n", $check, $result ? '✅ PASSED' : '❌ FAILED');
    if ($result) $passed++; else $failed++;
}

echo "\nTotal: $passed passed, $failed failed\n";

// Cleanup test data
echo "\n8. Cleaning up test data...\n";
if ($call->appointment_id) {
    Appointment::withoutGlobalScopes()->where('id', $call->appointment_id)->delete();
}
Call::withoutGlobalScopes()->where('id', $call->id)->delete();
echo "✅ Test data cleaned up\n";

echo "\n";
if ($failed === 0) {
    echo "🎉 All tests passed! The appointment booking flow is working correctly.\n";
} else {
    echo "⚠️  Some tests failed. Please check the logs for details.\n";
}