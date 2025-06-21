<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n" . str_repeat('=', 60) . "\n";
echo "BOOK AVAILABLE SLOT VIA CAL.COM\n";
echo str_repeat('=', 60) . "\n\n";

$company = Company::find(1);
$apiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : config('services.calcom.api_key');

$eventTypeId = 2563193;
$teamId = 39203;

// Hole verfügbare Slots
$slotsUrl = "https://api.cal.com/v1/slots?apiKey=$apiKey&eventTypeId=$eventTypeId&teamId=$teamId&startTime=2025-06-23T00:00:00Z&endTime=2025-06-30T23:59:59Z";

echo "Fetching available slots...\n";

$response = Http::get($slotsUrl);

if ($response->successful()) {
    $data = $response->json();
    
    if (isset($data['slots']) && !empty($data['slots'])) {
        echo "Available slots found!\n\n";
        
        // Finde den ersten verfügbaren Slot
        $availableSlot = null;
        foreach ($data['slots'] as $date => $dateSlots) {
            if (!empty($dateSlots)) {
                foreach ($dateSlots as $slot) {
                    if (!$availableSlot || Carbon::parse($slot['time'])->lt(Carbon::parse($availableSlot['time']))) {
                        $availableSlot = $slot;
                    }
                }
            }
        }
        
        if ($availableSlot) {
            echo "Using slot: " . $availableSlot['time'] . "\n\n";
            
            // Versuche zu buchen
            $bookingData = [
                'eventTypeId' => (int)$eventTypeId,
                'start' => $availableSlot['time'],
                'timeZone' => 'Europe/Berlin',
                'language' => 'de',
                'responses' => [
                    'name' => 'MCP Test Customer',
                    'email' => 'mcp-test@example.com',
                    'notes' => 'Gebucht über MCP'
                ],
                'metadata' => [
                    'source' => 'askproai_mcp',
                    'call_id' => '999'
                ]
            ];
            
            // Falls Team Event Type, füge teamId hinzu
            if ($teamId) {
                $bookingData['teamId'] = $teamId;
            }
            
            echo "Attempting to book...\n";
            
            $bookingResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://api.cal.com/v1/bookings?apiKey=$apiKey", $bookingData);
            
            echo "Booking Response Status: " . $bookingResponse->status() . "\n";
            echo "Booking Response: " . $bookingResponse->body() . "\n\n";
            
            if ($bookingResponse->successful()) {
                echo "\n✅ BOOKING SUCCESSFUL!\n";
                $bookingResult = $bookingResponse->json();
                
                // Update branch mit funktionierendem Event Type
                DB::table('branches')
                    ->where('id', '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793')
                    ->update(['calcom_event_type_id' => $eventTypeId]);
                
                echo "Branch updated with working event type ID: $eventTypeId\n";
                
                // Test MCP webhook nochmal
                echo "\nTesting MCP webhook with confirmed working configuration...\n";
                
                $testPayload = [
                    'event' => 'call_ended',
                    'call' => [
                        'call_id' => 'mcp_test_' . time(),
                        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
                        'from_number' => '+491234567890',
                        'to_number' => '+493083793369',
                        'direction' => 'inbound',
                        'call_status' => 'ended',
                        'start_timestamp' => now()->subMinutes(5)->timestamp * 1000,
                        'end_timestamp' => now()->timestamp * 1000,
                        'duration_ms' => 300000,
                        'transcript' => 'Test transcript',
                        'summary' => 'Customer wants appointment next Tuesday at 2pm',
                        'call_analysis' => [
                            'appointment_requested' => true,
                            'customer_name' => 'MCP Test Customer',
                            'sentiment' => 'positive'
                        ],
                        'retell_llm_dynamic_variables' => [
                            'booking_confirmed' => true,
                            'name' => 'MCP Test Customer',
                            'datum' => Carbon::now()->addDays(7)->format('Y-m-d'),
                            'uhrzeit' => '14:00',
                            'dienstleistung' => 'Beratung'
                        ]
                    ]
                ];
                
                // Use webhook test script
                exec('php test-mcp-webhook-simple.php', $output);
                echo implode("\n", $output);
            }
        }
    } else {
        echo "No slots available\n";
        print_r($data);
    }
} else {
    echo "Failed to fetch slots\n";
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";