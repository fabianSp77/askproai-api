<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Models\CalcomEventType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat('=', 60) . "\n";
echo "FIX CAL.COM MCP BOOKING\n";
echo str_repeat('=', 60) . "\n\n";

// Für MCP brauchen wir einen Event Type, der keine User-Verfügbarkeit prüft
// Das sind normalerweise "Instant Meeting" Event Types

// 1. Erstelle oder finde einen Instant Meeting Event Type
$company = Company::find(1);
$apiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : config('services.calcom.api_key');

echo "1. Checking for instant meeting event type...\n";

// Schauen wir uns den Event Type 2563193 genauer an
$eventTypeId = 2563193;

// Versuche eine einfache Verfügbarkeitsabfrage
// Füge teamId hinzu, da es ein Team Event Type ist
$teamId = 39203; // Von der Benutzernachricht
$availabilityUrl = "https://api.cal.com/v1/availability?apiKey=$apiKey&eventTypeId=$eventTypeId&teamId=$teamId&dateFrom=2025-06-23&dateTo=2025-06-30";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $availabilityUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nAvailability check for event type $eventTypeId:\n";
echo "HTTP Status: $httpCode\n";

if ($response && $httpCode == 200) {
    $data = json_decode($response, true);
    
    if (isset($data['slots'])) {
        echo "Available slots found!\n";
        
        // Finde den ersten verfügbaren Slot
        $availableSlot = null;
        foreach ($data['slots'] as $date => $slots) {
            if (!empty($slots) && is_array($slots)) {
                foreach ($slots as $slot) {
                    if (!$availableSlot) {
                        $availableSlot = $slot;
                        echo "First available slot: $slot\n";
                        break 2;
                    }
                }
            }
        }
        
        if ($availableSlot) {
            // Versuche mit diesem Slot zu buchen
            echo "\n2. Attempting to book with available slot...\n";
            
            $bookingData = [
                'eventTypeId' => $eventTypeId,
                'start' => $availableSlot,
                'timeZone' => 'Europe/Berlin',
                'language' => 'de',
                'responses' => [
                    'name' => 'MCP Test Customer',
                    'email' => 'mcp-test@example.com',
                    'notes' => 'Booked via MCP'
                ],
                'metadata' => [
                    'source' => 'askproai_mcp',
                    'call_id' => '999'
                ]
            ];
            
            $bookingResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://api.cal.com/v1/bookings?apiKey=$apiKey", $bookingData);
            
            echo "Booking Response Status: " . $bookingResponse->status() . "\n";
            echo "Booking Response: " . $bookingResponse->body() . "\n";
            
            if ($bookingResponse->successful()) {
                echo "\n✅ BOOKING SUCCESSFUL!\n";
                $bookingResult = $bookingResponse->json();
                
                // Update the branch to use this working event type
                DB::table('branches')
                    ->where('id', '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793')
                    ->update(['calcom_event_type_id' => $eventTypeId]);
                
                echo "Branch updated with working event type ID: $eventTypeId\n";
            }
        }
    } else {
        echo "No slots available or different response format\n";
        print_r($data);
    }
} else {
    echo "Failed to check availability\n";
    echo "Response: $response\n";
}

// Alternative: Erstelle einen Instant Meeting Event Type
echo "\n3. Alternative: Create/find instant meeting event type...\n";

// Hole Event Types und suche nach einem Instant Meeting Type
$eventTypesResponse = Http::withHeaders([
    'Content-Type' => 'application/json',
])->get("https://api.cal.com/v1/event-types?apiKey=$apiKey");

if ($eventTypesResponse->successful()) {
    $eventTypes = $eventTypesResponse->json();
    
    $instantEventType = null;
    foreach ($eventTypes['event_types'] ?? [] as $type) {
        if (isset($type['metadata']['apps']['instant']) || 
            strpos(strtolower($type['title']), 'instant') !== false ||
            (isset($type['schedulingType']) && $type['schedulingType'] === 'INSTANT')) {
            $instantEventType = $type;
            echo "Found instant event type: {$type['title']} (ID: {$type['id']})\n";
            break;
        }
    }
    
    if (!$instantEventType) {
        // Suche nach einem Event Type ohne Schedule Requirements
        foreach ($eventTypes['event_types'] ?? [] as $type) {
            if (!isset($type['scheduleId']) || $type['scheduleId'] === null) {
                echo "Found event type without schedule: {$type['title']} (ID: {$type['id']})\n";
                $instantEventType = $type;
                break;
            }
        }
    }
}

echo "\n" . str_repeat('=', 60) . "\n";