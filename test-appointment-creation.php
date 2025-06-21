<?php

// Test appointment creation from webhook data

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomV2Service;
use App\Models\Company;
use App\Models\Branch;

echo "=== Testing Appointment Creation Process ===\n\n";

// 1. Check Branch Configuration
echo "1. Checking Branch Configuration...\n";
$branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->find('7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b');

if (!$branch) {
    die("❌ Branch not found!\n");
}

echo "✅ Branch: " . $branch->name . "\n";
echo "  - Cal.com Event Type ID: " . ($branch->calcom_event_type_id ?: 'NOT SET') . "\n";
echo "  - Active: " . ($branch->is_active ? 'Yes' : 'No') . "\n";

// 2. Check Company Configuration
echo "\n2. Checking Company Configuration...\n";
$company = Company::find($branch->company_id);
echo "✅ Company: " . $company->name . "\n";
echo "  - Cal.com API Key: " . ($company->calcom_api_key ? 'SET' : 'NOT SET') . "\n";
echo "  - Cal.com Team Slug: " . ($company->calcom_team_slug ?: 'NOT SET') . "\n";

// 3. Test Cal.com Connection
echo "\n3. Testing Cal.com Connection...\n";
try {
    // CalcomV2Service gets API key from company automatically
    $calcom = new CalcomV2Service();
    
    // Get event types
    $eventTypes = $calcom->getEventTypes($company->calcom_team_slug);
    if ($eventTypes && isset($eventTypes['data'])) {
        echo "✅ Cal.com connection successful!\n";
        echo "  - Found " . count($eventTypes['data']) . " event types\n";
        
        // Check if branch event type exists
        $branchEventType = null;
        foreach ($eventTypes['data'] as $et) {
            if ($et['id'] == $branch->calcom_event_type_id) {
                $branchEventType = $et;
                break;
            }
        }
        
        if ($branchEventType) {
            echo "✅ Branch event type found: " . $branchEventType['title'] . "\n";
        } else {
            echo "❌ Branch event type ID " . $branch->calcom_event_type_id . " not found in Cal.com!\n";
        }
    } else {
        echo "❌ Failed to get Cal.com event types\n";
    }
} catch (\Exception $e) {
    echo "❌ Cal.com connection failed: " . $e->getMessage() . "\n";
}

// 4. Check recent calls for booking attempts
echo "\n4. Checking Recent Calls for Booking Data...\n";
$recentCalls = \DB::select("
    SELECT id, retell_call_id, extracted_date, extracted_time, 
           retell_dynamic_variables, analysis, created_at
    FROM calls 
    WHERE company_id = ? 
    AND created_at >= NOW() - INTERVAL 24 HOUR
    ORDER BY created_at DESC 
    LIMIT 5
", [$company->id]);

foreach ($recentCalls as $call) {
    echo "\nCall " . $call->retell_call_id . " (" . $call->created_at . "):\n";
    echo "  - Extracted Date: " . ($call->extracted_date ?: 'NONE') . "\n";
    echo "  - Extracted Time: " . ($call->extracted_time ?: 'NONE') . "\n";
    
    $dynamicVars = json_decode($call->retell_dynamic_variables, true);
    if ($dynamicVars) {
        echo "  - Dynamic Variables:\n";
        foreach (['booking_confirmed', 'datum', 'uhrzeit', 'name'] as $key) {
            if (isset($dynamicVars[$key])) {
                echo "    - $key: " . $dynamicVars[$key] . "\n";
            }
        }
    }
}

// 5. Test creating an appointment
echo "\n5. Testing Appointment Creation...\n";
$testData = [
    'eventTypeId' => $branch->calcom_event_type_id,
    'start' => '2025-06-25T14:00:00+02:00',
    'end' => '2025-06-25T14:30:00+02:00',
    'name' => 'Test Kunde',
    'email' => 'test@example.com',
    'phone' => '+491234567890',
    'notes' => 'Test-Buchung vom Webhook'
];

echo "Attempting to book:\n";
echo "  - Date/Time: 2025-06-25 14:00\n";
echo "  - Event Type: " . $branch->calcom_event_type_id . "\n";

try {
    $result = $calcom->bookAppointment(
        $testData['eventTypeId'],
        $testData['start'],
        $testData['end'],
        [
            'name' => $testData['name'],
            'email' => $testData['email'],
            'phone' => $testData['phone'],
            'timeZone' => 'Europe/Berlin'
        ],
        $testData['notes']
    );
    
    if ($result) {
        echo "✅ Appointment created successfully!\n";
        echo "  - Booking ID: " . ($result['id'] ?? 'unknown') . "\n";
    } else {
        echo "❌ Appointment creation returned null\n";
    }
} catch (\Exception $e) {
    echo "❌ Appointment creation failed: " . $e->getMessage() . "\n";
}