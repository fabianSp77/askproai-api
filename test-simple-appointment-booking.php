<?php

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Call;
use App\Models\Service;
use App\Jobs\ProcessRetellCallEndedJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "============================================\n";
echo "Simple Appointment Booking Test\n";
echo "============================================\n\n";

// Test Configuration
$testCallId = 'simple_test_' . time();
$testPhoneNumber = '+49 30 837 93 369'; // AskProAI Berlin number

// 1. Get branch and company
$phoneNumber = PhoneNumber::withoutGlobalScopes()->where('number', $testPhoneNumber)->first();
if (!$phoneNumber || !$phoneNumber->branch_id) {
    echo "❌ Could not resolve phone number\n";
    exit(1);
}

$branch = Branch::withoutGlobalScopes()->find($phoneNumber->branch_id);
$company = Company::withoutGlobalScopes()->find($branch->company_id);

echo "Branch: {$branch->name} (ID: {$branch->id})\n";
echo "Company: {$company->name} (ID: {$company->id})\n\n";

// Set company context
app()->instance('current_company_id', $company->id);
session(['company_id' => $company->id]);

// 2. Clear branch's calcom_event_type_id to avoid foreign key issues
echo "Clearing branch's Cal.com event type to test without Cal.com integration...\n";
$originalEventTypeId = $branch->calcom_event_type_id;
$branch->calcom_event_type_id = null;
$branch->save();
echo "✅ Temporarily cleared Cal.com event type\n\n";

// 3. Create appointment data
$appointmentData = [
    'datum' => '25.06.2025',
    'uhrzeit' => '14:30',
    'dienstleistung' => 'Beratungsgespräch',
    'name' => 'Test Kunde',
    'telefonnummer' => '+49 151 12345678',
    'email' => 'test@example.com',
    'notizen' => 'Testbuchung ohne Cal.com',
];

// 4. Cache the appointment data (simulating the collector)
$cacheKey = "retell_appointment_data:{$testCallId}";
$cachedData = array_merge($appointmentData, [
    'appointment_id' => 'TEST-' . strtoupper(uniqid()),
    'reference_id' => 'REF-' . strtoupper(uniqid()),
    'collected_at' => now()->toIso8601String(),
    'call_id' => $testCallId,
    'status' => 'collected'
]);

Cache::put($cacheKey, $cachedData, 3600);
echo "✅ Cached appointment data\n\n";

// 5. Create test call
$call = Call::create([
    'retell_call_id' => $testCallId,
    'call_id' => $testCallId,
    'from_number' => $appointmentData['telefonnummer'],
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

echo "✅ Created test call (ID: {$call->id})\n\n";

// 6. Process webhook
echo "Processing appointment booking...\n";

$webhookData = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => $testCallId,
        'from_number' => $appointmentData['telefonnummer'],
        'to_number' => $testPhoneNumber,
        'duration' => 180,
        'transcript' => 'Test conversation',
        'call_status' => 'ended',
        'start_timestamp' => now()->subMinutes(3)->timestamp * 1000,
        'end_timestamp' => now()->timestamp * 1000,
    ]
];

try {
    $job = new ProcessRetellCallEndedJob($webhookData);
    $job->setCompanyId($company->id);
    $job->handle();
    echo "✅ Webhook processing completed\n\n";
} catch (\Exception $e) {
    echo "❌ Error processing webhook: " . $e->getMessage() . "\n\n";
}

// 7. Check results
$call->refresh();

if ($call->appointment_id) {
    echo "✅ APPOINTMENT CREATED SUCCESSFULLY!\n";
    echo "   Appointment ID: {$call->appointment_id}\n";
    
    $appointment = \App\Models\Appointment::withoutGlobalScopes()->find($call->appointment_id);
    if ($appointment) {
        // Re-set company context for related models
        app()->instance('current_company_id', $appointment->company_id);
        
        $customer = \App\Models\Customer::find($appointment->customer_id);
        $service = \App\Models\Service::find($appointment->service_id);
        
        echo "   Customer: " . ($customer ? $customer->name : 'N/A') . "\n";
        echo "   Date/Time: {$appointment->starts_at}\n";
        echo "   Service: " . ($service ? $service->name : 'N/A') . "\n";
        echo "   Status: {$appointment->status}\n";
        echo "   Cal.com Event Type: " . ($appointment->calcom_event_type_id ?? 'None') . "\n";
    }
} else {
    echo "❌ No appointment was created\n";
    
    // Check recent logs
    echo "\nChecking recent logs...\n";
    $logs = shell_exec("tail -n 20 /var/www/api-gateway/storage/logs/laravel-2025-06-24.log | grep -E '(ERROR|Exception)' | tail -5");
    echo $logs;
}

// 8. Cleanup
echo "\nCleaning up...\n";

// Restore branch's original event type
$branch->calcom_event_type_id = $originalEventTypeId;
$branch->save();

// Delete test data
if ($call->appointment_id) {
    \App\Models\Appointment::withoutGlobalScopes()->where('id', $call->appointment_id)->delete();
}
Call::withoutGlobalScopes()->where('id', $call->id)->delete();

echo "✅ Cleanup completed\n";