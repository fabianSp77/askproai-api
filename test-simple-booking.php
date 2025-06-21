<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\CalcomV2Service;
use App\Models\Company;

echo "\n" . str_repeat('=', 60) . "\n";
echo "TEST SIMPLE BOOKING\n";
echo str_repeat('=', 60) . "\n\n";

// Get company
$company = Company::find(1);
$apiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : config('services.calcom.api_key');

// Create service
$calcomService = new CalcomV2Service($apiKey);

// First, let's get event types to find one that works
echo "Getting event types...\n";
$eventTypes = $calcomService->getEventTypes();

if ($eventTypes['success'] ?? false) {
    $availableTypes = [];
    foreach ($eventTypes['data']['event_types'] ?? [] as $type) {
        if (!$type['hidden']) {
            $availableTypes[] = $type;
            echo "- ID: {$type['id']}, Title: {$type['title']}, Slug: {$type['slug']}\n";
        }
    }
    
    if (!empty($availableTypes)) {
        // Use the first available event type
        $eventType = $availableTypes[0];
        $eventTypeId = $eventType['id'];
        
        echo "\nUsing event type: {$eventType['title']} (ID: $eventTypeId)\n\n";
        
        // Update branch to use this event type
        DB::table('branches')
            ->where('id', '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793')
            ->update(['calcom_event_type_id' => $eventTypeId]);
        
        echo "Updated branch with new event type ID\n";
        
        // Now test booking
        $bookingData = [
            'eventTypeId' => $eventTypeId,
            'start' => '2025-06-26T10:00:00+02:00',
            'end' => '2025-06-26T10:30:00+02:00',
            'timeZone' => 'Europe/Berlin',
            'language' => 'de',
            'metadata' => [
                'source' => 'askproai_test'
            ],
            'responses' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com'
            ]
        ];
        
        echo "\nTesting booking with minimal data...\n";
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://api.cal.com/v1/bookings?apiKey=' . $apiKey, $bookingData);
        
        echo "Status: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";