<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\CalcomV2Service;
use App\Models\Company;

echo "\n" . str_repeat('=', 60) . "\n";
echo "DEBUG CAL.COM BOOKING\n";
echo str_repeat('=', 60) . "\n\n";

// Get company and API key
$company = Company::find(1);
if (!$company) {
    die("Company not found\n");
}

$apiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : config('services.calcom.api_key');
echo "Using API key: " . substr($apiKey, 0, 20) . "...\n\n";

// Create service
$calcomService = new CalcomV2Service($apiKey);

// Test data
$eventTypeId = 2563193; // Event type with teamId=39203 from user
$startTime = '2025-06-25T14:00:00+02:00';
$endTime = '2025-06-25T14:30:00+02:00';
$customerData = [
    'name' => 'Test Customer',
    'email' => 'test@example.com',
    'phone' => '+491234567890'
];
$notes = 'Test booking from debug script';

echo "Creating booking with:\n";
echo "- Event Type ID: $eventTypeId\n";
echo "- Start: $startTime\n";
echo "- End: $endTime\n";
echo "- Customer: {$customerData['name']}\n\n";

try {
    $result = $calcomService->bookAppointment(
        $eventTypeId,
        $startTime,
        $endTime,
        $customerData,
        $notes
    );
    
    echo "Result:\n";
    print_r($result);
    
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";