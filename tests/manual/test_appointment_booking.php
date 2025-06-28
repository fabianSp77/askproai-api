<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test appointment booking with collect_appointment_data format
$testData = [
    'datum' => Carbon::tomorrow()->format('d.m.Y'), // Tomorrow
    'uhrzeit' => '14:30',
    'name' => 'Test Kunde',
    'telefonnummer' => '+49123456789',
    'email' => 'test@example.com',
    'dienstleistung' => '', // Empty service to test without specific service
    'kundenpraeferenzen' => 'Gerne nachmittags'
];

echo "Testing appointment booking with data:\n";
print_r($testData);

// Get first company and branch
$company = Company::first();
if (!$company) {
    die("No company found in database!\n");
}

$branch = $company->branches()->first();
if (!$branch) {
    die("No branch found for company!\n");
}

echo "\nUsing Company: {$company->name} (ID: {$company->id})\n";
echo "Using Branch: {$branch->name} (ID: {$branch->id})\n";

// Create a test call record
$call = new Call([
    'company_id' => $company->id,
    'branch_id' => $branch->id,
    'call_id' => 'test_' . uniqid(),
    'retell_call_id' => 'test_' . uniqid(),
    'from_number' => $testData['telefonnummer'],
    'to_number' => '+49123456780',
    'call_type' => 'test',
    'direction' => 'inbound',
    'call_status' => 'ended',
    'duration_sec' => 120,
    'start_timestamp' => Carbon::now()->subMinutes(2),
    'end_timestamp' => Carbon::now(),
    'raw_data' => []
]);

$call->save();

echo "\nCreated test call with ID: {$call->id}\n";

// Test appointment booking
$bookingService = new AppointmentBookingService();

echo "\nBooking appointment...\n";

try {
    $result = $bookingService->bookFromPhoneCall($call, $testData);
    
    if ($result['success']) {
        echo "\n✅ SUCCESS! Appointment booked:\n";
        echo "Appointment ID: " . $result['appointment']->id . "\n";
        echo "Customer: " . $result['appointment']->customer->name . "\n";
        echo "Date/Time: " . $result['appointment']->starts_at . "\n";
        echo "Status: " . $result['appointment']->status . "\n";
    } else {
        echo "\n❌ FAILED! Error: " . $result['message'] . "\n";
    }
} catch (\Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Cleanup
$call->delete();
echo "\nTest call deleted.\n";