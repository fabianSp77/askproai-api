<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\CalcomV2Service;
use App\Models\Company;
use Illuminate\Support\Facades\Http;

echo "\n" . str_repeat('=', 60) . "\n";
echo "CREATE INSTANT BOOKING VIA CAL.COM\n";
echo str_repeat('=', 60) . "\n\n";

// Get company
$company = Company::find(1);
$apiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : config('services.calcom.api_key');

echo "Using API key: " . substr($apiKey, 0, 20) . "...\n\n";

// For instant bookings, we need to use a public event type
// Let's find a public event type first
$calcomService = new CalcomV2Service($apiKey);
$eventTypes = $calcomService->getEventTypes();

if ($eventTypes['success'] ?? false) {
    $publicEventType = null;
    
    foreach ($eventTypes['data']['event_types'] ?? [] as $type) {
        if (!$type['hidden']) {
            echo "Found event type: {$type['title']} (ID: {$type['id']}, Slug: {$type['slug']})\n";
            if (!$publicEventType) {
                $publicEventType = $type;
            }
        }
    }
    
    if ($publicEventType) {
        echo "\nUsing event type: {$publicEventType['title']}\n";
        echo "Slug: {$publicEventType['slug']}\n";
        echo "ID: {$publicEventType['id']}\n\n";
        
        // For instant booking, we need to use the public booking endpoint
        // This doesn't require user availability checks
        
        $bookingData = [
            'start' => '2025-06-26T10:00:00+02:00',
            'eventTypeSlug' => $publicEventType['slug'],
            'timeZone' => 'Europe/Berlin',
            'language' => 'de',
            'metadata' => [
                'source' => 'askproai_mcp'
            ],
            'user' => 'askproai', // Username from the event type
            'name' => 'Test MCP Customer',
            'email' => 'test@example.com',
            'smsReminderNumber' => '+491234567890',
            'notes' => 'Test booking via MCP'
        ];
        
        echo "Testing instant booking...\n\n";
        
        // Use the instant meeting endpoint
        $instantMeetingUrl = "https://api.cal.com/v1/bookings/instant";
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($instantMeetingUrl . '?apiKey=' . $apiKey, $bookingData);
        
        echo "Response Status: " . $response->status() . "\n";
        echo "Response Body: " . $response->body() . "\n\n";
        
        if ($response->status() !== 200) {
            // Try with regular booking endpoint but with instant flag
            echo "Trying alternative approach...\n\n";
            
            $bookingDataAlt = [
                'eventTypeId' => $publicEventType['id'],
                'start' => '2025-06-26T10:00:00+02:00',
                'timeZone' => 'Europe/Berlin',
                'language' => 'de',
                'instant' => true, // Mark as instant booking
                'responses' => [
                    'name' => 'Test MCP Customer',
                    'email' => 'test@example.com',
                    'phone' => '+491234567890',
                    'notes' => 'Test booking via MCP'
                ],
                'metadata' => [
                    'source' => 'askproai_mcp'
                ]
            ];
            
            $response2 = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api.cal.com/v1/bookings?apiKey=' . $apiKey, $bookingDataAlt);
            
            echo "Alternative Response Status: " . $response2->status() . "\n";
            echo "Alternative Response Body: " . $response2->body() . "\n";
        }
    }
}

echo "\n" . str_repeat('=', 60) . "\n";