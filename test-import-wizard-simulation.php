<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Services\EventTypeNameParser;
use App\Services\SmartEventTypeNameParser;
use Illuminate\Support\Facades\DB;

// Get test data
$company = Company::first();
$branch = DB::table('branches')
    ->where('company_id', $company->id)
    ->where('is_active', 1)
    ->first();

echo "=== Testing Event Type Import Wizard Flow ===\n\n";
echo "Company: {$company->name} (ID: {$company->id})\n";
echo "Branch: {$branch->name} (ID: {$branch->id})\n\n";

// Step 1: Load Event Types from Cal.com
echo "Step 1: Loading Event Types from Cal.com...\n";

$apiKey = decrypt($company->calcom_api_key);
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->get('https://api.cal.com/v2/event-types');

if (!$response->successful()) {
    echo "Error loading event types\n";
    exit(1);
}

$data = $response->json();
$eventTypes = [];

// Extract event types
if (isset($data['data']['eventTypeGroups'])) {
    foreach ($data['data']['eventTypeGroups'] as $group) {
        if (isset($group['eventTypes']) && is_array($group['eventTypes'])) {
            $eventTypes = array_merge($eventTypes, $group['eventTypes']);
        }
    }
}

echo "Found " . count($eventTypes) . " event types\n\n";

// Step 2: Analyze Event Types with Smart Parser
echo "Step 2: Analyzing Event Types...\n";

$smartParser = new SmartEventTypeNameParser();
$nameParser = new EventTypeNameParser();

// Pick first 3 event types for testing
$testEventTypes = array_slice($eventTypes, 0, 3);

foreach ($testEventTypes as $eventType) {
    echo "\nEvent Type: " . $eventType['title'] . " (ID: " . $eventType['id'] . ")\n";
    
    // Get detailed info for this event type
    $detailResponse = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->get("https://api.cal.com/v2/event-types/{$eventType['id']}");
    
    if ($detailResponse->successful()) {
        $details = $detailResponse->json();
        $fullEventType = $details['data']['eventType'] ?? $eventType;
        
        // Check for users
        $users = [];
        if (isset($fullEventType['users']) && is_array($fullEventType['users'])) {
            foreach ($fullEventType['users'] as $user) {
                $users[] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'] ?? null,
                ];
                echo "  - User: " . $user['name'] . " (ID: " . $user['id'] . ")\n";
            }
        }
        
        // Step 3: Staff Mapping
        echo "  Staff Mapping:\n";
        foreach ($users as $user) {
            // Try to find existing staff
            $existingStaff = null;
            
            if ($user['email']) {
                $existingStaff = DB::table('staff')
                    ->where('company_id', $company->id)
                    ->where('email', $user['email'])
                    ->first();
            }
            
            if (!$existingStaff && $user['name']) {
                $existingStaff = DB::table('staff')
                    ->where('company_id', $company->id)
                    ->where('name', 'LIKE', '%' . $user['name'] . '%')
                    ->first();
            }
            
            if ($existingStaff) {
                echo "    → Found existing staff: {$existingStaff->name}\n";
            } else {
                echo "    → Would create new staff: {$user['name']}\n";
            }
        }
    }
}

// Step 4: Check current assignments
echo "\n\nCurrent Staff-EventType Assignments:\n";
$assignments = DB::table('staff_event_types')
    ->join('staff', 'staff.id', '=', 'staff_event_types.staff_id')
    ->join('calcom_event_types', 'calcom_event_types.id', '=', 'staff_event_types.event_type_id')
    ->where('staff.company_id', $company->id)
    ->select(
        'staff.name as staff_name',
        'calcom_event_types.name as event_type_name',
        'staff_event_types.calcom_user_id',
        'staff_event_types.is_primary'
    )
    ->get();

foreach ($assignments as $assignment) {
    echo "- {$assignment->staff_name} → {$assignment->event_type_name}";
    if ($assignment->calcom_user_id) {
        echo " (Cal.com User: {$assignment->calcom_user_id})";
    }
    echo "\n";
}

echo "\n=== Test Complete ===\n";